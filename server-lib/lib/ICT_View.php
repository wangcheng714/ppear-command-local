<?php


class ICT_View extends F3{



    const MAP_EXT = '.json';

    /**
     * @var string
     */
    protected static $_map_dir = "";

    /**
     * @var array
     */
    protected static $_maps = array();

    /**
     * @var array
     */
    protected static $_collection = array(
        'js' => array(),
        'css' => array()
    );

    protected static $_imported = array();

    protected static $map = array();

    private static $_blocks =  array();

    //产品线名称例如pc、mo
    private static $productName = null;

    public static function setProductName($name){
        if(isset($name)){
            self::$productName = $name;
            self::$_map_dir .= '/' . $name;
        }
    }

    /**
     * @param string $map_dir
     */
    public static function setMapDir($map_dir){
        self::$_map_dir = $map_dir;
    }

    /**
     * @return string
     */
    public static function getMapDir(){
        return self::$_map_dir;
    }

    /**
     * @param string $id
     * @param string $def_ns
     * @return string
     */
    private static function getNamespace(&$id, $def_ns = null){
        $pos = strpos($id, ':');
        if($pos === false){
            if($def_ns === null){
                $def_ns = '__global__';
            } else {
                $id = $def_ns . ':' . $id;
            }
            return $def_ns;
        } else {
            return substr($id, 0, $pos);
        }
    }


    /**
     * @param $id
     * @param $caller_ns
     * @param &$ns
     * @param &$map
     * @return mixed
     */
    public static function getInfo(&$id, $caller_ns = null, &$ns = null, &$map = null){
        $ns = self::getNamespace($id, $caller_ns);

        if(isset(self::$_maps[$ns])){
           $map = self::$_maps[$ns];
        } else {
            if(self::$_map_dir){

                if($ns === '__global__'){
                    $map_file = self::$_map_dir . '/map' . self::MAP_EXT;
                } else {
                    $map_file = self::$_map_dir . '/' . $ns . '-map' . self::MAP_EXT;
                }
                if(file_exists($map_file)){
                    if(self::MAP_EXT === '.php'){
                        self::$map = $map = self::$_maps[$ns] = include $map_file;
                    } else {
                        self::$map = $map = self::$_maps[$ns] = json_decode(file_get_contents($map_file), true);
                    }
                } else {
                    trigger_error('unable to load reource map [' . $map_file . ']', E_USER_ERROR);
                }
            } else {
                trigger_error('undefined resource map dir', E_USER_ERROR);
            }
        }
        if(isset($map['res'][$id])){
            return $map['res'][$id];
        } else {
            trigger_error('undefined resource [' . $id . ']', E_USER_ERROR);
        }
        return null;
    }

    public static function import($id, $caller_ns = null){
        if(isset(self::$_imported[$id])){
            return self::$_imported[$id];
        } else {
            $info = self::getInfo($id, $caller_ns, $ns, $map);
            if($info){
                $uri = $info['uri'];
                $type = $info['type'];
                if(isset($info['pkg'])){
                    $info = $map['pkg'][$info['pkg']];
                    $uri = $info['uri'];
                    foreach($info['has'] as $rId){
                        self::$_imported[$rId] = $uri;
                    }
                } else {
                    self::$_imported[$id] = $uri;
                }
                if(isset($info['deps'])){
                    foreach($info['deps'] as $dId){
                        self::import($dId);
                    }
                }
                if(is_array(self::$_collection[$type])){
                   self::$_collection[$type][] = $uri;
                }
                return $uri;
            } else {
                return null;
            }
        }
    }

    public static function block ( $block_name, $default="" ){

        if( !self::$_blocks[$block_name] && is_callable( $default ) ){
            $default (null);
        }else if ( is_callable( self::$_blocks[$block_name] ) ){
            $func = self::$_blocks[$block_name] ;
            $vars = self::$vars;
            $func( $vars );
        }else{
            echo $default;
        }
    }

    public static function define_block( $block_name, $block ){

        if( self::$_blocks[$block_name] ){
            return ;
        }

        if( is_callable($block) ){
            self::$_blocks[$block_name] = $block;
        }

    }

    public static function load ( $path, $caller_ns=null, $replace_static=false, $data=null ){

        $uri = self::import( $path, $caller_ns );

        $html = self::render($uri, $data);

        if ( $replace_static ){
            $html = ICT_Static::replace($html);
        }

        return $html;
    }

/**********支持本地调试升级*********/

    private static $templateDir = null;

    public static function setTemplateDir($templateDir){
        self::$templateDir = $templateDir;
    }

    public static function getTemplateDir($templateDir){
        return self::$templateDir;
    }

    public static function resolve($subPath){
        $file = realpath(self::$templateDir . $subPath);
        return $file;
    }

    public static function renderTestPage($page_path, $data){
        self::$vars = array_merge(self::$vars, $data);
        $uri = self::import( $page_path, $caller_ns );
        $html = self::render($uri, $data);
        $html = ICT_Static::replace($html);

        echo $html;
    }

/**********支持本地调试升级*********/


    public static function _load( $path, $data = null){
        $uri = self::import($path, null);

        $html = self::render($uri, $data);

        //if load layour ,replace static
        // $html = ICT_Static::replace($html);

        return $html;
    }

    public static function layout( $path ) {
       echo self::load( $path , null, true );
    }

    public static function widget ( $path, $data=null ) {
       echo self::load( $path, null, false, $data );
    }

    private static function sandbox($file, $data=null) {
        $vars = self::$vars;
        $vars['data'] = $data;
        // Render
        return require $file;
    }

    /**
     *   覆写，修改渲染的沙盒环境，传入F3的模板数据
    **/
    static function render($file, $data=null) {
        $file=self::resolve($file);
        ob_start();
        // Render
        self::sandbox($file, $data);
        $out =  ob_get_clean();
        return $out;
    }

    private static $_pool = array();
    private static $_pool_name;

    public static function startPool($name = '__global__'){
        if(!isset(self::$_pool[$name])){
            self::$_pool[$name] = '';
        }
        self::$_pool_name = $name;
        ob_start();
    }

    public static function endPool(){
        self::$_pool[self::$_pool_name] .= ob_get_clean();
    }

    public static function renderPool($name = '__global__'){
        if(isset(self::$_pool[$name])){
            return self::$_pool[$name];
        } else {
            return '';
        }
    }

    //收集script片断
    public static function startScript(){
        self::startPool('script');
    }
    public static function endScript(){
        self::endPool();
    }
    public static function renderScript(){
        echo self::renderPool('script');
    }

    //载入cms片段
    //path管理路径：http://cp.p2pzc.com/cms/list

    public static function cms( $path ){
        $cms = Cms::loadByPath($path);
        if(!empty($cms) && !empty($cms[0])){
            echo $cms[0]['content'];
        }
    }

}

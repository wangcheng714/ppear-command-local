<?php

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "ICT_Static.php";

class ICT_View extends F3{



    const MAP_EXT = '.json';
    const STATIC_NORMAL_LOAD_TYPE = 'normal';
    const STATIC_LS_LOAD_TYPE = 'localstorage';
    const STATIC_DEBUG_LOAD_TYPE = 'debug';

    const RENDER_ENV_PRO = 'pro';
    const RENDER_ENV_DEV = 'dev';

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

    //产品线名称例如pc、mo、hybrid
    private static $productName = null;

    public static function setProductName($name){
        if(isset($name)){
            self::$productName = $name;
            self::$_map_dir .= '/' . $name;
        }
    }

    public static function getProductName(){
        return self::$productName;
    }

    /* ls-diff start */
    protected static $static_keyhash_map = array(
        'js' => array(),
        'css' => array()
    );

    private static $staticLoadType = self::STATIC_NORMAL_LOAD_TYPE;
    private static $staticLoadTypeDefined = array(
        self::STATIC_NORMAL_LOAD_TYPE,
        self::STATIC_LS_LOAD_TYPE,
        self::STATIC_DEBUG_LOAD_TYPE
    );
    public static function setStaticLoadType($type){
        if(in_array(strtolower($type), self::$staticLoadTypeDefined)){
            self::$staticLoadType = $type;
        }
    }
    public static function getStaticLoadType(){
        return self::$staticLoadType;
    }
    protected static $cssLS = false;
    public static function setCssLS($css){
        if($css){
            self::$cssLS = true;
        }
    }

    //定义ls的回调函数名
    protected static $lsCallbackName = 'lsDiffCallback';
    
    /* ls-diff end */

    /* 调试方案 start */
    protected static $single_files_map = array(
        'js' => array(),
        'css' => array()
    );
    private static $renderEnv = self::RENDER_ENV_PRO;

    public static function setRenderEnv($env){
        if(strtolower($env) == self::RENDER_ENV_DEV){
            self::$renderEnv = self::RENDER_ENV_DEV;
        }
    }

    public static function getRenderEnv(){
        return self::$renderEnv;
    }
    /* 调试方案 end */

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


    private static function print_stack_trace(){
        $array =debug_backtrace();
      
        unset($array[0]);
        foreach($array as $row){
            $html .=$row['file'].':'.$row['line'].'行,调用方法:'.$row['function']."<p>";
        }
        return$html;
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
                    
                    /* ls-diff start */
                    if(isset($info['key']) && isset($info['hash'])){
                        self::$static_keyhash_map[$type][] = array(
                            "key" => $info['key'],
                            "hash" => $info['hash']
                        );
                    }

                    /* ls-diff end */
                    /* 调试功能 start 目前调试无法加载独立文件*/
                    self::$single_files_map[$type][] = $uri;
                    /* 调试功能 end */
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


    /**
     * 资源加载临时方案 ： 
     *   线上 ： 针对mox版本 采用修复后方案， 其他采用原方案
     *   线下 ： 全部采用新方案， 会导致老版本css加载顺序问题
     */
    public static function load ( $path, $caller_ns=null, $replace_static=false, $data=null ){
        if(self::getProductName() == 'mox' || self::getRenderEnv() == self::RENDER_ENV_DEV){
            $info = self::getInfo($path, $caller_ns, $ns, $map);
            $uri = $info['uri'];
            $html = self::render($uri, $data);
            $uri = self::import( $path, $caller_ns );  
        }else{
            $uri = self::import( $path, $caller_ns );
            $html = self::render($uri, $data);
            if ( $replace_static ){
                $html = ICT_Static::replace($html);
            }    
        }
        return $html;
    }

    public static function renderPage($page_path){
        $html = self::load($page_path);
        $html = ICT_Static::replace($html);
        echo $html;
    }

    /**********支持本地调试升级****start*****/

    private static $templateDir = null;

    public static function setTemplateDir($templateDir){
        self::$templateDir = $templateDir;
    }

    public static function getTemplateDir($templateDir){
        return self::$templateDir;
    }

    //线上版本需要去掉
    public static function resolve($subPath){
        $file = realpath(self::$templateDir . $subPath);
        return $file;
    }

    public static function renderTestPage($page_path, $data){

        self::setRenderEnv(self::RENDER_ENV_DEV);

        self::$vars = array_merge(self::$vars, $data);
        $info = self::getInfo($page_path, $caller_ns, $ns, $map);
        $uri = $info['uri'];
        $html = self::render($uri, $data);

        $uri = self::import( $page_path, $caller_ns );
        $html = ICT_Static::replace($html);
        echo $html;
    }

    /**********支持本地调试升级*****end****/


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
        //ls_load 资源为异步加载因此需要回调执行
        $staticLoadType = self::getStaticLoadType();
        if($staticLoadType == self::STATIC_LS_LOAD_TYPE){
            $scriptCode = '';
            $scriptReg = '/<script\s*[^>]*>|<\/script>/';
            if(isset(self::$_pool['script'])){
                $scriptInnerCode = preg_replace($scriptReg, '', self::$_pool['script']);    
                $scriptCode .= '<script type="text/javascript">';
                    $scriptCode .= 'function ' . self::$lsCallbackName . '(){'.  $scriptInnerCode . '}';
                $scriptCode .= '</script>';
            } 
            echo $scriptCode;
        }else{
            echo self::renderPool('script');
        }
        
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

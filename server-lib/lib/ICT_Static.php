<?php


class ICT_Static extends ICT_View {

    const STYLE_PLACEHOLDER  = '<!-- ICT_HEAD_CSS_PLACEHOLDER -->';
    const MAP_PLACEHOLDER = '<!-- ICT_MAP_JS_PLACEHOLDER -->';
    const SCRIPT_PLACEHOLDER = '<!-- ICT_BODY_JS_PLACEHOLDER -->';


    /**
     *
     */
    public static function reset(){
        self::$_maps = array();
        self::$_collection = array(
            'js' => array(),
            'css' => array()
        );
        /* ls-diff start */
        self::$static_keyhash_map = array(
            'js' => array(),
            'css' => array()  
        );
        /* ls-diff end */

        self::$single_files_map = array(
            'js' => array(),
            'css' => array()
        );
    }

    //映射js map，供require.async使用
    private static function map_js ( ){
        $map_js = array();

        foreach  ( self::$map as $key => $value ){
            $map_js[$key] = array();

            foreach  ( $value  as $k => $val ){
                if( $val['type'] &&  ($val['type'] === 'js' || $val['type'] === 'css')){
                    $map_js[$key][$k] = $val;
                }
            }
        }

        return json_encode($map_js);
    }

    public static function render($type, $reset = true){
        $html = '';
        //todo :async资源加载方案有问题需要更新
        if ($type === 'map'){
            $html = '<script type="text/javascript">';
            $html .= 'require.resourceMap('.self::map_js().');';
            $html .= '</script>';
        }
        
        if(!empty(self::$_collection[$type])){
            
            $staticLoadType = self::getStaticLoadType();
            $uris = self::$_collection[$type];

            switch ($staticLoadType) {
                //静态资源的LS加载方案, 拼接字符串代码太挫，需要重写
                case self::STATIC_LS_LOAD_TYPE:
                    if($type === 'js'){
                        $pkgs = array();
                        $html .= '<script type="text/javascript">';
                        $html .= 'F.load([';
                            $arrJsStatics = &self::$static_keyhash_map['js'];
                            $length = count($arrJsStatics);
                            for($staticIndex =0; $staticIndex<$length; $staticIndex++){
                                $pkgs[] = '{"id":"' . $arrJsStatics[$staticIndex]['key'] . '","hash":"' . $arrJsStatics[$staticIndex]['hash'] . '"}';
                            }
                            if(self::$cssLS){
                                $arrCssStatics = &self::$static_keyhash_map['css'];
                                $length = count($arrCssStatics);
                                for($staticIndex =0; $staticIndex<$length; $staticIndex++){
                                    $pkgs[] = '{"id":"' . $arrCssStatics[$staticIndex]['key'] . '","hash":"' . $arrCssStatics[$staticIndex]['hash'] . '"}';
                                }
                            }
                            $html .= join(",", $pkgs);
                        $html .= '], ' . self::$lsCallbackName .');';
                        $html .= '</script>';
                    } else if($type === 'css'){
                        if(!self::$cssLS){
                            $html = self::renderLinks($type, $uris);
                        }
                    }
                    break;
                //静态资源的debug加载方案
                case self::STATIC_DEBUG_LOAD_TYPE:
                    $singleUrls = self::$single_files_map[$type];
                    $html = self::renderLinks($type, $singleUrls);
                    break;
                //静态资源的常规加载方案
                case self::STATIC_NORMAL_LOAD_TYPE:
                default:
                    $html = self::renderLinks($type, $uris);
                    break;
            }

            if($reset){
                self::$_collection[$type] = array();
            }
        }
        return $html;
    }

    private static function renderLinks($type, $links){
        $lf = "\n";
        $html = "";
        if($type === 'js'){
            $html = '<script type="text/javascript" src="';
            $html .= implode('"></script>' . $lf . '<script type="text/javascript" src="', $links);
            $html .= '"></script>' . $lf;    
        } else if($type === 'css'){
            $html  = '<link rel="stylesheet" type="text/css" href="';
            $html .= implode('"/>' . $lf . '<link rel="stylesheet" type="text/css" href="', $links);
            $html .= '"/>' . $lf;   
        }
        return $html;
    }

    public static function replace($html, $reset = true){

        $pos = strpos($html, self::STYLE_PLACEHOLDER);
        if($pos !== false){
            var_dump(self::$_collection);
            $html = substr_replace($html, self::render('css'), $pos, strlen(self::STYLE_PLACEHOLDER));
        }
        $pos = strrpos($html, self::SCRIPT_PLACEHOLDER);
        if($pos !== false){
            $html = substr_replace($html, self::render('js'), $pos, strlen(self::SCRIPT_PLACEHOLDER));
        }

        $pos = strpos($html, self::MAP_PLACEHOLDER);
        if($pos !== false){
            $html = substr_replace($html, self::render('map'), $pos, strlen(self::MAP_PLACEHOLDER));
        }

        if($reset){
            self::reset();
        }
        return $html;
    }

}

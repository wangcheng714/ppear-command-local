<?php

/**
  PHP Fat-Free Framework

  The contents of this file are subject to the terms of the GNU General
  Public License Version 3.0. You may not use this file except in
  compliance with the license. Any of the license terms and conditions
  can be waived if you get permission from the copyright holder.

  Copyright (c) 2009-2012 F3::Factory
  Bong Cosca <bong.cosca@yahoo.com>

  @package Base
  @version 2.0.11
 * */
define('F3_BTIME', microtime(true));
defined('DEBUG') or define('DEBUG', 0);
define('LOG_ID', md5(uniqid(mt_rand(), true))); //请求id
if (PHP_SAPI != 'cli') {
    header('LOG_ID: ' . LOG_ID);
}
//! Base structure
class Base {

    //@{ Framework details
    const
        TEXT_AppName = 'Fat-Free Framework',
        TEXT_Version = '2.0.11',
        TEXT_AppURL = 'http://fatfree.sourceforge.net';
    //@}
    //@{ Locale-specific error/exception messages
    const
        TEXT_Illegal = '%s is not a valid framework variable name',
        TEXT_Config = 'The configuration file %s was not found',
        TEXT_Section = '%s is not a valid section',
        TEXT_MSet = 'Invalid multi-variable assignment',
        TEXT_NotArray = '%s is not an array',
        TEXT_PHPExt = 'PHP extension %s is not enabled',
        TEXT_Apache = 'Apache mod_rewrite module is not enabled',
        TEXT_Object = '%s cannot be used in object context',
        TEXT_Class = 'Undefined class %s',
        TEXT_Method = 'Undefined method %s',
        TEXT_NotFound = 'The URL %s was not found',
        TEXT_NotAllowed = '%s request is not allowed for the URL %s',
        TEXT_NoRoutes = 'No routes specified',
        TEXT_HTTP = 'HTTP status code %s is invalid',
        TEXT_Render = 'Unable to render %s - file does not exist',
        TEXT_Form = 'The input handler for %s is invalid',
        TEXT_Static = '%s must be a static method',
        TEXT_Fatal = 'Fatal error: %s',
        TEXT_Write = '%s must have write permission on %s',
        TEXT_Tags = 'PHP short tags are not supported by this server';
    //@}
    //@{ HTTP status codes (RFC 2616)
    const
        HTTP_100 = 'Continue',
        HTTP_101 = 'Switching Protocols',
        HTTP_200 = 'OK',
        HTTP_201 = 'Created',
        HTTP_202 = 'Accepted',
        HTTP_203 = 'Non-Authorative Information',
        HTTP_204 = 'No Content',
        HTTP_205 = 'Reset Content',
        HTTP_206 = 'Partial Content',
        HTTP_300 = 'Multiple Choices',
        HTTP_301 = 'Moved Permanently',
        HTTP_302 = 'Found',
        HTTP_303 = 'See Other',
        HTTP_304 = 'Not Modified',
        HTTP_305 = 'Use Proxy',
        HTTP_307 = 'Temporary Redirect',
        HTTP_400 = 'Bad Request',
        HTTP_401 = 'Unauthorized',
        HTTP_402 = 'Payment Required',
        HTTP_403 = 'Forbidden',
        HTTP_404 = 'Not Found',
        HTTP_405 = 'Method Not Allowed',
        HTTP_406 = 'Not Acceptable',
        HTTP_407 = 'Proxy Authentication Required',
        HTTP_408 = 'Request Timeout',
        HTTP_409 = 'Conflict',
        HTTP_410 = 'Gone',
        HTTP_411 = 'Length Required',
        HTTP_412 = 'Precondition Failed',
        HTTP_413 = 'Request Entity Too Large',
        HTTP_414 = 'Request-URI Too Long',
        HTTP_415 = 'Unsupported Media Type',
        HTTP_416 = 'Requested Range Not Satisfiable',
        HTTP_417 = 'Expectation Failed',
        HTTP_500 = 'Internal Server Error',
        HTTP_501 = 'Not Implemented',
        HTTP_502 = 'Bad Gateway',
        HTTP_503 = 'Service Unavailable',
        HTTP_504 = 'Gateway Timeout',
        HTTP_505 = 'HTTP Version Not Supported';
    //@}
    //@{ HTTP headers (RFC 2616)
    const
        HTTP_AcceptEnc = 'Accept-Encoding',
        HTTP_Agent = 'User-Agent',
        HTTP_Allow = 'Allow',
        HTTP_Cache = 'Cache-Control',
        HTTP_Connect = 'Connection',
        HTTP_Content = 'Content-Type',
        HTTP_Disposition = 'Content-Disposition',
        HTTP_Encoding = 'Content-Encoding',
        HTTP_Expires = 'Expires',
        HTTP_Host = 'Host',
        HTTP_IfMod = 'If-Modified-Since',
        HTTP_Keep = 'Keep-Alive',
        HTTP_LastMod = 'Last-Modified',
        HTTP_Length = 'Content-Length',
        HTTP_Location = 'Location',
        HTTP_Partial = 'Accept-Ranges',
        HTTP_Powered = 'X-Powered-By',
        HTTP_Pragma = 'Pragma',
        HTTP_Referer = 'Referer',
        HTTP_Transfer = 'Content-Transfer-Encoding',
        HTTP_WebAuth = 'WWW-Authenticate';
    //@}

    const
    //! Framework-mapped PHP globals
    //PHP_Globals='GET|POST|COOKIE|REQUEST|SESSION|FILES|SERVER|ENV',
        PHP_Globals = 'GET|POST|REQUEST|FILES|SERVER|ENV',
        //! HTTP methods for RESTful interface
        HTTP_Methods = 'GET|HEAD|POST|PUT|DELETE|OPTIONS';

    //@{ Global variables and references to constants
    protected static
        $vars,
        $null = null,
        $true = true,
        $false = false;
    //@}

    private static
    //! Read-only framework variables
        $readonly = 'BASE|PROTOCOL|ROUTES|STATS|VERSION';

    /**
      Convert Windows double-backslashes to slashes; Also for
      referencing namespaced classes in subdirectories
      @return string
      @param $str string
      @public
     * */
    static function fixslashes($str) {
        return $str ? strtr($str, '\\', '/') : $str;
    }

    /**
      Convert PHP expression/value to compressed exportable string
      @return string
      @param $arg mixed
      @public
     * */
    static function stringify($arg) {
        switch (gettype($arg)) {
            case 'object':
                return method_exists($arg, '__tostring') ? (string) stripslashes($arg) : get_class($arg) . '::__set_state()';
            case 'array':
                $str = '';
                foreach ($arg as $key => $val) {
                    $str.=($str ? ',' : '') . self::stringify($key) . '=>' . self::stringify($val);
                }
                return 'array(' . $str . ')';
            default:
                return var_export($arg, true);
        }
    }

    /**
      Flatten array values and return as CSV string
      @return string
      @param $args mixed
      @public
     * */
    static function csv($args) {
        return implode(',', array_map('stripcslashes', array_map('self::stringify', $args)));
    }

    /**
      Split pipe-, semi-colon, comma-separated string
      @return array
      @param $str string
      @public
     * */
    static function split($str) {
        return array_map('trim', preg_split('/[|;,]/', $str, 0, PREG_SPLIT_NO_EMPTY));
    }

    /**
      Generate Base36/CRC32 hash code
      @return string
      @param $str string
      @public
     * */
    static function hash($str) {
        return str_pad(base_convert(sprintf('%u', crc32($str)), 10, 36), 7, '0', STR_PAD_LEFT);
    }

    /**
      Generate md5 hash code
      @return string
      @param $str path
      @public
     * */
    static function md5($str) {
        return substr(hash_file('md5', $str), 0, 8);
    }

    /**
      Convert hexadecimal to binary-packed data
      @return string
      @param $hex string
      @public
     * */
    static function hexbin($hex) {
        return pack('H*', $hex);
    }

    /**
      Convert binary-packed data to hexadecimal
      @return string
      @param $bin string
      @public
     * */
    static function binhex($bin) {
        return implode('', unpack('H*', $bin));
    }

    /**
      Returns -1 if the specified number is negative, 0 if zero, or 1 if
      the number is positive
      @return int
      @param $num mixed
      @public
     * */
    static function sign($num) {
        return $num ? $num / abs($num) : 0;
    }

    /**
      Convert engineering-notated string to bytes
      @return int
      @param $str string
      @public
     * */
    static function bytes($str) {
        $greek = 'KMGT';
        $exp = strpbrk($str, $greek);
        return pow(1024, strpos($greek, $exp) + 1) * (int) $str;
    }

    /**
      Convert from JS dot notation to PHP array notation
      @return string
      @param $key string
      @public
     * */
    static function remix($key) {
        $out = '';
        $obj = false;
        foreach (preg_split('/\[\s*[\'"]?|[\'"]?\s*\]|\.|(->)/', $key, null, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE) as $fix) {
            if ($out) {
                if ($fix == '->') {
                    $obj = true;
                    continue;
                } else if ($obj) {
                    $obj = false;
                    $fix = '->' . $fix;
                } else {
                    $fix = '[' . var_export($fix, true) . ']';
                }
            }
            $out.=$fix;
        }
        return $out;
    }

    /**
      Return true if specified string is a valid framework variable name
      @return bool
      @param $key string
      @public
     * */
    static function valid($key) {
        if (preg_match('/^(\w+(?:\[[^\]]+\]|\.\w+|\s*->\s*\w+)*)$/', $key)) {
            return true;
        }
        // Invalid variable name
        trigger_error(sprintf(self::TEXT_Illegal, var_export($key, true)));
        return false;
    }

    /**
      Get framework variable reference/contents
      @return mixed
      @param $key string
      @param $set bool
      @public
     * */
    static function &ref($key, $set = true) {
        // Traverse array
        $matches = preg_split('/\[\s*[\'"]?|[\'"]?\s*\]|\.|(->)/', self::remix($key), null, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        // Referencing a SESSION variable element auto-starts a session
        if ($matches[0] == 'SESSION' && !session_id()) {
            // Use cookie jar setup
            call_user_func_array('session_set_cookie_params', self::$vars['JAR']);
            session_start();
            // Sync framework and PHP global
            self::$vars['SESSION'] = &$_SESSION;
        }
        // Read-only framework variable?
        if ($set && !preg_match('/^(' . self::$readonly . ')\b/', $matches[0])) {
            $var = &self::$vars;
        } else {
            $var = self::$vars;
        }
        $obj = false;
        foreach ($matches as $match) {
            if ($match == '->') {
                $obj = true;
            } else {
                if (preg_match('/@(\w+)/', $match, $token)) {
                // Token found
                    $match = &self::ref($token[1]);
                }
                if ($set) {
                    // Create property/array element if not found
                    if ($obj) {
                        if (!is_object($var)) {
                            $var = new stdClass;
                        }
                        if (!isset($var->$match)) {
                            $var->$match = null;
                        }
                        $var = &$var->$match;
                        $obj = false;
                    } else {
                        $var = &$var[$match];
                    }
                } else if ($obj && (isset($var->$match) || method_exists($var, '__get'))) {
                    // Object property found
                    $var = $var->$match;
                    $obj = false;
                } else if (is_array($var) && isset($var[$match])) {
                    // Array element found
                    $var = $var[$match];
                } else {
                    // Property/array element doesn't exist
                    return self::$null;
                }
            }
        }
        if ($set && count($matches) > 1 && preg_match('/GET|POST|COOKIE/', $matches[0], $php)) {
            // Sync with REQUEST
            $req = &self::ref(preg_replace('/^' . $php[0] . '\b/', 'REQUEST', $key));
            $req = $var;
        }
        return $var;
    }

    /**
      Copy contents of framework variable to another
      @param $src string
      @param $dst string
      @public
     * */
    static function copy($src, $dst) {
        $ref = &self::ref($dst);
        $ref = self::ref($src);
    }

    /**
      Concatenate string to framework string variable
      @param $var string
      @param $val string
      @public
     * */
    static function concat($var, $val) {
        $ref = &self::ref($var);
        $ref.= $val;
    }

    /**
      Format framework string variable
      @return string
      @public
     * */
    static function sprintf() {
        return call_user_func_array('sprintf', array_map('self::resolve', func_get_args()));
    }

    /**
      Add keyed element to the end of framework array variable
      @param $var string
      @param $key string
      @param $val mixed
      @public
     * */
    static function append($var, $key, $val) {
        $ref = &self::ref($var);
        $ref[self::resolve($key)] = $val;
    }

    /**
      Swap keys and values of framework array variable
      @param $var string
      @public
     * */
    static function flip($var) {
        $ref = &self::ref($var);
        $ref = array_combine(array_values($ref), array_keys($ref));
    }

    /**
      Merge one or more framework array variables
      @return array
      @public
     * */
    static function merge() {
        $args = func_get_args();
        foreach ($args as &$arg) {
            if (is_string($arg)) {
                $arg = self::ref($arg);
            }
            if (!is_array($arg)) {
                trigger_error(sprintf(self::TEXT_NotArray, self::stringify($arg)));
            }
        }
        return call_user_func_array('array_merge', $args);
    }

    /**
      Add element to the end of framework array variable
      @param $var string
      @param $val mixed
      @public
     * */
    static function push($var, $val) {
        $ref = &self::ref($var);
        if (!is_array($ref)) {
            $ref = array();
        }
        array_push($ref, is_array($val) ? array_map('self::resolve', $val) : (is_string($val) ? self::resolve($val) : $val));
    }

    /**
      Remove last element of framework array variable and
      return the element
      @return mixed
      @param $var string
      @public
     * */
    static function pop($var) {
        $ref = &self::ref($var);
        if (is_array($ref)) {
            return array_pop($ref);
        }
        trigger_error(sprintf(self::TEXT_NotArray, $var));
        return false;
    }

    /**
      Add element to the beginning of framework array variable
      @param $var string
      @param $val mixed
      @public
     * */
    static function unshift($var, $val) {
        $ref = &self::ref($var);
        if (!is_array($ref)) {
            $ref = array();
        }
        array_unshift($ref, is_array($val) ? array_map('self::resolve', $val) : (is_string($val) ? self::resolve($val) : $val));
    }

    /**
      Remove first element of framework array variable and
      return the element
      @return mixed
      @param $var string
      @public
     * */
    static function shift($var) {
        $ref = &self::ref($var);
        if (is_array($ref)) {
            return array_shift($ref);
        }
        trigger_error(sprintf(self::TEXT_NotArray, $var));
        return false;
    }

    /**
      Execute callback as a mutex operation
      @return mixed
      @public
     * */
    static function mutex() {
        $args = func_get_args();
        $func = array_shift($args);
        $handles = array();
        foreach ($args as $file) {
            $lock = $file . '.lock';
            while (true) {
                usleep(mt_rand(0, 100));
                if (is_resource($handle = @fopen($lock, 'x'))) {
                    $handles[$lock] = $handle;
                    break;
                }
                if (is_file($lock) && filemtime($lock) + self::$vars['MUTEX'] < time()) {
                    unlink($lock);
                }
            }
        }
        $out = $func();
        foreach ($handles as $lock => $handle) {
            fclose($handle);
            unlink($lock);
        }
        return $out;
    }

    /**
      Lock-aware file reader
      @param $file string
      @public
     * */
    static function getfile($file) {
        $out = false;
        if (!function_exists('flock')) {
            $out = self::mutex(function() use($file) {return @file_get_contents($file);}, $file);
        } else if ($handle = @fopen($file, 'r')) {
            flock($handle, LOCK_EX);
            $size = filesize($file);
            $out = $size ? fread($handle, $size) : $out;
            flock($handle, LOCK_UN);
            fclose($handle);
        }
        return $out;
    }

    /**
      Lock-aware file writer
      @param $file string
      @param $data string
      @public
     * */
    static function putfile($file, $data) {
        if (!function_exists('flock')) {
            $out = self::mutex(function() use($file, $data) {return file_put_contents($file, $data, LOCK_EX);}, $file);
        } else {
            $out = file_put_contents($file, $data, LOCK_EX);
        }
        return $out;
    }

    /**
      Evaluate template expressions in string
      @return string
      @param $val mixed
      @public
     * */
    static function resolve($val) {
        // Analyze string for correct framework expression syntax
        $self = __CLASS__;
        $str  = preg_replace_callback('/{{(.+?)}}/i', // Expression
            function($expr) use($self) { // Evaluate expression
                $out = preg_replace_callback('/(?<!@)\b(\w+)\s*\(([^\)]*)\)/', // Function
                    function($func) use($self) {
                        return is_callable($ref = $self::ref($func[1], false)) ?
                            // Variable holds an anonymous function
                            call_user_func_array($ref, str_getcsv($func[2])) :
                            // Check if empty array
                            ($func[1] . $func[2] == 'array' ? 'null' : ($func[1] == 'array' ? '\'Array\'' : $func[0]));
                    },
                    preg_replace_callback('/(?<!\w)@(\w+(?:\[[^\]]+\]|\.\w+)*' . '(?:\s*->\s*\w+)?)\s*(\(([^\)]*)\))?(?:\\\(.+))?/', // Framework variable
                        function($var) use($self) {
                            // Retrieve variable contents
                            $val = $self::ref($var[1], false);
                            if (isset($var[2]) && is_callable($val)) {
                                // Anonymous function
                                $val = call_user_func_array($val, str_getcsv($var[3]));
                            }
                            if (isset($var[4]) && class_exists('ICU', false)) {
                                // ICU-formatted string
                                $val = call_user_func_array('ICU::format', array($val, str_getcsv($var[4])));
                            }
                            return $self::stringify($val);
                        }, $expr[1]
                    )
                );
                return (!preg_match('/@|\bnew\s+/i', $out) && ($eval = eval('return (string)' . $out . ';')) !== false ? $eval : $out);
            }, $val
        );
        return $str;
    }

    /**
      Sniff headers for real IP address
      @return string
      @public
     * */
    static function realip() {
        $cmd = '/sbin/ifconfig | grep "inet addr" | grep -v "127.0.0.1" | sed -n \'s/^ *.*addr:\\([0-9.]\\{7,\\}\\) .*$/\\1/p\'';
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            // Behind proxy
            return $_SERVER['HTTP_CLIENT_IP'];
        } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Use first IP address in list
            list($ip) = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return $ip;
        } else if (isset($_SERVER['REMOTE_ADDR']) && '127.0.0.1' != $_SERVER['REMOTE_ADDR']) {
            return $_SERVER['REMOTE_ADDR'];
        } else if (@exec($cmd, $m) && $m && is_array($m)) {
            return $m[0];
        } else {
            return '127.0.0.1';
        }
    }

    /**
      Return true if IP address is local or within a private IPv4 range
      @return bool
      @param $addr string
      @public
     * */
    static function privateip($addr = null) {
        if (!$addr) {
            $addr = self::realip();
        }
        return preg_match('/^127\.0\.0\.\d{1,3}$/', $addr) || !filter_var($addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE);
    }

    static function params($params = array()) {
        if (is_array($params)) {
            foreach ($params as $key => $param) {
                switch ($key) {
                    case 'LANGUAGE':
                    case 'LOCALES':
                        self::$vars[$key] = $param;
                        class_exists('ICU') ? ICU::load() : null;
                        break;
                    case 'CACHE':
                        if (is_array($param)) {
                            foreach ($param as $k => $v) {
                                Cache::set(strval($k), $v);
                                break;
                            }
                        }
                        break;
                    case 'COOKIE':
                        if (!headers_sent()) {
                            $cookies = self::$vars['JAR'];
                            foreach ($param as $k => $v) {
                                array_unshift($cookies, $k, $v);
                            }
                            call_user_func_array('setcookie', $cookies);
                        }
                        break;
                    case 'SESSION':
                        if (!session_id()) {
                            session_start();
                            self::$vars['SESSION'] = &$_SESSION;
                        }
                        if (is_array($param)) {
                            foreach ($param as $k =>$v) {
                                $_SESSION[$k] = $v;
                                break;
                            }
                        }
                        break;
                    default:
                        if (!isset(self::$vars[$key]) || !is_array(self::$vars[$key]) || !is_array($param)) {
                            self::$vars[$key] = $param;
                        } else {
                            array_merge_recursive(self::$vars[$key], $param);
                        }
                        break;
                }
            }
        }
    }

    /**
      Clean and repair HTML
      @return string
      @param $html string
      @public
     * */
    static function tidy($html) {
        if (!extension_loaded('tidy')) {
            return $html;
        }
        $tidy = new Tidy;
        $tidy->parseString($html, self::$vars['TIDY'], str_replace('-', '', self::$vars['ENCODING']));
        $tidy->cleanRepair();
        return (string) $tidy;
    }

    /**
      Create folder; Trigger error and return false if script has no
      permission to create folder in the specified path
      @param $name string
      @param $perm int
      @public
     * */
    static function mkdir($name, $perm = 0775, $recursive = true) {
        $parent = dirname($name);
        if (!@is_writable($parent) && !chmod($parent, $perm)) {
            $uid = posix_getpwuid(posix_geteuid());
            trigger_error(sprintf(self::TEXT_Write, $uid['name'], realpath(dirname($name))));
            return false;
        }
        // Create the folder
        if (!file_exists($name)) {
            mkdir($name, $perm, $recursive);
        }
    }

    /**
      Intercept calls to undefined methods
      @param $func string
      @param $args array
      @public
     * */
    function __call($func, array $args) {
        trigger_error(sprintf(self::TEXT_Method, get_called_class() . '->' . $func . '(' . self::csv($args) . ')'));
    }

    /**
      Intercept calls to undefined static methods
      @param $func string
      @param $args array
      @public
     * */
    static function __callStatic($func, array $args) {
        trigger_error(sprintf(self::TEXT_Method, get_called_class() . '::' . $func . '(' . self::csv($args) . ')'));
    }

    /**
      Return instance of child class
      @public
     * */
    static function instance() {
        return eval('return new ' . get_called_class() . '(' . self::csv(func_get_args()) . ');');
    }

    /**
      Class constructor
      @public
     * */
    function __construct() {
        // Prohibit use of class as an object
        trigger_error(sprintf(self::TEXT_Object, get_called_class()));
    }

}

//! Main framework code
class F3 extends Base {

    /**
     * 对可能的xss字符进行转义编码
     * @param string|array $val
     * @return 编码后的值
     */
    static function xssEncode($val) {
        if (is_array($val)) {
            foreach ($val as $k => $v) {
                if ($k == 'carids' || (is_string($k) && strncmp($k, 'rc_', 3) === 0)) {
                    $val[$k] = $v;
                } else {
                    $val[$k] = self::xssEncode($v);
                }
            }
            return $val;
        }
        if (is_string($val)) {
            $val = preg_replace('/(UPDATE\s|SELECT\s|\sUNION|DELETE\s|DROP\s|REPLACE\s)/i', '', $val);
            return str_replace(array('&', '>', '<', '"', "'", '{'), array('&amp;', '&gt;', '&lt;', '&quot;', '&#39;', '&#123;'), $val);
        }
        return $val;
    }

    /**
     * 对转义编码过的xss字符解码
     * @param string|array $val
     * @return 解码后的值
     */
    static function xssDecode($val) {
        if (is_array($val)) {
            foreach ($val as $k => $v) {
                if ($k != 'carids' || !(is_string($k) && strncmp($k, 'rc_', 3) === 0)) {
                    $val[$k] = self::xssDecode($v);
                } else {
                    $val[$k] = $v;
                }
            }
            return $val;
        }
        if (is_string($val)) {
            return str_replace(array('&gt;', '&lt;', '&quot;', '&#39;', '&#123;', '&amp;'), array('>', '<', '"', "'", '{', '&'), $val);
        }
        return $val;
    }

    /**
     * 新版本的set函数，只支持x.y.z的path设置方式
     * @param string $key
     * @param mixed $val
     * @param boolean $persist
     */
    static function tset($key, $val, $persist = false) {
        $var = &self::$vars;
        do {
            $pos = strpos($key, '.');
            if ($pos !== false) {
                $firstkey = substr($key, 0, $pos);
                $key = substr($key, $pos + 1);
                if ($firstkey == "CACHE") {
                    if ($val) {
                        Cache::set($key, $val);
                    }
                    break;
                } else if ($firstkey == "COOKIE") {
                    if (!headers_sent()) {
                        $cookies = self::$vars['JAR'];
                        array_unshift($cookies, $key, $val);
                        call_user_func_array('setcookie', $cookies);
                    }
                    break;
                } else if ($firstkey == "SESSION") {
                    if (!session_id()) {
                        session_start();
                        self::$vars['SESSION'] = &$_SESSION;
                    }
                    $_SESSION[$firstkey] = $val;
                    break;
                }
                if (!isset($var[$firstkey])) {
                    $var[$firstkey] = array();
                }
                $var = &$var[$firstkey];
            } else {
                $var[$key] = $val;
                if (($key == "LANGUAGE" || $key == 'LOCALES') && class_exists('ICU')) {
                    ICU::load();
                }
                break;
            }
        } while (true);
    }

    static function tget($key) {
        if (isset(self::$vars[$key])) {
            return self::$vars[$key];
        }
        if (strpos($key, '[') !== false) {
            $key = str_replace('[', '.', $key);
            $key = str_replace(']', '', $key);
        }
        $var = &self::$vars;
        $obj = false;
        do {
            $pos = strpos($key, '.');
            if ($pos === false) {
                $pos = strpos($key, '->');
                if ($pos != false) {
                    $obj = true;
                }
            }
            if ($pos !== false) {
                $firstkey = substr($key, 0, $pos);
                $key = substr($key, $pos + ($obj ? 2 : 1));
                if ($firstkey == "CACHE") {
                    return Cache::get($key);
                } else if ($firstkey == "COOKIE") {
                    if (isset($_COOKIE[$key])) {
                        return $_COOKIE[$key];
                    }
                    return null;
                } else if ($firstkey == "SESSION") {
                    if (isset($_SESSION[$key])) {
                        return $_SESSION[$key];
                    }
                    return null;
                }
                if (isset($var[$firstkey])) {
                    $var = &$var[$firstkey];
                } else {
                    return null;
                }
            } else {
                if ($key == "COOKIE") {
                    return $_COOKIE;
                }
                if ($key == "SESSION") {
                    return $_SESSION;
                }
                if (is_object($var)) {
                    return $var->$key;
                }
                if (isset($var[$key])) {
                    return $var[$key];
                }
                return null;
            }
        } while (true);
    }

    /**
      Bind value to framework variable
      @param $key string
      @param $val mixed
      @param $persist bool
      @param $resolve bool
      @public
     * */
    static function set($key, $val, $persist = false, $resolve = true) {
        return self::tset($key, $val);
    }

    /**
      Retrieve value of framework variable and apply locale rules
      @return mixed
      @param $key string
      @param $args mixed
      @public
     * */
    static function get($key, $args = null) {
        return self::tget($key);
    }

    /**
      Unset framework variable
      @param $key string
      @public
     * */
    static function clear($key) {
        if (preg_match('/{{.+}}/', $key)) {
            // Variable variable
            $key = self::resolve($key);
        }
        if (!self::valid($key)) {
            return;
        }
        if (preg_match('/COOKIE/', $key) && !headers_sent()) {
            $val = $_COOKIE;
            $matches = preg_split('/\[\s*[\'"]?|[\'"]?\s*\]|\./', self::remix($key), null, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
            array_shift($matches);
            if ($matches) {
                // Expire specific cookie
                $var = '';
                foreach ($matches as $match) {
                    if (!$var) {
                        $var = $match;
                    } else {
                        $var.='[\'' . $match . '\']';
                    }
                }
                $val = array($var, false);
            }
            if (is_array($val)) {
            // Expire all cookies
                foreach (array_keys($val) as $var) {
                    $func = self::$vars['JAR'];
                    $func['expire'] = strtotime('-1 year');
                    array_unshift($func, $var, false);
                    call_user_func_array('setcookie', $func);
                }
            }
            return;
        }
        // Clearing SESSION array ends the current session
        if ($key == 'SESSION') {
            if (!session_id()) {
                call_user_func_array('session_set_cookie_params', self::$vars['JAR']);
                session_start();
            }
            // End the session
            session_unset();
            session_destroy();
        }
        preg_match('/^(' . self::PHP_Globals . ')(.*)$/', $key, $match);
        if (isset($match[1])) {
            $name = self::remix($key, false);
            eval($match[2] ? 'unset($_' . $name . ');' : '$_' . $name . '=null;');
        }
        $name = preg_replace('/^(\w+)/', '[\'\1\']', self::remix($key));
        // Assign null to framework variables; do not unset
        eval(ctype_upper(preg_replace('/^\w+/', '\0', $key)) ? 'self::$vars' . $name . '=null;' : 'unset(self::$vars' . $name . ');');
        // Remove from cache
        $hash = 'var.' . self::hash(self::remix($key));
        if (Cache::cached($hash)) {
            Cache::clear($hash);
        }
    }

    /**
      Return true if framework variable has been assigned a value
      @return bool
      @param $key string
      @public
     * */
    static function exists($key) {
        if (preg_match('/{{.+}}/', $key)) {
            // Variable variable
            $key = self::resolve($key);
        }
        if (!self::valid($key)) {
            return false;
        }
        $id = session_id();
        $var = &self::ref($key, false);
        if (!$id && session_id()) {
            // End the session
            session_unset();
            session_destroy();
        }
        return isset($var);
    }

    /**
      Multi-variable assignment using associative array
      @param $arg array
      @param $pfx string
      @public
     * */
    static function mset($arg, $pfx = '') {
        if (!is_array($arg)) {
            // Invalid argument
            trigger_error(self::TEXT_MSet);
        } else {
            // Bind key-value pairs
            foreach ($arg as $key => $val) {
                self::set($pfx . $key, $val);
            }
        }
    }

    /**
      Determine if framework variable has been cached
      @return mixed
      @param $key string
      @public
     * */
    static function cached($key) {
        if (preg_match('/{{.+}}/', $key)) {
            // Variable variable
            $key = self::resolve($key);
        }
        return self::valid($key) ? Cache::cached('var.' . self::hash(self::remix($key))) : false;
    }

    /**
      Configure framework according to INI-style file settings;
      Cache auto-generated PHP code to speed up execution
      @param $file string
      @public
     * */
    static function config($file) {
        // Generate hash code for config file
        $hash = 'php.' . self::hash($file);
        $cached = Cache::cached($hash);
        if ($cached && filemtime($file) < $cached) {
            // Retrieve from cache
            $save = Cache::get($hash);
        } else {
            if (!is_file($file)) {
                // Configuration file not found
                trigger_error(sprintf(self::TEXT_Config, $file));
                return;
            }
            // Load the .ini file
            $cfg = array();
            $sec = '';
            if ($ini = file($file)) {
                foreach ($ini as $line) {
                    preg_match('/^\s*(?:(;)|\[(.+)\]|(.+?)\s*=\s*(.+))/', $line, $parts);
                    if (isset($parts[1]) && $parts[1]) {
                        // Comment
                        continue;
                    } else if (isset($parts[2]) && $parts[2]) {
                        // Section
                        $sec = strtolower($parts[2]);
                    } else if (isset($parts[3]) && $parts[3]) {
                        // Key-value pair
                        $csv = array_map(
                            function($val) {
                                $val = trim($val);
                                return is_numeric($val) || preg_match('/^\w+$/i', $val) && defined($val) ? eval('return ' . $val . ';') : $val;
                            }, str_getcsv($parts[4])
                        );
                        $cfg[$sec = $sec? : 'globals'][$parts[3]] = count($csv) > 1 ? $csv : $csv[0];
                    }
                }
            }
            $plan = array('globals' => 'set', 'maps' => 'map', 'routes' => 'route');
            ob_start();
            foreach ($cfg as $sec => $pairs) {
                if (isset($plan[$sec])) {
                    foreach ($pairs as $key => $val) {
                        echo 'self::' . $plan[$sec] . '(' . self::stringify($key) . ',' . (is_array($val) && $sec != 'globals' ? self::csv($val) : self::stringify($val)) . ');' . "\n";
                    }
                }
            }
            $save = ob_get_clean();
            // Compress and save to cache
            Cache::set($hash, $save);
        }
        // Execute cached PHP code
        eval($save);
        if (!is_null(self::$vars['ERROR'])) {
            // Remove from cache
            Cache::clear($hash);
        }
    }

    /**
      Convert special characters to HTML entities using globally-
      defined character set
      @return string
      @param $str string
      @param $all bool
      @public
     * */
    static function htmlencode($str, $all = false) {
        return call_user_func($all ? 'htmlentities' : 'htmlspecialchars', $str, ENT_COMPAT, self::$vars['ENCODING'], true);
    }

    /**
      Convert HTML entities back to their equivalent characters
      @return string
      @param $str string
      @param $all bool
      @public
     * */
    static function htmldecode($str, $all = false) {
        return $all ? html_entity_decode($str, ENT_COMPAT, self::$vars['ENCODING']) : htmlspecialchars_decode($str, ENT_COMPAT);
    }

    /**
      Send HTTP status header; Return text equivalent of status code
      @return mixed
      @param $code int
      @public
     * */
    static function status($code) {
        if (!defined('self::HTTP_' . $code)) {
            // Invalid status code
            trigger_error(sprintf(self::TEXT_HTTP, $code));
            return false;
        }
        // Get description
        $response = constant('self::HTTP_' . $code);
        // Send raw HTTP header
        if (PHP_SAPI != 'cli' && !headers_sent()) {
            header($_SERVER['SERVER_PROTOCOL'] . ' ' . $code . ' ' . $response);
        }
        return $response;
    }

    /**
      Retrieve HTTP headers
      @return array
      @public
     * */
    static function headers() {
        if (PHP_SAPI != 'cli') {
            $req = array();
            foreach ($_SERVER as $key => $val) {
                if (substr($key, 0, 5) == 'HTTP_') {
                    $req[strtr(ucwords(strtolower(strtr(substr($key, 5), '_', ' '))), ' ', '-')] = $val;
                }
            }
            return $req;
        }
        return array();
    }

    /**
      Send HTTP header with expiration date (seconds from current time)
      @param $secs int
      @public
     * */
    static function expire($secs = 0) {
        if (PHP_SAPI != 'cli' && !headers_sent()) {
            $time = time();
            $req = self::headers();
            if (isset($req[self::HTTP_IfMod]) && strtotime($req[self::HTTP_IfMod]) + $secs > $time) {
                self::status(304);
                die;
            }
            /* header(self::HTTP_Powered.': '.self::TEXT_AppName.' '. '('.self::TEXT_AppURL.')'); */
            if ($secs) {
                header_remove(self::HTTP_Pragma);
                header(self::HTTP_Expires . ': ' . gmdate('r', $time + $secs));
                header(self::HTTP_Cache . ': max-age=' . $secs);
                header(self::HTTP_LastMod . ': ' . gmdate('r'));
            } else {
                header(self::HTTP_Pragma . ': no-cache');
                header(self::HTTP_Cache . ': no-cache, must-revalidate');
            }
        }
    }

    /**
      Reroute to specified URI
      @param $uri string
      @public
     * */
    static function reroute($uri) {
        $uri = self::resolve($uri);
        $uri = self::encode_uri_id($uri);
        if (PHP_SAPI != 'cli' && !headers_sent()) {
            // HTTP redirect
            self::status($_SERVER['REQUEST_METHOD'] == 'GET' ? 301 : 303);
            if (session_id()) {
                session_commit();
            }
            header(self::HTTP_Location . ': ' . (preg_match('/^https?:\/\//', $uri) ? $uri : (self::$vars['BASE'] . $uri)));
            die;
        }
        self::mock('GET ' . $uri);
        self::run();
    }

    static function encode_uri_id($uri) {
        $uri = preg_replace_callback('/\/(\d+)/', function($match) {return str_replace($match[1], Util::xcryptEncrypt($match[1]), $match[0]);}, $uri);
        return $uri;
    }

    /**
      Assign handler to route pattern
      @param $pattern string
      @param $funcs mixed
      @param $ttl int
      @param $throttle int
      @param $hotlink bool
      @public
     * */
    static function route($pattern, $funcs, $ttl = 0, $throttle = 0, $hotlink = true) {
        list($methods, $uri) = preg_split('/\s+/', $pattern, 2, PREG_SPLIT_NO_EMPTY);
        foreach (self::split($methods) as $method) {
            // Use pattern and HTTP methods as route indexes
            // Save handler, cache timeout and hotlink permission
            self::$vars['ROUTES'][$uri][strtoupper($method)] = array($funcs, $ttl, $throttle, $hotlink);
        }
    }

    /**
     * 设置路由
     */
    public static function setRoute(array $route) {
        self::$vars['ROUTES'] = $route;
    }

    /**
      Provide REST interface by mapping URL to object/class
      @param $url string
      @param $class mixed
      @param $ttl int
      @param $throttle int
      @param $hotlink bool
      @param $prefix string
      @public
     * */
    static function map($url, $class, $ttl = 0, $throttle = 0, $hotlink = true, $prefix = '') {
        foreach (explode('|', self::HTTP_Methods) as $method) {
            $func = $prefix . $method;
            if (method_exists($class, $func)) {
                $ref = new ReflectionMethod($class, $func);
                self::route($method . ' ' . $url, $ref->isStatic() ? array($class, $func) : array(new $class, $func), $ttl, $throttle, $hotlink);
                unset($ref);
            }
        }
    }

    /**
      Call route handler
      @return mixed
      @param $funcs string
      @param $listen bool
      @public
     * */
    static function call($funcs, $listen = false) {
        $classes = array();
        $funcs = is_string($funcs) ? self::split($funcs) : array($funcs);
        foreach ($funcs as $func) {
            if (is_string($func)) {
                $func = self::resolve($func);
                if (preg_match('/(.+)\s*(->|::)\s*(.+)/s', $func, $match)) {
                    if (!class_exists($match[1]) || !method_exists($match[1], '__call') && !method_exists($match[1], $match[3])) {
                        self::error(404);
                        return false;
                    }
                    $func = array($match[2] == '->' ? new $match[1] : $match[1], $match[3]);
                } else if (!function_exists($func)) {
                    if (preg_match('/\.php$/i', $func)) {
                        foreach (self::split(self::$vars['IMPORTS']) as $path) {
                            if (is_file($file = $path . $func)) {
                                $instance = new F3instance;
                                if ($instance->sandbox($file) === false) {
                                    return false;
                                }
                            }
                        }
                        return true;
                    }
                    self::error(404);
                    return false;
                }
            }
            if (!is_callable($func)) {
                self::error(404);
                return false;
            }
            $oop = is_array($func) && (is_object($func[0]) || is_string($func[0]));
            !is_object($func) && F3::set('called_func', array('class' => get_class($func[0]), 'function' => $func[1], 'hinstance' => $func[0]));
            if ($listen && $oop && method_exists($func[0], $before = 'beforeRoute') && !in_array($func[0], $classes)) {
                // Execute beforeRoute() once per class
                if (call_user_func(array($func[0], $before)) === false) {
                    return false;
                }
                $classes[] = is_object($func[0]) ? get_class($func[0]) : $func[0];
            }
            $m = null;
            is_array($func) && is_object($func[0]) && is_string($func[1]) && $m = new ReflectionMethod($func[0], $func[1]);
            if ($m && $m->getNumberOfParameters() > 0) {
                $out = self::runWithParams($func[0], $m, $_GET);
            } else {
                $out = call_user_func($func);
            }
            if ($listen && $oop && method_exists($func[0], $after = 'afterRoute') && !in_array($func[0], $classes)) {
                // Execute afterRoute() once per class
                if (call_user_func(array($func[0], $after)) === false) {
                    return false;
                }
                $classes[] = is_object($func[0]) ? get_class($func[0]) : $func[0];
            }
        }
        return $out;
    }

    /**
      Process routes based on incoming URI
      @public
     * */
    static function run() {
        // Validate user against spam blacklists
        if (self::$vars['DNSBL'] && !self::privateip($addr = self::realip()) && (!self::$vars['EXEMPT'] || !in_array($addr, self::split(self::$vars['EXEMPT'])))) {
            // Convert to reverse IP dotted quad
            $quad = implode('.', array_reverse(explode('.', $addr)));
            foreach (self::split(self::$vars['DNSBL']) as $list) {
            // Check against DNS blacklist
                if (gethostbyname($quad . '.' . $list) != $quad . '.' . $list) {
                    if (self::$vars['SPAM']) {
                        // Spammer detected; Send to blackhole
                        self::reroute(self::$vars['SPAM']);
                    } else {
                        // Forbidden
                        self::error(403);
                        die;
                    }
                }
            }
        }
        // Process routes
        if (!isset(self::$vars['ROUTES']) || !self::$vars['ROUTES']) {
            trigger_error(self::TEXT_NoRoutes);
            return;
        }
        $found = false;
        // Detailed routes get matched first
        krsort(self::$vars['ROUTES']);
        $time = time();
        $req = preg_replace('/^' . preg_quote(self::$vars['BASE'], '/') . '\b(.+)/' . (self::$vars['CASELESS'] ? '' : 'i'), '\1', rawurldecode($_SERVER['REQUEST_URI']));
        foreach (self::$vars['ROUTES'] as $uri => $route) {
            $pattern = '/^';
            $pattern.= preg_replace('/(?:\{\{)?@(\w+\b)(?:\}\})?/', '(?P<\1>[^\/&]+)', str_replace('\*', '(.*)', preg_quote($uri, '/')));
            $pattern.= '\/?(?:\?.*)?$/';
            $pattern.= (self::$vars['CASELESS'] ? '' : 'i') . 'um';
            if (!preg_match($pattern, $req, $args)) {
                continue;
            }
            $wild = is_int(strpos($uri, '/*'));
            // Inspect each defined route
            foreach ($route as $method => $proc) {
                if (!preg_match('/HEAD|' . $method . '/', $_SERVER['REQUEST_METHOD'])) {
                    continue;
                }
                $found = true;
                list($funcs, $ttl, $throttle, $hotlink) = $proc;
                if (!$hotlink && isset(self::$vars['HOTLINK']) && isset($_SERVER['HTTP_REFERER']) && parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) != $_SERVER['SERVER_NAME']) {
                    // Hot link detected; Redirect page
                    self::reroute(self::$vars['HOTLINK']);
                }
                if (!$wild) {
                    // Save named uri captures
                    foreach (array_keys($args) as $key) {
                        // Remove non-zero indexed elements
                        if (is_numeric($key) && $key) {
                            unset($args[$key]);
                        }
                    }
                }
                $encoded_ids = F3::get('EncodeIDs');
                foreach ($args as $k => $v) {
                    if (in_array($k, $encoded_ids, true)) {
                        $encoded_id = substr($v, 0, Xcrypt::ID_LENGTH);
                        $args[$k] = str_replace($encoded_id, Util::xcryptDecrypt($encoded_id), $v);
                    }
                }
                self::$vars['PARAMS'] = $args;
                // Default: Do not cache
                self::expire(0);
                if ($_SERVER['REQUEST_METHOD'] == 'GET' && $ttl) {
                    $_SERVER['REQUEST_TTL'] = $ttl;
                    // Get HTTP request headers
                    $req = self::headers();
                    // Content divider
                    $div = chr(0);
                    // Get hash code for this Web page
                    $hash = 'url.' . self::hash($_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI']);
                    $cached = Cache::cached($hash);
                    $uri = '/^' . self::HTTP_Content . ':.+/';
                    if ($cached && $time - $cached < $ttl) {
                        if (!isset($req[self::HTTP_IfMod]) || $cached > strtotime($req[self::HTTP_IfMod])) {
                            // Activate cache timer
                            self::expire($cached + $ttl - $time);
                            // Retrieve from cache
                            $buffer = Cache::get($hash);
                            $type = strstr($buffer, $div, true);
                            if (PHP_SAPI != 'cli' && !headers_sent() && preg_match($uri, $type, $match)) {
                                // Cached MIME type
                                header($match[0]);
                            }
                            // Save response
                            self::$vars['RESPONSE'] = substr(strstr($buffer, $div), 1);
                        }
                        else {
                            // Client-side cache is still fresh
                            self::status(304);
                            die;
                        }
                    } else {
                        // Activate cache timer
                        self::expire($ttl);
                        $type = '';
                        foreach (headers_list() as $hdr) {
                            if (preg_match($uri, $hdr)) {
                                // Add Content-Type header to buffer
                                $type = $hdr;
                                break;
                            }
                        }
                        // Cache this page
                        ob_start();
                        self::call($funcs, true);
                        self::$vars['RESPONSE'] = ob_get_clean();
                        if (!self::$vars['ERROR'] && self::$vars['RESPONSE']) {
                            // Compress and save to cache
                            Cache::set($hash, $type . $div . self::$vars['RESPONSE']);
                        }
                    }
                } else {
                    // Capture output
                    ob_start();
                    self::$vars['REQBODY'] = file_get_contents('php://input');
                    self::call($funcs, true);
                    self::$vars['RESPONSE'] = ob_get_clean();
                }
                $elapsed = time() - $time;
                $throttle = $throttle? : self::$vars['THROTTLE'];
                if ($throttle / 1e3 > $elapsed) {
                    // Delay output
                    usleep(1e6 * ($throttle / 1e3 - $elapsed));
                }
                if (strlen(self::$vars['RESPONSE']) && !self::$vars['QUIET']) {
                    // Display response
                    echo self::$vars['RESPONSE'];
                }
            }
            if ($found) {
                // Hail the conquering hero
                return;
            }
            // Method not allowed
            if (PHP_SAPI != 'cli' && !headers_sent()) {
                header(self::HTTP_Allow . ': ' . implode(',', array_keys($route)));
            }
            self::error(405);
            return;
        }
        if (PHP_SAPI != 'cli' && !empty($_SERVER['REQUEST_URI']) && $m = self::dispath($_SERVER['REQUEST_URI'])) {
        	echo self::call($m, true);
        } else {
            self::error(404);
        }
    }

    public static function setFilter($filter)
    {
        self::$vars['FILTER'][] = $filter;
    }

    /**
     * 提取路由模板变量
     */
    private static function getParam($route, $pattern) {
        $param = array();
        if (false !== strpos($route, '<') && preg_match_all('/<(\w+)>/', $route, $m)) {
            foreach ($m[1] as $name) {
                $param[$name] = "<$name>";
            }
        }
        return $param;
    }

    /**
     * 新版路由分发
     */
    private static function dispath($url) {
        $rule = array(
            //define for cp
            '/^cp\/(?P<controller>\w+)\/$/iu' => 'CP_<controller>->index',
            '/^cp\/(?P<controller>\w+)\/(?P<action>\w+)\/$/iu' => 'CP_<controller>-><action>',
            //define for web
            '/^(?P<controller>\w+)\/$/ui' => '<controller>Controller->index',
            '/^(?P<controller>\w+)\/(?P<action>\w+)\/$/ui' => '<controller>Controller-><action>',
        );
        $pos = strpos($url, '?');
        $pos && $url = substr($url, 0, $pos);
        $uri = $url;
        $url = trim($url, '/') . '/';
        foreach ($rule as $pattern => $template) {
            if (!preg_match($pattern, $url, $m)) {
                continue;
            } else {
                self::$vars['PARAMS'][] = $uri;
            }
            $param = self::getParam($pattern, $template);
            foreach ($m as $k => $v) {
                if (!isset($param[$k])) {
                    continue;
                }
                if (false !== strpos($template, $param[$k])) {
                    $template = str_replace($param[$k], $v, $template);
                } else {
                    self::$vars['PARAMS'][$k] = $_REQUEST[$k] = $_GET[$k] = $v;
                    !is_numeric($v) && self::$vars['PARAMS'][$k] = Util::xcryptDecrypt($v);
                }
            }
            return $template;
        }
        return false;
    }

    /**
     * 调用含有参数方法
     */
    private static function runWithParams($controller, $method, $params) {
        $ar = array();
        $func_param = $method->getParameters();
        foreach ($func_param as $param_item) {
            $name = $param_item->getName();
            if (isset($params[$name])) {
                if ($param_item->isArray()) {
                    $ar[] = is_array($params[$name]) ? $params[$name] : array($params[$name]);
                } else {
                    $ar[] = $params[$name];
                }
            } else if ($param_item->isDefaultValueAvailable()) {
                $ar[] = $param_item->getDefaultValue();
            } else {
                trigger_error('The reason cause this error may be param <strong style="color:#F00;">' . $name . '</strong> can not be null', E_USER_ERROR);
            }
        }
        return $method->invokeArgs($controller, $ar);
    }

    /**
      Transmit a file for downloading by HTTP client; If kilobytes per
      second is specified, output is throttled (bandwidth will not be
      controlled by default); Return true if successful, false otherwise;
      Support for partial downloads is indicated by third argument
      @param $file string
      @param $kbps int
      @param $partial
      @public
     * */
    static function send($file, $kbps = 0, $partial = true) {
        $file = self::resolve($file);
        if (!is_file($file)) {
            self::error(404);
            return false;
        }
        if (PHP_SAPI != 'cli' && !headers_sent()) {
            header(self::HTTP_Content . ': application/octet-stream');
            header(self::HTTP_Partial . ': ' . ($partial ? 'bytes' : 'none'));
            header(self::HTTP_Length . ': ' . filesize($file));
        }
        $ctr = 1;
        $handle = fopen($file, 'r');
        $time = microtime(true);
        while (!feof($handle) && !connection_aborted()) {
            if ($kbps) {
                // Throttle bandwidth
                $ctr++;
                if (($ctr / $kbps) > $elapsed = microtime(true) - $time) {
                    usleep(1e6 * ($ctr / $kbps - $elapsed));
                }
            }
            // Send 1KiB and reset timer
            echo fread($handle, 1024);
        }
        fclose($handle);
        die;
    }

    /**
      Remove HTML tags (except those enumerated) to protect against
      XSS/code injection attacks
      @return mixed
      @param $input string
      @param $tags string
      @public
     * */
    static function scrub($input, $tags = null) {
        if (is_array($input)) {
            foreach ($input as &$val) {
                $val = self::scrub($val, $tags);
            }
        }
        if (is_string($input)) {
            $input = ($tags == '*') ? $input : strip_tags($input, is_string($tags) ? ('<' . implode('><', self::split($tags)) . '>') : $tags);
        }
        return $input;
    }

    /**
      Call form field handler
      @param $fields string
      @param $funcs mixed
      @param $tags string
      @param $filter int
      @param $opt array
      @param $assign bool
      @public
     * */
    static function input($fields, $funcs = null, $tags = null, $filter = FILTER_UNSAFE_RAW, $opt = array(), $assign = true) {
        $funcs = is_string($funcs) ? self::split($funcs) : array($funcs);
        foreach (self::split($fields) as $field) {
            $found = false;
            // Sanitize relevant globals
            foreach (explode('|', 'GET|POST|REQUEST') as $var) {
                if (self::exists($var . '.' . $field)) {
                    $key = &self::ref($var . '.' . $field);
                    if (is_array($key)) {
                        foreach ($key as $sub) {
                            self::input($sub, $funcs, $tags, $filter, $opt, $assign);
                        }
                    } else {
                        $key = self::scrub($key, $tags);
                        $val = filter_var($key, $filter, $opt);
                        foreach ($funcs as $func) {
                            if ($func) {
                                if (is_string($func) && preg_match('/([\w\\]+)\s*->\s*(\w+)/', $func, $match)) {
                                    // Convert class->method syntax
                                    $func = array(new $match[1], $match[2]);
                                }
                                if (!is_callable($func)) {
                                    // Invalid handler
                                    trigger_error(sprintf(self::TEXT_Form, $field));
                                    return;
                                }
                                if (!$found) {
                                    $out = call_user_func($func, $val, $field);
                                    if (!$assign) {
                                        return $out;
                                    }
                                    if ($out) {
                                        $key = $out;
                                    }
                                    $found = true;
                                } else if ($assign && $out) {
                                    $key = $val;
                                }
                            }
                        }
                    }
                }
            }
            if (!$found) {
                // Invalid handler
                trigger_error(sprintf(self::TEXT_Form, $field));
                return;
            }
        }
    }

    /**
      Render user interface
      @return string
      @param $file string
      @public
     * */
    static function render($file) {
        $file = self::resolve($file);
        foreach (self::split(self::$vars['GUI']) as $gui) {
            if (is_file($view = self::fixslashes($gui . $file))) {
                $instance = new F3instance;
                $out = $instance->grab($view);
                return self::$vars['TIDY'] ? self::tidy($out) : $out;
            }
        }
    }

    /**
      Return runtime performance analytics
      @return array
      @public
     * */
    static function profile() {
        $stats = &self::$vars['STATS'];
        // Compute elapsed time
        $stats['TIME']['elapsed'] = microtime(true) - $stats['TIME']['start'];
        // Compute memory consumption
        $stats['MEMORY']['current'] = memory_get_usage();
        $stats['MEMORY']['peak'] = memory_get_peak_usage();
        return $stats;
    }

    /**
      Mock environment for command-line use and/or unit testing
      @param $pattern string
      @param $args array
      @public
     * */
    static function mock($pattern, array $args = null) {
        list($method, $uri) = explode(' ', $pattern, 2);
        $method = strtoupper($method);
        $url = parse_url($uri);
        $query = '';
        if ($args) {
            $query .= http_build_query($args);
        }
        $query.=isset($url['query']) ? (($query ? '&' : '') . $url['query']) : '';
        if ($query) {
            parse_str($query, $GLOBALS['_' . $method]);
            parse_str($query, $GLOBALS['_REQUEST']);
        }
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = self::$vars['BASE'] . $url['path'] . ($query ? ('?' . $query) : '');
    }

    /**
      Perform test and append result to TEST global variable
      @return string
      @param $cond bool
      @param $pass string
      @param $fail string
      @public
     * */
    static function expect($cond, $pass = null, $fail = null) {
        if (is_string($cond)) {
            $cond = self::resolve($cond);
        }
        $text = $cond ? $pass : $fail;
        self::$vars['TEST'][] = array(
            'result' => (int) (boolean) $cond,
            'text' => is_string($text) ? self::resolve($text) : var_export($text, true),
        );
        return $text;
    }

    /**
      Display default error page; Use custom page if found
      @param $code int
      @param $str string
      @param $trace array
      @param $fatal bool
      @public
     * */
    static function error($code, $str = '', array $trace = null, $fatal = false) {
        $prior = self::$vars['ERROR'];
        $out = '';
        switch ($code) {
            case 404:
                $str = sprintf(self::TEXT_NotFound, $_SERVER['REQUEST_URI']);
                break;
            case 405:
                $str = sprintf(self::TEXT_NotAllowed, $_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
                break;
            default:
                // Generate internal server error if code is zero
                if (!$code) {
                    $code = 500;
                }
                if (!self::$vars['DEBUG']) {
                    // Disable stack trace
                    $trace = null;
                } else if ($code == 500 && !$trace) {
                    $trace = debug_backtrace();
                }
                if (is_array($trace)) {
                    $line = 0;
                    $plugins = is_array($plugins = glob(self::$vars['PLUGINS'] . '*.php')) ? array_map('self::fixslashes', $plugins) : array();
                    // Stringify the stack trace
                    $out = '';
                    foreach ($trace as $nexus) {
                        // Remove stack trace noise
                        if (self::$vars['DEBUG'] < 3 && !$fatal && (
                                !isset($nexus['file']) || self::$vars['DEBUG'] < 2 && (
                                    strrchr(basename($nexus['file']), '.') == '.tmp' || in_array(self::fixslashes($nexus['file']), $plugins)
                                ) || isset($nexus['function']) && preg_match('/^(call_user_func(?:_array)?|' . 'trigger_error|{.+}|' . __FUNCTION__ . '|__)/', $nexus['function'])
                            )) {
                            continue;
                        }
                        $args = array();
                        if (isset($nexus['args'])) {
                            foreach ($nexus['args'] as $arg) {
                                if (is_bool($arg)) {
                                    $args[] = $arg ? "true" : "false";
                                } else if (is_numeric($arg)) {
                                    $args[] = $arg;
                                } else if (is_string($arg)) {
                                    $args[] = '"' . str_replace(array("\n", "\t"), array("", " "), trim($arg)) . '"';
                                } else if (is_scalar($arg)) {
                                    $args[] = $arg;
                                } else {
                                    $args[] = json_encode($arg);
                                }
                            }
                        }
                        $out .= "#$line "
                            . (isset($nexus['file']) ? $nexus['file'] : 'nofile')
                            .'(' . (isset($nexus['line']) ? $nexus['line'] : 'noline') . '): '
                            . (isset($nexus['class']) ? $nexus['class'] : '')
                            . (isset($nexus['type']) ? $nexus['type'] : '')
                            . (isset($nexus['function']) ? $nexus['function'] : '')
                            . '(' . implode(', ', $args) . ')'
                            . "\n";
                        $line++;
                    }
                }
                break;
        }
        // Save error details
        self::$vars['ERROR'] = array(
            'code' => $code,
            'title' => self::status($code),
            'text' => preg_replace('/\v/', '', $str),
            'trace' => $out
        );
        $error = &self::$vars['ERROR'];
        if (self::$vars['DEBUG'] < 2 && self::$vars['QUIET']) {
            return;
        }
        if ($prior || self::$vars['QUIET']) {
            return;
        }
        $func = self::$vars['ONERROR'];
        if ($func && !$fatal) {
            self::call($func, true);
        } else {
            $html['code'] = $error['code'];
            foreach (array('title', 'text', 'trace') as $sub) {
                // Convert to HTML entities for safety
                $html[$sub] = self::htmlencode(rawurldecode($error[$sub]));
            }
            echo '<html><head><title>' . $html['code'] . ' ' . $html['title'] . '</title></head><body><h1>' . $html['title'] . '</h1><p><i>' . $html['text'] . '</i></p><p>' . nl2br($html['trace']) . '</p></body></html>';
        }
        if (self::$vars['STRICT']) {
            die;
        }
    }

    /**
      Bootstrap code
      @public
     * */
    static function start() {
        // Prohibit multiple calls
        if (self::$vars) {
            return;
        }
        ini_set('display_errors', 0);
        ini_set('register_globals', 0);
        $ini = ini_get_all(null, false);
        ob_start();
        set_error_handler('F3::handleError', 2 == DEBUG ? E_ALL : E_ALL ^ E_NOTICE);
        set_exception_handler('F3::handleException');
        if (2 == DEBUG) {
            assert_options(ASSERT_ACTIVE, true);
            assert_options(ASSERT_BAIL, true);
            assert_options(ASSERT_WARNING, true);
            assert_options(ASSERT_CALLBACK, 'F3::handleAssert');
        }
        // Apache mod_rewrite enabled?
        if (function_exists('apache_get_modules') && !in_array('mod_rewrite', apache_get_modules())) {
            trigger_error(self::TEXT_Apache);
            return;
        }
        // Fix Apache's VirtualDocumentRoot limitation
        $_SERVER['DOCUMENT_ROOT'] = dirname(self::fixslashes($_SERVER['SCRIPT_FILENAME']));
        // Adjust HTTP request time precision
        $_SERVER['REQUEST_TIME'] = microtime(true);
        if (PHP_SAPI == 'cli') {
            // Command line: Parse GET variables in URL, if any
            if (isset($_SERVER['argc']) && $_SERVER['argc'] < 2) {
                array_push($_SERVER['argv'], '/');
            }
            // Detect host name from environment
            $_SERVER['SERVER_NAME'] = gethostname();
            // Convert URI to human-readable string
            self::mock('GET ' . $_SERVER['argv'][1]);
        }
        // Hydrate framework variables
        $base = self::fixslashes(preg_replace('/\/[^\/]+$/', '', $_SERVER['SCRIPT_NAME']));
        $scheme = PHP_SAPI == 'cli' ? null : isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' ? 'https' : 'http';
        $jar = array(
            'expire' => 0,
            'path' => $base? : '/',
            'domain' => is_int(strpos($_SERVER['SERVER_NAME'], '.')) && !filter_var($_SERVER['SERVER_NAME'], FILTER_VALIDATE_IP) ? ('.' . $_SERVER['SERVER_NAME']) : '',
            'secure' => ($scheme == 'https'),
            'httponly' => true
        );
        self::$vars = array(
            // Autoload folders
            'AUTOLOAD' => './',
            // Web root folder
            'BASE' => $base,
            // Cache backend to use (autodetect if true; disable if false)
            'CACHE' => false,
            // Case-sensitivity of route patterns
            'CASELESS' => true,
            // Stack trace verbosity:
            // 0-no stack trace, 1-noise removed, 2-normal, 3-verbose
            'DEBUG' => 1,
            // DNS black lists
            'DNSBL' => null,
            // Document encoding
            'ENCODING' => 'utf-8',
            // Last error
            'ERROR' => null,
            // Allow/prohibit framework class extension
            'EXTEND' => true,
            // IP addresses exempt from spam detection
            'EXEMPT' => null,
            // User interface folders
            'GUI' => './',
            // URL for hotlink redirection
            'HOTLINK' => null,
            // Include path for procedural code
            'IMPORTS' => './',
            // Default cookie settings
            'JAR' => $jar,
            // Default language (auto-detect if null)
            'LANGUAGE' => null,
            // Autoloaded classes
            'LOADED' => null,
            // Dictionary folder
            'LOCALES' => './',
            // Maximum POST size
            'MAXSIZE' => self::bytes($ini['post_max_size']),
            // Max mutex lock duration
            'MUTEX' => 60,
            // Custom error handler
            'ONERROR' => null,
            // Plugins folder
            'PLUGINS' => self::fixslashes(__DIR__) . '/',
            // Scheme/protocol
            'PROTOCOL' => $scheme,
            // Allow framework to proxy for plugins
            'PROXY' => false,
            // Stream handle for HTTP PUT method
            'PUT' => null,
            // Output suppression switch
            'QUIET' => false,
            // Absolute path to document root folder
            'ROOT' => $_SERVER['DOCUMENT_ROOT'] . '/',
            // Framework routes
            'ROUTES' => null,
            // URL for spam redirection
            'SPAM' => null,
            // Stop script on error?
            'STRICT' => true,
            // Profiler statistics
            'STATS' => array(
                'MEMORY' => array('start' => memory_get_usage()),
                'TIME' => array('start' => microtime(true))
            ),
            // Temporary folder
            'TEMP' => 'temp/',
            // Minimum script execution time
            'THROTTLE' => 0,
            // Tidy options
            'TIDY' => array(),
            // Framework version
            'VERSION' => self::TEXT_AppName . ' ' . self::TEXT_Version,
            // Default whois server
            'WHOIS' => 'whois.internic.net'
        );
        // Alias the GUI variable (2.0+)
        self::$vars['UI'] = &self::$vars['GUI'];

        //PPEAR 本地开发无须安全校验检查
        // if (php_sapi_name() != 'cli') {
        //     if (strpos($_SERVER['REQUEST_URI'], '/response') === false) { //兼容银联的格式
        //         //防XSS
        //         $GLOBALS['_POST'] = F3::xssEncode($GLOBALS['_POST']);
        //         $GLOBALS['_GET'] = F3::xssEncode($GLOBALS['_GET']);
        //         $GLOBALS['_COOKIE'] = F3::xssEncode($GLOBALS['_COOKIE']);
        //         if(!strcasecmp($_SERVER['REQUEST_METHOD'], 'POST'))
        //         {
        //             $info = parse_url($_SERVER['HTTP_REFERER']);
        //             if(!empty($info['host']) && $info['host'] != $_SERVER['HTTP_HOST'])
        //             {
        //                 self::error(404);
        //             }
        //         }
        //     }
        // }
        // Create convenience containers for PHP globals
        foreach (explode('|', self::PHP_Globals) as $var) {
            // Sync framework and PHP globals
            self::$vars[$var] = &$GLOBALS['_' . $var];
            if (isset($ini['magic_quotes_gpc']) && $ini['magic_quotes_gpc'] && preg_match('/^[GPCR]/', $var)) {
                // Corrective action on PHP magic quotes
                array_walk_recursive(self::$vars[$var], function(&$val) {$val = stripslashes($val);});
            }
        }
        // Initialize autoload stack and shutdown sequence
        spl_autoload_register(__CLASS__ . '::autoload');
        register_shutdown_function(__CLASS__ . '::stop');
    }

    /**
     * handle exception message
     */
    public static function handleException($exception) {
        restore_error_handler();
        restore_exception_handler();
        try {
            $file = $exception->getFile();
            $message = $exception->getMessage();
            $line = $exception->getLine();
            $code = $exception->getCode();
            if (error_reporting()) {
                if (2 == DEBUG) {
                    !$code && $code = E_ERROR;
                    $er = self::errorInfo($code, $message, $file, $line, 0, $exception);
                    self::showError($code, $message, $file, $line, $er, $exception);
                } else {
                    self::error(500, $exception->getMessage(), $exception->getTrace());
                }
            }
        } catch (Exception $e) {

        }
    }

    /**
     * handle assert message
     */
    public static function handleAssert($file, $line, $message) {
        try {
            $code = E_NOTICE;
            $er = self::errorInfo($code, $message, $file, $line);
            self::showError($code, $message, $file, $line, $er, null);
        } catch (Exception $e) {

        }
    }

    /**
     * handle error message
     */
    public static function handleError($code, $message, $file, $line) {
        restore_error_handler();
        restore_exception_handler();
        try {
            if (error_reporting() & $code) {
                if (2 == DEBUG) {
                    $er = self::errorInfo($code, $message, $file, $line);
                    self::showError($code, $message, $file, $line, $er, null);
                } else {
                    self::error(500, $message);
                }
            }
        } catch (Exception $e) {

        }
    }

    /**
      Execute shutdown function
      @public
     * */
    static function stop() {
        chdir(self::$vars['ROOT']);
        $error = error_get_last();
        if ($error) {
            $file = $error['file'];
            $line = $error['line'];
            $message = $error['message'];
        }
        if ($error && !self::$vars['QUIET'] && in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR))) {
            if (empty($error)) {
                @flush();
                @ob_flush();
                @ob_end_flush();
                if (function_exists('fastcgi_finish_request')) {
                    fastcgi_finish_request();
                }
            }
            if ($error && 2 == DEBUG) {
                $code = $error['type'];
                $file = $error['file'];
                $line = $error['line'];
                $message = $error['message'];
                $er = self::errorInfo($code, $message, $file, $line);
                self::showError($code, $message, $file, $line, $er);
            } else {
                self::error(500, sprintf(self::TEXT_Fatal, $error['message']), array($error), false);
            }
        }
        if (isset(self::$vars['UNLOAD']) && is_callable(self::$vars['UNLOAD'])) {
            self::call(self::$vars['UNLOAD']);
        }
    }

    private static function showError($code, $message, $file, $line, $error, $exception = null) {
        ob_clean();
        $now = date('Y-m-d H:i:s');
        $title = $exception ? get_class($exception) : "PHP Error [{$code}]";
        extract($error);
        if (strcasecmp(PHP_SAPI, 'cli')) {
            require(dirname(__FILE__) . '/trace.php');
        } else {
            echo "{$now} {$title} {$message}\r\n";
            foreach ($stack as $key => $item) {
                echo sprintf("#{$key}%s(%d):\t%s%s%s(%s)\r\n", $item['file'], $item['line'], $item['class'], $item['type'], $item['function'], self::showArgs($item['args']));
            }
        }
        echo ob_get_clean();
        exit(1);
    }

    private static function showArgs($args) {
        $string = '';
        foreach ($args as $var) {
            $string.= self::showVar($var);
        }
        return rtrim($string, ', ');
    }

    private static function showVar($var, $key = '') {
        $string = $key ? '\'' . $key . '\'=>' : '';
        if (is_string($var)) {
            if (strlen($var) > 64) {
                $string.='"' . htmlspecialchars(substr($var, 0, 64)) . '..."';
            } else {
                $string.='"' . htmlspecialchars($var) . '"';
            }
        } else if (is_float($var) || is_integer($var)) {
            $string.= $var;
        } else if (is_bool($var)) {
            $string.= $var ? 'true' : 'false';
        } else if (is_object($var)) {
            $string.= get_class($var);
        } else if (is_array($var)) {
            $string.= self::showArray($var);
        } else if (is_resource($var)) {
            $string .= 'resource';
        } else if (is_null($var)) {
            $string .= 'null';
        }
        !is_array($var) && $string.= ',';
        return $string;
    }

    private static function showArray($var) {
        $string = 'array(';
        foreach ($var as $k => $v) {
            $string.= self::showVar($v, is_numeric($k) ? '' : $k);
        }
        return rtrim($string, ', ') . '),';
    }

    private static function errorInfo($code, $message, $file, $line, $skip = 1, $exception = null) {
        $array = array('addr' => '127.0.0.1', 'from' => '127.0.0.1', 'host' => '127.0.0.1');
        $array['exectime'] = microtime(true) - F3_BTIME;
        $array['error']['file'] = $file;
        $array['error']['line'] = $line;
        $array['error']['message'] = $message;
        $array['error']['code'] = $code;
        $array['error']['source'] = self::getSource($file, $line);
        $array['ver'] = '';
        $array['time'] = date('Y-m-d H:i:s');
        $array['server'] = isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'Unknown';
        $array['phpver'] = PHP_VERSION;
        $array['mmem'] = memory_get_peak_usage(true);
        $array['umem'] = memory_get_usage(true);
        !empty($_SERVER['SERVER_ADDR']) && $array['addr'] = $_SERVER['SERVER_ADDR'];
        !empty($_SERVER['REMOTE_ADDR']) && $array['from'] = $_SERVER['REMOTE_ADDR'];
        !empty($_SERVER['HTTP_X_FORWARDED_FOR']) && $array['from'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
        !empty($_SERVER['HTTP_HOST']) && $array['host'] = $_SERVER['HTTP_HOST'];
        $array['stack'] = array();
        $trace = $exception ? $exception->getTrace() : debug_backtrace();
        if (count($trace) > $skip && $skip) {
            $trace = array_slice($trace, $skip);
        }
        foreach ($trace as $key => $item) {
            !isset($item['file']) && $item['file'] = 'unknown';
            !isset($item['line']) && $item['line'] = 0;
            !isset($item['function']) && $item['function'] = 'unknown';
            !isset($item['type']) && $item['type'] = '';
            !isset($item['class']) && $item['class'] = '';
            !isset($item['args']) && $item['args'] = array();
            if ($item['source'] = self::getSource($item['file'], $item['line'])) {
                $array['stack'][] = $item;
            }
        }
        return $array;
    }

    private static function getSource($file, $lineNumber, $padding = 3) {
        $source = array();
        if (!$file || !is_readable($file) || !$fp = fopen($file, 'r')) {
            return $source;
        }
        $bline = $lineNumber - $padding;
        $eline = $lineNumber + $padding;
        $line = 0;
        while (($row = fgets($fp))) {
            if (++$line > $eline) {
                break;
            }
            if ($line < $bline) {
                continue;
            }
            $row = htmlspecialchars($row, ENT_NOQUOTES);
            $source[$line] = $row;
        }
        fclose($fp);
        return $source;
    }

    /**
      onLoad event handler (static class initializer)
      @public
     * */
    static function loadstatic($class) {
        $loaded = &self::$vars['LOADED'];
        $lower = strtolower($class);
        if (!isset($loaded[$lower])) {
            $loaded[$lower] = array_map('strtolower', get_class_methods($class));
            if (in_array('onload', $loaded[$lower])) {
                // Execute onload method
                $method = new ReflectionMethod($class, 'onload');
                if ($method->isStatic()) {
                    call_user_func(array($class, 'onload'));
                } else {
                    trigger_error(sprintf(self::TEXT_Static, $class . '::onload'));
                }
            }
        }
    }

    /**
      Intercept instantiation of objects in undefined classes
      @param $class string
      @public
     * */
    static function autoload($class) {
        if (defined('ROOT_DIR') && strncmp($class, 'Mod', 3) === 0 && ($pos = strpos($class, '_', 3)) !== false) {
            //模块的内部实现文件
            static $loaded_mod_class = array();
            if (isset($loaded_mod_class[strtolower($class)])) {
                return;
            }
            $mod = strtolower(substr($class, 0, $pos));
            require ROOT_DIR . '/modules/' . $mod . '/' . $class . '.php';
            $loaded_mod_class[strtolower($class)] = 1;
            return;
        }
        foreach (self::split(self::$vars['PLUGINS'] . ';' . self::$vars['AUTOLOAD']) as $auto) {
            $ns = '';
            $iter = ltrim($class, '\\');
            for (;;) {
                if ($glob = glob($auto . self::fixslashes($ns) . '*')) {
                    $grep = preg_grep('/^' . preg_quote($auto, '/') . implode('[\/\.]', explode('\\', $ns . $iter)) . '(?:\.class)?\.php/i', $glob);
                    if ($file = current($grep)) {
                        unset($grep);
                        $instance = new F3instance;
                        $instance->sandbox($file);
                        // Verify that the class was loaded
                        if (class_exists($class, false)) {
                            // Run onLoad event handler if defined
                            self::loadstatic($class);
                            return;
                        } else if (interface_exists($class, false)) {
                            return;
                        }
                    }
                    $parts = explode('\\', $iter, 2);
                    if (count($parts) > 1) {
                        $iter = $parts[1];
                        $grep = preg_grep('/^' . preg_quote($auto . self::fixslashes($ns) . $parts[0], '/') . '$/i', $glob);
                        if ($file = current($grep)) {
                            $ns = str_replace('/', '\\', preg_replace('/^' . preg_quote($auto, '/') . '/', '', $file)) . '\\';
                            continue;
                        }
                        $ns.=$parts[0] . '\\';
                    }
                }
                break;
            }
        }
        if (count(spl_autoload_functions()) == 1) {
            // No other registered autoload functions exist
            trigger_error(sprintf(self::TEXT_Class, $class));
        }
    }

    /**
      Intercept calls to undefined static methods and proxy for the
      called class if found in the plugins folder
      @return mixed
      @param $func string
      @param $args array
      @public
     * */
    static function __callStatic($func, array $args) {
        if (self::$vars['PROXY'] && $glob = glob(self::fixslashes(self::$vars['PLUGINS'] . '/*.php', GLOB_NOSORT))) {
            foreach ($glob as $file) {
                $class = strstr(basename($file), '.php', true);
                // Prevent recursive calls
                $found = false;
                foreach (debug_backtrace() as $trace) {
                     // Support namespaces
                    if (isset($trace['class']) && preg_match('/\b' . preg_quote($trace['class']) . '\b/i', strtolower($class)) && preg_match('/' . $trace['function'] . '/i', strtolower($func))) {
                        $found = true;
                        break;
                    }
                }
                if ($found) {
                    continue;
                }
                // Run onLoad event handler if defined
                self::loadstatic($class);
                if (in_array($func, self::$vars['LOADED'][$class])) {
                    // Proxy for plugin
                    return call_user_func_array(array($class, $func), $args);
                }
            }
        }
        if (count(spl_autoload_functions()) == 1) {
            // No other registered autoload functions exist
            trigger_error(sprintf(self::TEXT_Method, $func));
        }
        return false;
    }

}

//! Cache engine
class Cache extends Base {

    //@{ Locale-specific error/exception messages
    const
        TEXT_Backend = 'Cache back-end is invalid',
        TEXT_Store = 'Unable to save %s to cache',
        TEXT_Fetch = 'Unable to retrieve %s from cache',
        TEXT_Clear = 'Unable to clear %s from cache';
    //@}

    static
        //! Level-1 cached object
        $buffer,
        //! Cache back-end
        $backend;

    /**
      Auto-detect extensions usable as cache back-ends; MemCache must be
      explicitly activated to work properly; Fall back to file system if
      none declared or detected
      @public
     * */
    static function detect() {
        $ref = array_merge(array_intersect(array('apc', 'xcache'), array_map('strtolower', get_loaded_extensions())), array());
        self::$vars['CACHE'] = array_shift($ref)? : ('folder=' . self::$vars['ROOT'] . 'cache/');
    }

    /**
      Initialize cache backend
      @return bool
      @public
     * */
    static function prep() {
        if (!self::$vars['CACHE']) {
            return true;
        }
        if (is_bool(self::$vars['CACHE'])) {
            // Auto-detect backend
            self::detect();
        }
        if (preg_match('/^(apc)|(memcache)=(.+)|(xcache)|(folder)=(.+\/)/i', self::$vars['CACHE'], $match)) {
            if (isset($match[5]) && $match[5]) {
                if (!is_dir($match[6])) {
                    self::mkdir($match[6]);
                }
                // File system
                self::$backend = array('type' => 'folder', 'id' => $match[6]);
            } else {
                $ext = strtolower($match[1]? : ($match[2]? : $match[4]));
                if (!extension_loaded($ext)) {
                    trigger_error(sprintf(self::TEXT_PHPExt, $ext));
                    return false;
                }
                if (isset($match[2]) && $match[2]) {
                    // Open persistent MemCache connection(s)
                    $mcache = null;
                    foreach (self::split($match[3]) as $server) {
                        // Hostname:port
                        list($host, $port) = explode(':', $server);
                        if (is_null($port)) {
                            // Use default port
                            $port = 11211;
                        }
                        // Connect to each server
                        if (is_null($mcache)) {
                            $mcache = memcache_pconnect($host, $port);
                        } else {
                            memcache_add_server($mcache, $host, $port);
                        }
                    }
                    // MemCache
                    self::$backend = array('type' => $ext, 'id' => $mcache);
                } else {
                    // APC and XCache
                    self::$backend = array('type' => $ext);
                }
            }
            self::$buffer = null;
            return true;
        }
        // Unknown back-end
        trigger_error(self::TEXT_Backend);
        return false;
    }

    /**
      Store data in framework cache; Return true/false on success/failure
      @return bool
      @param $name string
      @param $data mixed
      @public
     * */
    static function set($name, $data) {
        if (!self::$vars['CACHE']) {
            return true;
        }
        if (is_null(self::$backend)) {
            // Auto-detect back-end
            //self::detect();
            if (!self::prep()) {
                return false;
            }
        }
        $key = $_SERVER['SERVER_NAME'] . '.' . $name;
        // Serialize data for storage
        $time = time();
        // Add timestamp
        $val = serialize(array($time, $data));
        // Instruct back-end to store data
        switch (self::$backend['type']) {
            case 'apc':
                $ok = apc_store($key, $val);
                break;
            case 'memcache':
                $ok = memcache_set(self::$backend['id'], $key, $val);
                break;
            case 'xcache':
                $ok = xcache_set($key, $val);
                break;
            case 'folder':
                $ok = self::putfile(self::$backend['id'] . $key, $val);
                break;
        }
        if (is_bool($ok) && !$ok) {
            trigger_error(sprintf(self::TEXT_Store, $name));
            return false;
        }
        // Free up space for level-1 cache
        while (count(self::$buffer) && strlen(serialize($data)) + strlen(serialize(array_slice(self::$buffer, 1))) > ini_get('memory_limit') - memory_get_peak_usage()) {
            self::$buffer = array_slice(self::$buffer, 1);
        }
        self::$buffer[$name] = array('data' => $data, 'time' => $time);
        return true;
    }

    /**
      Retrieve value from framework cache
      @return mixed
      @param $name string
      @param $quiet bool
      @public
     * */
    static function get($name, $quiet = false) {
        if (!self::$vars['CACHE']) {
            return false;
        }
        if (is_null(self::$backend)) {
            // Auto-detect back-end
            //self::detect();
            if (!self::prep()) {
                return false;
            }
        }
        $stats = &self::$vars['STATS'];
        if (!isset($stats['CACHE'])) {
            $stats['CACHE'] = array(
                'level-1' => array('hits' => 0, 'misses' => 0),
                'backend' => array('hits' => 0, 'misses' => 0)
            );
        }
        // Check level-1 cache first
        if (isset(self::$buffer) && isset(self::$buffer[$name])) {
            $stats['CACHE']['level-1']['hits'] ++;
            return self::$buffer[$name]['data'];
        } else {
            $stats['CACHE']['level-1']['misses'] ++;
        }
        $key = $_SERVER['SERVER_NAME'] . '.' . $name;
        // Instruct back-end to fetch data
        switch (self::$backend['type']) {
            case 'apc':
                $val = apc_fetch($key);
                break;
            case 'memcache':
                $val = memcache_get(self::$backend['id'], $key);
                break;
            case 'xcache':
                $val = xcache_get($key);
                break;
            case 'folder':
                $val = is_file(self::$backend['id'] . $key) ? self::getfile(self::$backend['id'] . $key) : false;
                break;
        }
        if (is_bool($val)) {
            $stats['CACHE']['backend']['misses'] ++;
            // No error display if specified
            if (!$quiet) {
                trigger_error(sprintf(self::TEXT_Fetch, $name));
            }
            self::$buffer[$name] = null;
            return false;
        }
        // Unserialize timestamp and data
        list($time, $data) = unserialize($val);
        $stats['CACHE']['backend']['hits'] ++;
        // Free up space for level-1 cache
        while (count(self::$buffer) && strlen(serialize($data)) + strlen(serialize(array_slice(self::$buffer, 1))) > ini_get('memory_limit') - memory_get_peak_usage()) {
            self::$buffer = array_slice(self::$buffer, 1);
        }
        self::$buffer[$name] = array('data' => $data, 'time' => $time);
        return $data;
    }

    /**
      Delete variable from framework cache
      @return bool
      @param $name string
      @param $quiet bool
      @public
     * */
    static function clear($name, $quiet = false) {
        if (!self::$vars['CACHE']) {
            return true;
        }
        if (is_null(self::$backend)) {
            // Auto-detect back-end
            self::detect();
            if (!self::prep()) {
                return false;
            }
        }
        $key = $_SERVER['SERVER_NAME'] . '.' . $name;
        // Instruct back-end to clear data
        switch (self::$backend['type']) {
            case 'apc':
                $ok = !apc_exists($key) || apc_delete($key);
                break;
            case 'memcache':
                $ok = memcache_delete(self::$backend['id'], $key);
                break;
            case 'xcache':
                $ok = !xcache_isset($key) || xcache_unset($key);
                break;
            case 'folder':
                $ok = !is_file(self::$backend['id'] . $key) || @unlink(self::$backend['id'] . $key);
                break;
        }
        if (is_bool($ok) && !$ok) {
            if (!$quiet) {
                trigger_error(sprintf(self::TEXT_Clear, $name));
            }
            return false;
        }
        // Check level-1 cache first
        if (isset(self::$buffer) && isset(self::$buffer[$name])) {
            unset(self::$buffer[$name]);
        }
        return true;
    }

    /**
      Return false if specified variable is not in cache;
      otherwise, return Un*x timestamp
      @return mixed
      @param $name string
      @public
     * */
    static function cached($name) {
        return self::get($name, true) ? self::$buffer[$name]['time'] : false;
    }

}

//! F3 object mode
class F3instance {

    const
        TEXT_Conflict = '%s conflicts with framework method name';

    /**
      Get framework variable reference; Workaround for PHP's
      call_user_func() reference limitation
      @return mixed
      @param $key string
      @param $set bool
      @public
     * */
    function &ref($key, $set = true) {
        return F3::ref($key, $set);
    }

    /*
      Run PHP code in sandbox
      @param $file string
      @public
     */
    function sandbox($file) {
        return require $file;
    }

    /**
      Grab file contents
      @return mixed
      @param $file string
      @public
     * */
    function grab($file) {
        $file = F3::resolve($file);
        if (!ini_get('short_open_tag')) {
            $text = preg_replace_callback('/<\?(?:\s|\s*(=))(.+?)\?>/s', function($tag) {return '<?php ' . ($tag[1] ? 'echo ' : '') . trim($tag[2]) . ' ?>';}, $orig = self::getfile($file));
            if (ini_get('allow_url_fopen') && ini_get('allow_url_include')) {
                // Stream wrap
                $file = 'data:text/plain,' . urlencode($text);
            } else if ($text != $orig) {
                // Save re-tagged file in temporary folder
                if (!is_dir($ref = F3::ref('TEMP'))) {
                    F3::mkdir($ref);
                }
                $temp = $ref . $_SERVER['SERVER_NAME'] . '.tpl.' . F3::md5($file);
                if (!is_file($temp)) {
                    self::mutex(function() use($temp, $text) {file_put_contents($temp, $text);});
                }
                $file = $temp;
            }
        }
        ob_start();
        // Render
        $this->sandbox($file);
        return ob_get_clean();
    }

    /**
      Proxy for framework methods
      @return mixed
      @param $func string
      @param $args array
      @public
     * */
    function __call($func, array $args) {
        return call_user_func_array('F3::' . $func, $args);
    }

    /**
      Class constructor
      @param $boot bool
      @public
     * */
    function __construct($boot = false) {
        if ($boot) {
            F3::start();
        }
        // Allow application to override framework methods?
        if (F3::ref('EXTEND')) {
            // User assumes risk
            return;
        }
        // Get all framework methods not defined in this class
        $def = array_diff(get_class_methods('F3'), get_class_methods(__CLASS__));
        // Check for conflicts
        $class = new ReflectionClass($this);
        foreach ($class->getMethods() as $func) {
            if (in_array($func->name, $def)) {
                trigger_error(sprintf(self::TEXT_Conflict, get_called_class() . '->' . $func->name));
            }
        }
    }

}

// Bootstrap
return new F3instance(true);

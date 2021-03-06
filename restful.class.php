<?php
/**
 * Created by PhpStorm.
 * User: ershov-ilya
 * Website: ershov.pw
 * GitHub : https://github.com/ershov-ilya
 * Date: 25.01.2015
 * Time: 12:52
 */
//header('Content-Type: text/plain; charset=utf-8');

class RESTful {
    private $raw_scope;
    public $scope;
    public $data;
    static $filter;

    function __construct($ACTION='',
                         $data_filter=array(),
                         $scopeSanitize=array(),
                         $raw_scope_filter=array('ACTION','METHOD','id','scope','sc','hash', 'sessid', 'crm', 'agent', 'ip', 'city', 'Referer', 'http_referer')
    )
    {
        if(is_string($data_filter)) $data_filter=explode(',',$data_filter);
        if(is_string($raw_scope_filter)) $raw_scope_filter=explode(',',$raw_scope_filter);
        $this->raw_scope = array();

        defined('ACTION') or define('ACTION', $ACTION);
        RESTful::$filter = $data_filter;

        // Define method type
        if(empty($_SERVER['DOCUMENT_ROOT']) && !empty($_SERVER['SHELL'])) {
            define('METHOD', 'CONSOLE');
            //$this->private_name='CONSOLE';
        }
        else
        {
            $this->raw_scope['agent'] = $_SERVER['HTTP_USER_AGENT'];
            $this->raw_scope['ip'] = $ip = $_SERVER['REMOTE_ADDR'];
            if(isset($_SERVER['HTTP_REFERER'])){$this->raw_scope['http_referer']=$_SERVER['HTTP_REFERER'];}

            if(isset($_SERVER['REQUEST_METHOD']))
            {
                define('METHOD', $_SERVER['REQUEST_METHOD']);
                //$this->private_name=$_SERVER['REQUEST_METHOD'];
            }
            else{
                define('METHOD', 'UNKNOWN');
            }
        }


        // Combine parameters
        if(METHOD=='CONSOLE') {
            //$this->raw_scope = array_merge($this->raw_scope, $_SERVER['argv']);
            require(API_CORE_PATH.'/class/rest/getoptions.php');
            $this->raw_scope = array_merge($this->raw_scope, getOptions());
        }
        else{
            $this->raw_scope = array_merge($this->raw_scope, $_REQUEST);
            $this->raw_scope = array_merge($this->raw_scope, $this->parseRequestHeaders());
        }

        // Расстановка значений
        $this->raw_scope['ACTION']=$ACTION;
        $this->raw_scope['METHOD']=METHOD;
        if(empty($this->raw_scope['scope']) && !empty($this->raw_scope['sc'])){
            // sc - синоним scope
            $this->raw_scope['scope']=$this->raw_scope['sc'];
            unset($this->raw_scope['sc']);
        }

        // Для дебага, возможность переопределять метод
        if(DEBUG && isset($_GET['METHOD'])) $this->raw_scope['METHOD']=$_GET['METHOD'];

        $this->scope = $this->sanitize($this->filtrateScope($raw_scope_filter), $scopeSanitize);
        $this->data = $this->sanitize($this->filtrateScope(), $scopeSanitize);
        return $this->raw_scope;
    }

    public static function map($data, $map=array(), $strict=false){
        $result=array();
        if($strict) {
            foreach ($data as $k => $v) {
                if (isset($map[$k])) $result[$map[$k]] = $v;
            }
        }else{
            foreach ($data as $k => $v) {
                if (isset($map[$k])) $result[$map[$k]] = $v;
                else $result[$k] = $v;
            }
        }
        return $result;
    }

    function getRaw(){
        return $this->raw_scope;
    }

    function parseRequestHeaders() {
        $headers = array();
        foreach($_SERVER as $key => $value) {
            if (substr($key, 0, 5) <> 'HTTP_') {
                continue;
            }
            $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
            $headers[$header] = $value;
        }
        return $headers;
    }

    function sanitize($arr, $filter=array()){
        $out=array();
        foreach($arr as $key => $val) {
            $val=urldecode($val);
            if(isset($filter[$key])){
                $type=gettype($filter[$key]);
                switch($type){
                    case 'integer':
                        $out[$key] = filter_var($val, $filter[$key]);
                        break;
                    case 'string': // регулярка типа '/[^0-9a-zA-Z]/'
                        $out[$key]=preg_replace($filter[$key],'',$val);
                        break;
                    case 'object': // Closure объект, либо объект с методом __invoke
                        $out[$key]=$filter[$key]($val);
                        break;
                }
                continue;
            }
            switch ($key) {
                case 'sc':
                case 'scope':
                case 'user':
                case 'login':
                    // Only ALLOWED SYMBOLS
                    $out[$key] = preg_replace('/[^a-zA-Z0-9\-_\.]+/i', '', $val);
                    break;
                case 'METHOD':
                case 'ACTION':
                    $out[$key] = preg_replace('/[^a-zA-Z\-_\.]+/i', '', $val);
                    break;
//                case 'phone':
//                    $out[$key] = preg_replace('/[^0-9\s\(\)\-_\.,\+]+/i', '', $val);
//                    break;
                case 'pass':
                case 'name':
                case 'surname':
                    $out[$key] = filter_var($val, FILTER_SANITIZE_STRING);
                    break;
                case 'phone':
                case 'id':
                    $out[$key] = filter_var($val, FILTER_SANITIZE_NUMBER_INT);
                    break;
                case 'email':
                    $out[$key] = filter_var($val, FILTER_SANITIZE_EMAIL);
                    break;
                default:
                    //$out[$key] = $val;
                    $out[$key] = filter_var($val, FILTER_SANITIZE_STRING);
            }
        }
        return $out;
    }

    function filtrate($arr, $filter=NULL){
        if($filter==NULL) $filter=RESTful::$filter;
        if (is_string($filter)) $filter=explode(',',$filter);
        $res=array();
        foreach($filter as $el){
            $el_cropspace=preg_replace('/ /','',$el);
            if(isset($arr[$el_cropspace])) $res[$el_cropspace]=$arr[$el_cropspace]; // Вырезаем пробелы из имён параметров
            if(isset($arr[$el])) $res[$el]=$arr[$el];
        }
        return $res;
    }

    function filtrateScope($filter=NULL){
        return $this->filtrate($this->raw_scope,$filter);
    }

    function randomString($length = 12, $charSet='') {
        if(!empty($charSet )) $characters = $charSet;
        else $characters = '0123456789abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    function __invoke($key=null){
        if(empty($key)) return null;
        $type=gettype($key);
        if(!($type=='int'||$type=='string')) return null;
        if(isset($this->data[$key])) return $this->data[$key];
        if(isset($this->scope[$key])) return $this->scope[$key];
        return null;
    }

} // class RESTful

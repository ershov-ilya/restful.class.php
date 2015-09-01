<?php
/**
 * Created by PhpStorm.
 * User: ershov-ilya
 * Website: ershov.pw
 * GitHub : https://github.com/ershov-ilya
 * Date: 25.01.2015
 * Time: 12:52
 */
header('Content-Type: text/plain; charset=utf-8');

class RESTful {
    private $raw_scope;
    public $scope;
    public $data;
    static $filter;

    function __construct($ACTION='',
                         $filter=array(),
                         $arrSanitize=array(),
                         $raw_scope_filter=array('ACTION','METHOD','id','scope','sc','hash', 'sessid', 'crm', 'agent', 'ip', 'city', 'Referer', 'http_referer')
    ){
        $this->raw_scope = array();

        defined('ACTION') or define('ACTION', $ACTION);
        RESTful::$filter = $filter;

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

        $this->scope = $this->sanitize($this->filtrateScope($raw_scope_filter), $arrSanitize);
        $this->data = $this->sanitize($this->filtrateScope(), $arrSanitize);
        return $this->raw_scope;
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
                $out[$key] = filter_var($val, $filter[$key]);
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
        if (is_string($filter)){
            $filter=explode(',',$filter);
        }
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

} // class restful
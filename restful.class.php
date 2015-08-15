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
    private $private_scope;
    public $scope;
    public $data;
    static $filter;

    function __construct($ACTION='', $filter=array(), $arrSanitize=array()){
        $this->private_scope = array();

        defined('ACTION') or define('ACTION', $ACTION);
        $scope_filter=array('ACTION','METHOD','id','scope','sc','hash', 'sessid', 'crm', 'agent', 'ip', 'city', 'referer');
        RESTful::$filter = $filter;

        // Define method type
        if(isset($_SERVER['argc'])) {
            define('METHOD', 'CONSOLE');
            //$this->private_name='CONSOLE';
        }
        else
        {
            $this->private_scope['agent'] = $_SERVER['HTTP_USER_AGENT'];
            $this->private_scope['ip'] = $ip = $_SERVER['REMOTE_ADDR'];
            if(isset($_SERVER['HTTP_REFERER'])){$this->private_scope['referer']=$_SERVER['HTTP_REFERER'];}

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
            //$this->private_scope = array_merge($this->private_scope, $_SERVER['argv']);
            require(API_CORE_PATH.'/class/rest/getoptions.php');
            $this->private_scope = array_merge($this->private_scope, getOptions());
        }
        else{
            $this->private_scope = array_merge($this->private_scope, $_REQUEST);
            $this->private_scope = array_merge($this->private_scope, $this->parseRequestHeaders());
        }

        // Расстановка значений
        $this->private_scope['ACTION']=$ACTION;
        $this->private_scope['METHOD']=METHOD;
        if(empty($this->private_scope['scope']) && !empty($this->private_scope['sc'])){
            // sc - синоним scope
            $this->private_scope['scope']=$this->private_scope['sc'];
            unset($this->private_scope['sc']);
        }

        // Для дебага, возможность переопределять метод
        if(DEBUG && isset($_GET['METHOD'])) $this->private_scope['METHOD']=$_GET['METHOD'];

        $this->scope = $this->sanitize($this->filtrateScope($scope_filter), $arrSanitize);
        $this->data = $this->sanitize($this->filtrateScope(), $arrSanitize);
        return $this->private_scope;
    }

    function getRaw(){
        return $this->private_scope;
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
        return $this->filtrate($this->private_scope,$filter);
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
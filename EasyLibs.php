<?php
//
//                >>>  EasyLibs.php  <<<
//
//
//      [Version]    v2.3  (2016-04-08)  Stable
//
//      [Require]    PHP v5.3+
//
//      [Usage]      A Light-weight PHP Class Library
//                   without PHP Extensions.
//
//
//          (C)2015-2016    shiy2008@gmail.com
//

// -----------------------------------
//
//    File System Node Object  v0.4
//
// -----------------------------------

class FS_File extends SplFileObject {
    private $accessMode;
    public $URI;

    public function __construct($_File_Name,  $_Mode = 'a+') {
        if (! file_exists($_File_Name)) {
            @ mkdir( pathinfo($_File_Name, PATHINFO_DIRNAME) );
            file_put_contents($_File_Name, '');
        }
        parent::__construct($_File_Name, $_Mode);

        $this->accessMode = $_Mode;
        $this->URI = $this->getRealPath();
    }
    public function readAll() {
        return  file_get_contents( $this->URI );
    }
    public function write($_Data) {
        return $this->fwrite($_Data);
    }
    public function delete() {
        $_URI = $this->URI;
        unset($this);
        return unlink($_URI);
    }
    public function copyTo($_Target) {
        $_Mode = $this->accessMode;
        $_URI = $this->URI;

        unset($this);
        copy($_URI, $_Target);

        return  new self($_URI, $_Mode);
    }
    public function moveTo($_Target) {
        $_Mode = $this->accessMode;
        $_URI = $this->URI;

        unset($this);
        rename($_URI, $_Target);

        return  new self($_Target, $_Mode);
    }
}

class FS_Directory extends SplFileInfo {
    private static function realPath($_Path) {
        $_Path = realpath($_Path);
        return  (substr($_Path, -1) == DIRECTORY_SEPARATOR)  ?  substr($_Path, 0, -1) : $_Path;
    }

    private $accessMode;
    public $URI;

    public function __construct($_Dir_Name,  $_Mode = 0764) {
        if (! file_exists( $_Dir_Name ))
            mkdir($_Dir_Name, $_Mode, true);

        parent::__construct( $_Dir_Name );

        $this->accessMode = $_Mode;
        $this->URI = $this->getRealPath();
    }

    public function traverse() {
        $_Args = func_get_args();

        if (! ($_Args[0] instanceof Closure)) {
            $_Mode = $_Args[0];
            $_Callback = $_Args[1];
        } else
            $_Callback = $_Args[0];

        foreach (
            new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->URI, FilesystemIterator::SKIP_DOTS),
                isset($_Mode) ? $_Mode : 0
            ) as
            $_Name => $_File
        ) {
            $_File->setFileClass('FS_File');

            if (false === call_user_func(
                $_Callback,  $_Name,  $_File->openFile('a+')
            ))
                break;
        }
        return $this;
    }
    public function delete() {
        $_URI = $this->traverse(2,  function ($_Name, $_File) {
            $_URI = $_File->URI;
            //  Let SplFileObject release the file,
            //  so that Internal Functions can use it.
            unset($_File);

            is_file($_URI) ? unlink($_URI) : rmdir($_URI);
        })->URI;

        unset($this);
        return rmdir($_URI);
    }
    public function copyTo($_Target) {
        $_Mode = $this->accessMode;

        if (! file_exists($_Target))  mkdir($_Target, $_Mode, true);

        $_Target = self::realPath($_Target);

        return  $this->traverse(2,  function ($_Name, $_File) use ($_Target, $_Mode) {
            $_Name = $_Target.DIRECTORY_SEPARATOR.$_Name;
            $_URI = $_File->URI;
            unset($_File);

            if ( is_dir($_URI) )
                return  mkdir($_Name, $_Mode, true);

            if (! file_exists($_Name))
                mkdir(dirname($_Name), $_Mode, true);
            copy($_URI, $_Name);
        });
    }
    public function moveTo($_Target) {
        $_Mode = $this->accessMode;
        $_URI = $this->URI;

        unset($this);
        rename($_URI, $_Target);

        return  new self($_Target, $_Mode);
    }
}

// ------------------------------
//
//    SQLite OOP Wrapper  v0.6
//
// ------------------------------

class SQL_Table {
    private $ownerBase;
    private $name;

    public function __construct($_DataBase, $_Name) {
        $this->ownerBase = $_DataBase;
        $this->name = $_Name;
    }

    public function rename($_Name) {
        return  $this->ownerBase->exec(
            "alter table {$this->name} rename to {$_Name}"
        );
    }
    public function addColumn($_Name,  $_Define = null) {
        if (is_string( $_Name ))
            $_Name = array("$_Name" => $_Define);

        foreach ($_Name  as  $_Key => $_Value)
            $this->ownerBase->exec(
                "alter table {$this->name} add column {$_Key} {$_Value}"
            );
    }
    public function insert($_Record) {
        $_Field_Name = array();  $_Field_Value = array();

        foreach ($_Record  as  $_Name => $_Value)
            if ($_Value !== null) {
                $_Field_Name[] = $_Name;
                $_Field_Value[] = is_string( $_Value )  ?
                    $this->ownerBase->quote($_Value)  :  $_Value;
            }
        return  $this->ownerBase->exec(join('', array(
            "insert into {$this->name} (",
            join(', ', $_Field_Name),
            ') values (',
            join(', ', $_Field_Value),
            ')'
        )));
    }
    public function update($_Where, $_Data) {
        $_Set_Data = array();

        foreach ($_Data  as  $_Name => $_Value)
            $_Set_Data[] = "{$_Name}=".(
                is_string( $_Value )  ?
                    $this->ownerBase->quote($_Value)  :  $_Value
            );
        return  $this->ownerBase->exec(join(' ', array(
            "update {$this->name} set",
            join(', ', $_Set_Data),
            "where {$_Where}"
        )));
    }
    public function delete($_Where) {
        return  $this->ownerBase->exec(
            "delete from {$this->name} where {$_Where}"
        );
    }
}

class SQLite {

    /* ----- SQL Statement Generation ----- */

    private static $statement = array(
        'select'  =>  array(
            'select',  'from',  'where',  'order by',  'limit',  'offset'
        )
    );
    private static $allTable = array(
        'select'  =>  'name, sql',
        'from'    =>  'SQLite_Master',
        'where'   =>  "type = 'table'"
    );

    private static function queryString($_SQL_Array) {
        $_SQL = array();

        foreach (self::$statement  as  $_Name => $_Key)
            if (isset( $_SQL_Array[$_Name] ))
                for ($i = 0;  $i < count($_Key);  $i++)
                    if (isset( $_SQL_Array[ $_Key[$i] ] )) {
                        $_SQL[] = $_Key[$i];
                        $_SQL[] = $_SQL_Array[ $_Key[$i] ];
                    }
        return  join(' ', $_SQL);
    }

    /* ----- Data Base Operation ----- */

    private $dataBase;
    private $table = array();

    public function __construct($_Base_Name) {
        if (! ($_Base_Name instanceof FS_Directory))
            new FS_Directory( pathinfo($_Base_Name, PATHINFO_DIRNAME) );
        try {
            $this->dataBase = new PDO("sqlite:{$_Base_Name}.db");
        } catch (PDOException $_Error) {
            echo '[Error - '.basename($_Base_Name).']  '.$_Error->getMessage();
        }
    }

    public function query(
        $_SQL_Array,  $_Fetch_Type = PDO::FETCH_OBJ,  $_Fetch_Args = null
    ) {
        $_Query = $this->dataBase->query( self::queryString( $_SQL_Array ) );

        if (! $_Query)
            $_Query = array();
        elseif (! $_Fetch_Args)
            $_Query = array_map(
                function ($_Object) {
                    return  get_object_vars( $_Object );
                },
                $_Query->fetchAll($_Fetch_Type)
            );
        else
            $_Query = $_Query->fetchAll($_Fetch_Type, $_Fetch_Args);

        return $_Query;
    }

    /* ----- Data Table Operation ----- */

    public function hasTable($_Name) {
        $_Statement = self::$allTable;
        $_Statement['where'] .= " and name = '{$_Name}'";

        return  !! count( $this->query($_Statement) );
    }

    private function addTable($_Name) {
        return  $this->table[$_Name] = new SQL_Table($this->dataBase, $_Name);
    }

    public function __get($_Name) {
        if ($_Name == 'error')
            return array(
                'code'  =>  $this->dataBase->errorCode(),
                'info'  =>  $this->dataBase->errorInfo(),
            );
        if (isset( $this->table[$_Name] ))
            return $this->table[$_Name];

        if ($this->hasTable( $_Name ))
            return $this->addTable($_Name);
    }

    public function createTable($_Name, $_Column_Define) {
        $_Define_Array = array();

        foreach ($_Column_Define  as  $_Key => $_Define)
            $_Define_Array[] = "{$_Key} {$_Define}";

        $_Result = $this->dataBase->exec(
            "create Table if not exist {$_Name} (\n    "  .
            join(",\n    ", $_Define_Array)  .
            "\n)"
        );
        return  is_numeric($_Result) ? (!! $this->addTable($_Name)) : false;
    }

    public function dropTable($_Name) {
        if (is_numeric(
            $this->dataBase->exec("drop Table if exist {$_Name}")
        )) {
            unset( $this->table[$_Name] );
            return true;
        }
    }
}

// ----------------------------------------
//
//    Simple HTTP Server & Client  v1.0
//
// ----------------------------------------

class HTTP_Cookie {
    private $data = array();

    public function __construct($_Cookie) {
        if (is_array( $_Cookie ))
            return  $this->data = $_Cookie;

        if (function_exists('http_parse_cookie'))
            return  $this->data = http_parse_cookie($_Cookie);

        $_Cookie = explode(';', $_Cookie, 2);

        foreach ($_Cookie as $_Item) {
            $_Item = explode('=', $_Item, 2);
            $this->data[trim( $_Item[0] )] = trim( $_Item[1] );
        }
    }
    public function __toString() {
        if (function_exists('http_build_cookie'))
            return  http_build_cookie($this->data);

        $_Cookie = array();

        foreach ($this->data  as  $_Key => $_Value)
            $_Cookie[] = "{$_Key}={$_Value}";

        return  join('; ', $_Cookie);
    }

    public function get($_Name) {
        if (isset( $this->data[$_Name] ))  return $this->data[$_Name];
    }
    public function set($_Name, $_Value) {
        $this->data[$_Name] = $_Value;
    }
}

class HTTP_Request {
    private static $More_Header = array(
        'REMOTE_ADDR', 'REQUEST_METHOD', 'CONTENT_TYPE', 'CONTENT_LENGTH'
    );
    private static function getHeaders() {
        $_Header = array();  $_Take = false;

        foreach ($_SERVER  as  $_Key => $_Value) {
            if (substr($_Key, 0, 5) == 'HTTP_') {
                $_Key = substr($_Key, 5);
                $_Take = true;
            }
            if ($_Take  ||  in_array($_Key, self::$More_Header)) {
                $_Header[str_replace(' ', '-', ucwords(
                    strtolower( str_replace('_', ' ', $_Key) )
                ))] = $_Value;
                $_Take = false;
            }
        }
        return $_Header;
    }

    private static $IPA_Header = array(
        'Client-Ip',  'X-Forwarded-For',  'Remote-Addr'
    );
    private static function getIPA($_Header) {
        foreach (self::$IPA_Header as $_Key) {
            if (empty( $_Header[$_Key] ))  continue;

            $_IPA = explode(',', $_Header[$_Key]);
            return  trim( $_IPA[0] );
        }
    }
    private static function getData($_Method) {
        if ($_Method == 'GET')  return $_GET;
        if ($_Method == 'POST')  return $_POST;

        parse_str(file_get_contents('php://input'), $_Args);

        if ($_Method == 'DELETE') {
            global $_DELETE;
            $_DELETE = $_Args;
        } elseif ($_Method == 'PUT') {
            global $_PUT;
            $_PUT = $_Args;
        }
        return $_Args;
    }

    public $header;
    public $method;
    public $IPAddress;
    public $cookie;
    public $data;

    public function __construct() {
        $_Header = $this->header = self::getHeaders();

        $this->method = $this->header['Request-Method'];

        $this->IPAddress = self::getIPA( $this->header );

        if (isset( $_Header['Cookie'] ))
            $this->cookie = new HTTP_Cookie( $_Header['Cookie'] );

        $this->data = self::getData( $this->method );
    }
}

class HTTP_Response {
    public static $statusCode = array(
        '100'  =>  'Continue',
        '101'  =>  'Switching Protocols',
        '200'  =>  'OK',
        '201'  =>  'Created',
        '202'  =>  'Accepted',
        '203'  =>  'Non-authoritative Information',
        '204'  =>  'No Content',
        '205'  =>  'Reset Content',
        '206'  =>  'Partial Content',
        '300'  =>  'Multiple Choices',
        '301'  =>  'Moved Permanently',
        '302'  =>  'Found',
        '303'  =>  'See Other',
        '304'  =>  'Not Modified',
        '305'  =>  'Use Proxy',
        '306'  =>  'Unused',
        '307'  =>  'Temporary Redirect',
        '400'  =>  'Bad Request',
        '401'  =>  'Unauthorized',
        '402'  =>  'Payment Required',
        '403'  =>  'Forbidden',
        '404'  =>  'Not Found',
        '405'  =>  'Method Not Allowed',
        '406'  =>  'Not Acceptable',
        '407'  =>  'Proxy Authentication Required',
        '408'  =>  'Request Timeout',
        '409'  =>  'Conflict',
        '410'  =>  'Gone',
        '411'  =>  'Length Required',
        '412'  =>  'Precondition Failed',
        '413'  =>  'Request Entity Too Large',
        '414'  =>  'Request-url Too Long',
        '415'  =>  'Unsupported Media Type',
        '416'  =>  '',
        '417'  =>  'Expectation Failed',
        '428'  =>  'Precondition Required',
        '429'  =>  'Too Many Requests',
        '431'  =>  'Request Header Fields Too Large',
        '500'  =>  'Internal Server Error',
        '501'  =>  'Not Implemented',
        '502'  =>  'Bad Gateway',
        '503'  =>  'Service Unavailable',
        '504'  =>  'Gateway Timeout',
        '505'  =>  'HTTP Version Not Supported',
        '511'  =>  'Network Authentication Required'
    );
    private static function getFriendlyHeaders($_Header_Array) {
        $_Header = array();

        foreach ($_Header_Array as $_Str) {
            $_Item = explode(':', $_Str, 2);

            if (isset( $_Item[1] )) {
                $_Header[trim( $_Item[0] )] = trim( $_Item[1] );
                continue;
            }
            if ( preg_match('#HTTP/[\d\.]+\s+(\d+)#', $_Str, $_Num) )
                $_Header['Response-Code'] = intval( $_Num[1] );
            else
                $_Header[] = $_Str;
        }
        return $_Header;
    }

    public  $header;
    private $data;
    private $dataJSON;

    public function __construct($_Header, $_Data) {
        $this->header = isset($_Header[0]) ? self::getFriendlyHeaders($_Header) : $_Header;

        if (is_string( $_Data )) {
            $this->data = $_Data;
            $this->dataJSON = json_decode($_Data, true);
        } else {
            $this->data = json_encode($_Data);
            $this->dataJSON = $_Data;
        }
    }
    public function __get($_Key) {
        return $this->{$_Key};
    }
    public function __set($_Key, $_Value) {
        switch ($_Key) {
            case 'data':        {
                $this->data = $_Value;
                $this->dataJSON = json_decode($_Value, true);
                break;
            }
            case 'dataJSON':    {
                $this->data = json_encode($_Value);
                $this->dataJSON = $_Value;
            }
        }
    }
}

class HTTPServer {
    public $request;
    private $onStart;

    public function setStatus($_Code) {
        $_Message = isset( HTTP_Response::$statusCode[$_Code] )  ?
            HTTP_Response::$statusCode[$_Code]  :  '';

        header("HTTP/1.1 {$_Code} {$_Message}");

        if ($_Code == 429)
            header('Retry-After: '.func_get_arg(1));
    }

    public function setHeader($_Head,  $_Value = null) {
        if (! is_array($_Head))
            $_Head = array("{$_Head}" => $_Value);

        if (! isset( $_Head['X-Powered-By'] ))
            $_Head['X-Powered-By'] = '';

        $_XPB = $_Head['X-Powered-By'];

        if (stripos($_XPB, 'EasyLibs')  ===  false) {
            $_XPB .= '; EasyLibs.php/1.6';
            $_Head['X-Powered-By'] = trim(preg_replace('/;\s*;/', ';', $_XPB),  ';');
        }

        if (isset( $_Head['WWW-Authenticate'] ))
            $this->setStatus(401);
        else {
            $this->setStatus(
                isset( $_Head['Response-Code'] )  ?  $_Head['Response-Code']  :  200
            );
            unset( $_Head['Response-Code'] );
        }
        foreach ($_Head  as  $_Key => $_String)
            header("{$_Key}: {$_String}");

        return $this;
    }

    public function __construct($_xDomain = false,  $_onStart = null) {
        $this->request = new HTTP_Request();
        $this->onStart = $_onStart;

        $_Header = $this->request->header;

        if ((! $_xDomain)  ||  ($this->request->method != 'OPTIONS'))
            return;

        $_AC = 'Access-Control';
        $_ACA = "{$_AC}-Allow";    $_ACR = "{$_AC}-Request";

        $this->setHeader(array(
            'Response-Code'          =>  204,
            "{$_ACA}-Origin"         =>
                isset( $_Header['Origin'] )  ?  $_Header['Origin']  :  '*',
            "{$_ACA}-Methods"        =>
                isset( $_Header["{$_ACR}-Methods"] )  ?
                    $_Header["{$_ACR}-Methods"]  :  'GET, POST, PUT, DELETE',
            "{$_ACA}-Headers"        =>
                isset( $_Header["{$_ACR}-Headers"] )  ?
                    $_Header["{$_ACR}-Headers"]  :  'X-Requested-With',
            "{$_ACA}-Credentials"    =>  true,
            "{$_AC}-Expose-Headers"  =>  'X-Powered-By',
            "{$_AC}-Max-Age"         =>  300
        ));
        exit;
    }

    public function setCookie($_Key,  $_Value = null) {
        if (! is_array($_Key))
            $_Key = array("{$_Key}" => $_Value);

        $this->setHeader('Set-Cookie',  new HTTP_Cookie($_Key));
    }
    //  ToDo --- http://php.net/manual/zh/features.http-auth.php
    public function auth($_Tips = 'Auth Needed',  $_Check_Handle) {
        if (isset( $_SERVER['PHP_AUTH_USER'] )  &&  isset( $_SERVER['PHP_AUTH_PW'] )) {
            if (false !== call_user_func(
                $_Check_Handle,  $_SERVER['PHP_AUTH_USER'],  $_SERVER['PHP_AUTH_PW'],  $this
            ))
                return;
            else
                $this->setStatus(403);
        } else
            $this->setHeader('WWW-Authenticate', "Basic realm=\"{$_Tips}\"");

        exit;
    }
    public function download($_File) {
        $this->setHeader(array(
            'Content-Type'               =>  'application/octet-stream',
            'Content-Disposition'        =>  'attachment; filename="'.basename($_File).'"',
            'Content-Transfer-Encoding'  =>  'binary'
        ));
        readfile($_File);
        exit;
    }
    public function send($_Data,  $_Header = null) {
        if ($_Data instanceof HTTP_Response) {
            $_Header = $_Data->header;
            $_Data = $_Data->data;
        }
        if ($_Header)  $this->setHeader($_Header);
        echo  is_string($_Data) ? $_Data : json_encode($_Data);
        ob_flush() & flush();
    }
    public function redirect($_URL,  $_Second = 0,  $_Tips = '') {
        if ($_Second)
            $this->send($_Tips, array(
                'Refresh'  =>  "{$_Second}; url={$_URL}"
            ));
        else
            $this->setHeader('Location', $_URL);

        exit;
    }

    public function on($_Method, $_Path, $_Callback) {
        $_rPath = $_SERVER['PATH_INFO'];
        if (
            ($this->request->method == strtoupper($_Method))  &&
            (stripos($_rPath, $_Path)  !==  false)
        ) {
            $_rPath = explode('/',  trim($_rPath, '/'));

            $_Return = isset( $this->onStart )  ?
                call_user_func($this->onStart, $_rPath, $this->request)  :  null;

            if ($_Return !== false)
                $_Return = call_user_func_array($_Callback, array(
                    $_rPath,  $this->request,  $_Return
                ));
            if (is_array( $_Return ))
                $this->send($_Return['data'], $_Return['header']);
            else
                $this->send(
                     is_string($_Return) ? $_Return : json_encode($_Return)
                );
        }
        return $this;
    }
}

class HTTPClient {
    private static function setRequestHeaders($_Header_Array) {
        $_Header = array();

        foreach ($_Header_Array  as  $_Key => $_Value)
            $_Header[] = "$_Key: $_Value";

        if (
            empty( $_Header_Array['Content-Type'] )  &&
            isset( $_Header_Array['Request-Method'] )  &&
            (strtoupper( $_Header_Array['Request-Method'] )  ==  'POST')
        )
            $_Header[] = 'Content-Type: application/x-www-form-urlencoded';

        return  join("\r\n", $_Header);
    }

    private function request($_Method,  $_URL,  $_Header,  $_Data = array()) {
        $_Response = @file_get_contents($_URL, false, stream_context_create(array(
            'http' => array(
                'method'   =>  $_Method,
                'header'   =>  self::setRequestHeaders($_Header),
                'content'  =>  http_build_query($_Data)
            )
        )));
        return  ($_Response !== false)  ?
            new HTTP_Response($http_response_header, $_Response)  :  $_Response;
    }

    public function head($_URL) {
        stream_context_set_default(array(
            'http' => array(
                'method' => 'HEAD'
            )
        ));
        return  get_headers($_URL, 1);
    }
    public function get($_URL,  $_Header = array()) {
        return  $this->request('GET', $_URL, $_Header);
    }
    //  ToDo --- http://php.net/manual/zh/features.file-upload.post-method.php
    public function post($_URL,  $_Data,  $_Header = array()) {
        return  $this->request('POST', $_URL, $_Data, $_Header);
    }
    public function delete($_URL,  $_Data,  $_Header = array()) {
        return  $this->request('DELETE', $_URL, $_Data, $_Header);
    }
    public function put($_URL,  $_Data,  $_Header = array()) {
        return  $this->request('PUT', $_URL, $_Data, $_Header);
    }
}

// ------------------------------
//
//    HTML Converter  v0.3
//
// ------------------------------

require_once('phpQuery.php');


abstract class HTMLConverter {
    public static function getURLDomain($_URL) {
        $_URL = explode('/', $_URL, 4);
        $_URL[0] = '';
        return  join('/',  array_slice($_URL, 0, 3));
    }

    private static function getDOMCharSet($_DOM) {
        $_Meta = $_DOM['meta[http-equiv="Content-Type"]'];

        if ( $_Meta->size() ) {
            preg_match(
                '/charset=([^;]+)/i',  $_Meta->attr('content'),  $_CharSet
            );
            return $_CharSet[1];
        }
        return $_DOM['meta[charset]']->attr('charset');
    }

    public $URL;
    public $domain;
    public $CharSet;
    public $DOM;
    public $content;
    public $title;
    public $rule;

    public function __construct($_URL,  $_Selector = null,  $_Rule) {
        if (substr(trim($_URL), 0, 1) != '<') {
            $this->URL = $_URL;
            $this->domain = self::getURLDomain($_URL);

            $this->DOM = phpQuery::newDocumentFile($_URL);
            $this->content = $this->DOM['body'];
        } else {
            $this->DOM = phpQuery::newDocumentHTML($_URL);
            $this->content = $this->DOM;
        }
        $this->CharSet = self::getDOMCharSet( $this->DOM );

        if (is_string( $_Selector )) {
            $_DOM = $this->content[ $_Selector ];
            if ( $_DOM->size() )  $this->content = $_DOM;
        }

        $_Title = $this->content->filter('h1');
        $_Title = $_Title->size() ? $_Title : $this->content['h1'];
        $this->title = $_Title->text();

        $this->rule = $_Rule;
    }

    private $link = array();

    private function innerLink($_URL) {
        $_URL = preg_replace('/^\/([^\/])/', '$1', $_URL);
        $_Host = parse_url($_URL, PHP_URL_HOST);

        if (empty( $_Host ))
            return  parse_url($this->URL, PHP_URL_SCHEME) .
                ":{$this->domain}/{$_URL}";

        if (self::getURLDomain($_URL) == $this->domain)
            return $_URL;
    }

    public function __get($_Name) {
        if ($_Name != 'link')  return;

        if (empty( $this->link )) {
            $this->link = array(
                'inner'  =>  array(),
                'outer'  =>  array()
            );
            foreach ($this->content['a[href]'] as $_Link) {
                $_HREF = $_Link->getAttribute('href');
                if ($_HREF[0] == '#')  continue;

                $_InnerURL = $this->innerLink($_HREF);

                if ($_InnerURL) {
                    $_Link->setAttribute('href', $_InnerURL);
                    array_push($this->link['inner'], $_Link);
                } else
                    array_push($this->link['outer'], $_Link);
            }
        }
        return $this->link;
    }

    public function convert() {
        $_Target = array();

        foreach ($this->rule  as  $_Selector => $_Callback)
            foreach ($this->content[$_Selector] as $_DOM) {/*
                array_push($_Target, $_DOM);
            }
        usort($_Target,  function ($_A, $_B) {
            return  pq($_A)->parents()->size() - pq($_B)->parents()->size();
        });

        foreach ($_Target as $_DOM) {*/
            $_DOM_ = pq($_DOM);/*

            foreach ($this->rule  as  $_Selector => $_Callback)
                if ($_DOM_->is( $_Selector )) {*/
                    $_DOM_->html(call_user_func(
                        $_Callback,  trim( $_DOM_->html() ),  $_DOM_
                    ));/*
                    break;
                }*/
        }
        return  trim( $this->content->text() );
    }
}

class HTML_MarkDown extends HTMLConverter {
    public function getTitleAttr($_DOM) {
        $_Title = ' "' . $_DOM->attr('title') . '"';
        return  (count($_Title) > 3)  ?  $_Title  :  '';
    }

    public function __construct($_URL,  $_Selector = null,  $_Rule = array()) {
        $_This = $this;

        parent::__construct($_URL, $_Selector, array_merge(array(
            'h1, h2, h3, h4, h5, h6'  =>  function ($_HTML, $_DOM) {
                return  "\n\n" . str_repeat('#', $_DOM->get(0)->tagName[1]) .
                    " {$_HTML}";
            },
            'em'                      =>  function ($_HTML) {
                return  " *{$_HTML}*";
            },
            'b, strong'               =>  function ($_HTML) {
                return  " **{$_HTML}**";
            },
            'del'                     =>  function ($_HTML) {
                return  " ~~{$_HTML}~~";
            },
            'a[href]'                 =>  function ($_HTML, $_DOM) use ($_This) {
                return  "[{$_HTML}](" . $_DOM->attr('href') .
                    $_This->getTitleAttr($_DOM) . ')';
            },
            'ul > li'                 =>  function ($_HTML) {
                return  "\n - {$_HTML}";
            },
            'ol > li'                 =>  function ($_HTML, $_DOM) {
                return  "\n " . ($_DOM->prevAll()->size() + 1) . ". {$_HTML}";
            },
            'img'                     =>  function ($_HTML, $_DOM) use ($_This) {
                return  '![' . $_DOM->attr('alt') . '](' . $_DOM->attr('src') .
                    $_This->getTitleAttr($_DOM) . ')';
            },
            'hr'                      =>  function ($_HTML, $_DOM) {
                return  "\n\n" . (
                    preg_match(
                        '/page-break-after:\s*always/i', $_DOM->attr('style')
                    ) ?
                        '[========]' : '---'
                ) . "\n\n";
            },
            'p'                       =>  function ($_HTML) {
                return  "\n\n{$_HTML}\n\n";
            },
            'pre'                     =>  function ($_HTML) {
                return  "\n> {$_HTML}";
            },
            'code'                    =>  function ($_HTML) {
                return  " `{$_HTML}` ";
            },
            'pre > code'              =>  function ($_HTML) {
                return  "\n```\n{$_HTML}\n```\n";
            },
            'table'                   =>  function ($_HTML) {
                return  "\n\n{$_HTML}";
            },
            'tr'                      =>  function ($_HTML, $_DOM) {
                $_Code = "\n" . trim(
                    preg_replace('/(\s*<.+?>\s*)+/', ' | ', $_HTML)
                );
                $_TR = $_DOM->parents('table')->eq(0)->find('tr');

                if (! $_TR->index($_DOM))
                    $_Code .= "\n" . trim(join(' | ', array_fill(
                        0,  $_TR->eq(1)->children()->size() + 2,  '-----'
                    )), '- ');

                return $_Code;
            }
        ), $_Rule));
    }
}
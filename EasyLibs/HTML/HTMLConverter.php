<?php

// ------------------------------
//
//    HTML Converter Core  v0.2
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

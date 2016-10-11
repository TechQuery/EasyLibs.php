<?php

// ------------------------------
//
//    HTML to MarkDown  v0.1
//
// ------------------------------


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

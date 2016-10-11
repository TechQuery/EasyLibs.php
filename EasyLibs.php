<?php
//
//                >>>  EasyLibs.php  <<<
//
//
//      [Version]    v2.4  (2016-10-12)  Stable
//
//      [Require]    PHP v5.3+
//
//      [Usage]      A Light-weight PHP Class Library
//                   without PHP Extensions.
//
//
//          (C)2015-2016    shiy2008@gmail.com
//


spl_autoload_register(function ($_ClassName) {

    $_Path = array_reduce(
        array(
            '/^(FS)_/', '/(SQL)/', '/^(HTTP)/', '/^(HTML)/'
        ),
        function  ($_Prev, $_Item) use ($_ClassName) {
            return  ((! $_Prev)  &&  preg_match($_Item, $_ClassName, $_Path))  ?
                $_Path[1]  :  $_Prev;
        }
    );

    require_once("EasyLibs/$_Path/$_ClassName.php");

},  true,  true);

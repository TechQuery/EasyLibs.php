<?php

// -----------------------------------
//
//    File Directory Object  v0.3
//
// -----------------------------------


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

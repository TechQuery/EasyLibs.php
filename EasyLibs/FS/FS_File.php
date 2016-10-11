<?php

// -------------------------
//
//    File Object  v0.1
//
// -------------------------


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

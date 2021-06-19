<?php


namespace libraries;


class FileEdit
{
    protected $imgArr = [];
    protected $directory;

    public function addFile($directory = false) {

        if(empty($directory)) $this->directory = $_SERVER['DOCUMENT_ROOT'] . PATH . UPLOAD_DIR;
            else $this->directory = $directory;

        foreach ($_FILES as $key => $file) {


            if(is_array($file['name'])) {

                $fileArr = [];

                for($i = 0; $i < count($file['name']); $i++) {

                    if(!empty($file['name'][$i])) {
                        $fileArr['name'] = $file['name'][$i];
                        $fileArr['type'] = $file['type'][$i];
                        $fileArr['tmp_name'] = $file['tmp_name'][$i];
                        $fileArr['error'] = $file['error'][$i];
                        $fileArr['size'] = $file['size'][$i];

                        $resName = $this->createFile($fileArr);

                        if(!empty($resName)) $this->imgArr[$key][] = $resName;

                    }
                }

            } else {


                if(!empty($file['name'])) {

                    $resName = $this->createFile($file);

                    if(!empty($resName)) $this->imgArr[$key] = $resName;

                }

            }

        }
    // Геттер
        return $this->getFiles();
    }

    protected function createFile($file) {

//        $file['name'] =   'file.1.zip';

        $fileNameArr = explode('.', $file['name']);
        $ext = end($fileNameArr);
        unset ($fileNameArr[array_key_last($fileNameArr)]);
        $fileName = implode('.', $fileNameArr);

        $fileName = (new TextModify())->translit($fileName);

        $fileName = $this->checkFile($fileName, $ext);

        $fileFullName = $this->directory . $fileName;

        if ($this->uploadFile($file['tmp_name'], $fileFullName))
            return $fileName;

        return false;

    }


    protected function uploadFile($tmpName, $dest) {

        if(move_uploaded_file($tmpName, $dest)) return true;

        return false;

    }

    protected function checkFile($fileName, $ext, $fileLastName = '') {
        if(!file_exists($this->directory . $fileName . $fileLastName . '.' . $ext))
            return $fileName . $fileLastName . '.' . $ext;
        return $this->checkFile($fileName, $ext,  '_' . hash('crc32', time() . rand(0, 100)));
    }

    public function getFiles() {

        return $this->imgArr;

    }
}
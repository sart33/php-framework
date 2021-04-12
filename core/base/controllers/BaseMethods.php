<?php


namespace core\base\controllers;


use core\base\settings\Settings;

trait BaseMethods
{



    protected function clearStr($str) {

        if (is_array($str)) {
            foreach ($str as $key => $item) $str[$key] = trim(strip_tags($item));
            return $str;
        } else {
            return trim(strip_tags($str));
        }
    }

    protected function clearNum($num) {
//        if(is_int($num)) echo 'int';
//        elseif (is_float($num)) echo 'float';
//        else echo 'string';
        return $num * 1;
    }

    protected function isPost() {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    protected function isAjax() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }

    protected function redirect($http = false, $code = false) {
        if($code) {
            $codes = ['301' => 'HTTP/1.1 301 Move Permanently'];

            if($codes[$code]) header($codes[$code]);
        }
        if($http) $redirect = $http;
        else $redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : PATH;

        header('Location: ' . $redirect );


    }

    protected function writeLog($message, $file = 'log.txt', $event = 'Fault') {

        $dateTime = new \DateTime();

        $str = $event . ': ' . $dateTime->format('d-m-Y G:i:s') . ' - ' . $message . "\r\n";
        // Пишем данные в файл. Конкретно - дописываем в конец файла.
        file_put_contents('log/' . $file, $str, FILE_APPEND);
    }

    protected function createRadio($settings = false) {
        if(!$settings) $settings = Settings::instance();
        $radio = $settings->get('radio');

        if($radio) {
            foreach ($this->columns as $name => $item) {
                if($radio[$name]) {
                    $this->foreignData[$name] = $radio[$name];
                }
            }
        }
    }

}
<?php

namespace core\base\controllers;

use core\base\settings\Settings;

class RouteController
{

    static private $_instance;


    protected $routes;
    protected $controller;
    protected $inputMethod;
    protected $outputMethod;
    protected $parameters;

    /*** Работаем в необъектном контексте, а в статическом. $this-> не доступен потому что $this-> ссылка на объект   ***/
    static public function getInstance()
{
     if(self::$_instance instanceof self)
     {
         return self::$_instance;

     }
     return self::$_instance = new self;
}
/***блокируем создание объекта из вне ***/

    /***блокируем клонирование объекта из вне ***/

    private function __clone()
    {
    }

    private function __construct()
    {
        $s = Settings::get('routes');

        exit();
    }


}

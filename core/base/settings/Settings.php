<?php


namespace core\base\settings;
//use core\base\settings\ShopSettings;


class Settings
{
    static private $_instance;

    private $routes = [
        'admin' => [
            'alias' => 'admin',
            'path' => 'core/admin/controllers/',
            'hrUrl' => false
        ],
        'settings' => [
            'path' => 'core/base/settings/'
        ],
        'plugins' => [
            'path' => 'core/plugins/',
            'hrUrl' => false
        ],
        'user' => [
            'path' => 'core/user/controllers/',
            'hrUrl' => true,
            'routes' => [

            ]

        ],
        'default' => [
            'controller' => 'IndexController',
            'inputMethod' => 'inputData',
            'outputMethod' => 'outputData'
        ]
    ];

    private $templateArr = [
        'text' => ['name', 'phone', 'adress'],
        'textarea' => ['content', 'keywords']
    ];

    private function __clone()
    {
    }
/*** * singleton ***/
    static public function  instance() {
        if(self::$_instance instanceof self) {
            return self::$_instance;
        }
            return self::$_instance = new self;
        }

        static public function get($property) {
            return self::instance()->$property;
        }
/*** ***/
        private function __construct()
        {
        }
}
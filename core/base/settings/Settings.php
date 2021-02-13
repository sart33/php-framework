<?php


namespace core\base\settings;
use core\base\settings\ShopSettings;


class Settings
{
    static private $_instance;

    private $routes = [
        'admin' => [
            'alias' => 'admin',
            'path' => 'core/admin/controllers/',
            'hrUrl' => false,
            'routes' => [

            ]
        ],
        'settings' => [
            'path' => 'core/base/settings/'
        ],
        'plugins' => [
            'path' => 'core/plugins/',
            'hrUrl' => false,
            'dir' => false
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

    private $lalala = 'lalala';

    private function __construct()
    {
    }

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


        /*** склейка массивов***/
        public function clueProperties($class){
            $baseProperties = [];
            foreach ($this as $name => $item) {
                $property = $class::get($name);
                $baseProperties[$name] = $property;
                if(is_array($property) && is_array($item)) {

                    $baseProperties[$name] = $this->arrayMergeRecursive($this->$name, $property);
                    continue;
                }
                if(!$property) $baseProperties[$name] = $this->$name;
            }
            return $baseProperties;
        }

    /***  Рекурсивный метод склейки массивов, массивы с одинаковыми числовыми ключами - добавляет, с текстовыми- перезаписывает  ***/
    public function arrayMergeRecursive() {

        $arrays = func_get_args();

        $base = array_shift($arrays);

        foreach ($arrays as $array) {
            foreach ($array as $key => $value) {
                if(is_array($value) && is_array($base[$key])) {
                    $base[$key] = $this->arrayMergeRecursive($base[$key], $value);
                } else {
                    if(is_int($key)) {
                        if(!in_array($value, $base)) array_push($base,  $value);
                        continue;
                    }
                    $base[$key] = $value;
                }

            }

        }
        return $base;
    }
}
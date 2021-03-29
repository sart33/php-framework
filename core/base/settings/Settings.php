<?php


namespace core\base\settings;
use core\base\controllers\Singleton;
use core\base\settings\ShopSettings;


class Settings
{
//    static private $_instance; - перекочевало в трейт
//v3 'path' => 'core/admin/controllers/'.... - Эти пути являются одновременно и пространствами имен

    use Singleton;

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
        ],
    ];

    private $defaultTable = 'teachers';

    private $templateArr = [
        'text' => ['name', 'phone', 'adress'],
        'textarea' => ['content', 'keywords']
    ];

    private $lalala = 'lalala';

// Клон и констракт тож удалили

/*** * singleton ***/
    // Удалили - перекочевал в трейт


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
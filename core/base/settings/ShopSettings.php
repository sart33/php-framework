<?php


namespace core\base\settings;
use core\base\controllers\Singleton;
use core\base\settings\Settings;

class ShopSettings
{

    use Singleton;


    private $baseSettings;

    private $routes = [
        'plugins' => [
            'path' => 'core/plugins/',
            'hrUrl' => false,
            'dir' => 'controllers',
            'routes' => [

            ],
        ],
    ];
    private $templateArr = [
        'text' => ['price', 'short'],
        'textarea' => ['goods_content']
    ];


    /*** * singleton ***/

    static public function get($property) {
        return self::getInstance()->$property;
    }

    static private function  getInstance() {
        if(self::$_instance instanceof self) {
            return self::$_instance;
        }
        /***   метод склейки   ****/
        self::instance()->baseSettings = Settings::instance();
        $baseProperties = self::$_instance->baseSettings->clueProperties(get_class());
        // И записываем через setProperty
        self::$_instance->setProperty($baseProperties);
        return self::$_instance;
    }


    protected function setProperty($properties){
        if(isset($properties)) {
            foreach ($properties as $name => $property) {
//                $this->($a) - Если без значка доллара - то обращаемся к свойству “a” по имени. Если с $ - то обращаемся к свойству, которое хранится внутри переменной “а”. Со знаком доллара - часто в цикле foreach, где имена переменным даются внутри этого цикла.
//                В этом контексте это равносильно напр: $this->templateArr
                $this->$name = $property;
            }
        }
    }
}
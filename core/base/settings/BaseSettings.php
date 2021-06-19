<?php


namespace core\base\settings;


use core\base\controllers\Singleton;
use core\base\settings\Settings;

trait BaseSettings
{

    use Singleton {
        instance as SingletonInstance;
    }


    private $baseSettings;

    static public function get($property) {
        return self::instance()->$property;
    }

    static public function instance() {
        if(self::$_instance instanceof self) {
            return self::$_instance;
        }
        /***   метод склейки   ****/
        self::SingletonInstance()->baseSettings = Settings::instance();
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
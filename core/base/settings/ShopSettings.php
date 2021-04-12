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
                'karree' => 'OffersKarree/getImport'


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
        self::$_instance->setProperty($baseProperties);
        return self::$_instance;
    }


    protected function setProperty($properties){
        if($properties) {
            foreach ($properties as $name => $property) {
                $this->$name = $property;
            }
        }
    }
}
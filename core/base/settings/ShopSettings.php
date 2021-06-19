<?php


namespace core\base\settings;
use core\base\settings\BaseSettings;

class ShopSettings
{

    use BaseSettings;


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


}
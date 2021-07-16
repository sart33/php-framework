<?php


namespace core\base\controllers;


class BaseRoute
{
    use Singleton, BaseMethods;

    public static function routeDirection() {

        if(self::instance()->isAjax() === true) {
            exit((new BaseAjax())->route());
        }

//        exit((new BaseAjax())->route());
        RouteController::instance()->route();
    }
}
<?php
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

define('VG_ACCESS', true);
header('Content-Type:text/html;charset=utf8');
session_start();
require_once 'config.php';
require_once 'core/base/settings/internal_settings.php';

use core\base\exceptions\RouteException;
use core\base\controllers\RouteController;

try {
    /*** Вызов статического метода у класса  RouteController ***/
    /*** Поскольку вызов идет вне класса, то модификатор доступа у route() - должен быть "Паблик"***/
   RouteController::getInstance()->route();
}
/****Есл исключение класса RouteException будет сгенерировано посредством создания объекта А который начнет созд. др.
 * объекты и т.д. То сгенерированное в этой цепочке throw RouteException - прилетит в этот catch . throw ищет ближайший catch этого класса. Если начали ч/з блок try  ***/

catch (RouteException $e) {
    exit($e->getMessage());

}








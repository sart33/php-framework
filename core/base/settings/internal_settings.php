<?php



defined('VG_ACCESS') or die('Access denied');

const TEMPLATE = 'templates/default/';
const ADMIN_TEMPLATE = 'core/admin/views/';
const UPLOAD_DIR = 'userfiles/';

const COOKIE_VERSION = '1.0.0';
const CRYPT_KEY = '';
/*** время бездействия для администратора***/
const COOKIE_TIME = 60;
const BLOCK_TIME = 3;
/***постраничная навигация ***/
/***количество позиций***/
const QTY = 8;
/***количество ссылок на страницы ***/
const QTY_LINKS  = 3;

const ADMIN_CSS_JS = [
    'styles' => ['css/main.css'],
    'scripts' => []
];

const USER_CSS_JS = [
    'styles' => ['css/style.css'],
    'scripts' => ['js/style.js']
];
use core\base\exceptions\RouteException;
function autoloadMainClasses($className) {
    $className = str_replace('\\', '/', $className);

    if (!@include_once $className . '.php') {
        throw new RouteException('Invalid filename for include - ' . $className);
    }

}
spl_autoload_register('autoloadMainClasses');
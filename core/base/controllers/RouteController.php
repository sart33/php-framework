<?php

namespace core\base\controllers;
use core\base\exceptions\RouteException;
use core\base\settings\ShopSettings;
use core\base\settings\Settings;

class RouteController extends BaseController
{

    static private $_instance;



    protected $routes;


    /*** Работаем в необъектном контексте, а в статическом. $this-> не доступен потому что $this-> ссылка на объект   ***/
    static public function getInstance()
{
     if(self::$_instance instanceof self)
     {
         return self::$_instance;

     }
     return self::$_instance = new self;
}

    /***блокируем клонирование объекта из вне */
    private function __clone()
    {
    }

        /***блокируем создание объекта из вне */
    private function __construct()
    {
        $adress_str = $_SERVER['REQUEST_URI'];
        /***Если символ / стаит в конце строки - мы должны перенаправить пользователя на ссылку без этого символа */
        if(strrpos($adress_str, '/') === strlen($adress_str) - 1 && strrpos($adress_str, '/') !== 0) {
           // $this это псевдо переменная, когда методы вызываются в контексте объекта, ссылка на вызывающий объект

            $this->redirect(rtrim($adress_str, '/'), 301);
        }
        //В пременную $path мы сохранили обрезанную строку в которой содержится имя выполнения скрипта.
         // Если константа PATH не совпадет с именем скрипта - не подгрузятся ни контроллеры ни изображения, - ничего. В этой ситуации нет смысла продолжать другие действия   **
        //
        $path = substr($_SERVER['PHP_SELF'], 0, strpos($_SERVER['PHP_SELF'], 'index.php'));
        //Поетому **
        if ($path === PATH) {

            //Сохраним маршруты в наше свойство routes
        $this->routes = Settings::get('routes');
        //если свойства routes - не получены - мы не сможем продолжать выполнение скрипта. Генерируем исключение

        if(!$this->routes) throw new RouteException('the site is under maintenance');
        //Если скрипт выбрасывает исключение, скрипт автоматически переходит в index.php в блок catch() и работа скрипта завершается

            /*** ПРОВЕРЯЕМ, НЕ В АДМИНКУ ЛИ ПЫТАЕТСЯ ПОПАСТЬ ЧЕЛОВЕК ОТПРАВИВШИЙ ЗАПРОС */

            $url = explode('/', substr($adress_str, strlen(PATH)));
//            if(strpos($adress_str, $this->routes['admin']['alias']) === strlen(PATH)) {}

            //Проверяем на соответствие адрестной строки до первого слеша алиасу пути админа
            if($url[0] && $url[0] === $this->routes['admin']['alias']) {
                /*** Пути админки */
                //Выкидываем admin из массива и сдвигаем cлед элемент массива на ключ "0"
                array_shift($url);
                // Если $url[0]- существует, то существует ли дирректориияконкретно для этого плагина
                if($url[0] && is_dir($_SERVER['DOCUMENT_ROOT'] . PATH . $this->routes['plugins']['path'] . $url[0] )) {
                    //Если выполнится - начинаем делать что-то для плагина,  ае сли нет - то пониммаем что попали в административную панел

                    $plugin = array_shift($url);
                    // В $pluginSettings пишем фактический путь настроек для плагина
                    $pluginSettings = $this->routes['settings']['path'] . ucfirst($plugin. 'Settings');
                    if (file_exists($_SERVER['DOCUMENT_ROOT'] . PATH . $pluginSettings . '.php')) {
                        // Если file_exists вернет тру - то должны осуществить подключение этого файла.
                        // И необходимо переопределить наше свойство роут - вдруг будут какие-то изменения которые внес плагин,
                        // чтобы ему было поудобнее с чем-либо работать
                        // Исправляем слеши для неймспейсов
                           $pluginSettings = str_replace('/', '\\', $pluginSettings);
                        // Вызываем класс, обращаемся к его статическому меетоду get() и получаем свойство.
                        // После склейки, в свойство $this->routes попадут новые маршруты, скорректированные и т.д.
                           $this->routes = $pluginSettings::get('routes');
                    }
                        // Наша система должна знать каким образом програмист хочет указать наличие дополнительной
                        // вложенной дирректории плагинов
                        //Если элемент существует, то в переменную $dir запишем $this->routes['plugins']['dir']
                        $dir = $this->routes['plugins']['dir'] ? '/'. $this->routes['plugins']['dir'] . '/' : '/';
                        // Учитывая что разработчики плагинов для нашего фреймворка могут не оч хорошо знать соглашение об именованиях,
                        // внутреннюю логику роботы фреймворма и директоррию могут указать некоректно/как угодно.
                        // Здесь мы должны предусмотреть защиту от этого следующим образом.
                        $dir = str_replace('//', '/', $dir); // Двойные слеши обрежутся и заменятся на одиночный слеш.

                        $this->controller = $this->routes['plugins']['path'] . $plugin . $dir;
                        $hrUrl = $this->routes['plugins']['hrUrl'];
                        $route = 'plugins';


                } else {
                    //Если это не плагин - а стало быть админ панель
                    //$this - ссылка на вызывающий объект
                    $this->controller = $this->routes['admin']['path'];

                    // ПРОВЕРЯЕМ - РАБОТАЕМ ЛИ МЫ С ЧПУ
                    $hrUrl = $this->routes['admin']['hrUrl'];

                    //ячейка маршрута
                    $route = 'admin';
                }



            } else {

                /*** Пути пользователя */
//                $url = explod('/', substr($adress_str, strlen(PATH)));

                //Таким образом наша система поймет работать ли ей с ЧПУ (Создавать ли массив параметров)
                $hrUrl = $this->routes['user']['hrUrl'];
                //Определение месста подключения контроллеров
                $this->controller = $this->routes['user']['path'];

                $route = 'user';

            }
            /*** Создание метода создающего маршрут. На вход - метод принимает маршрут который ему нужно создать "$route"
             *и массив ссылок ($url) из которых нужно создать этот маршрут */


            $this->createRoute($route, $url);
            // Если есть параметр: color, и т.д.
            if($url[1]) {
                // Сохраняем единожды в переменную и не мучим кажд раз функ каунт
                $count = count($url);
              //На первой итерации ключ - пуст
                $key = '';

                //Если работаем не с ЧПУ
                if(!$hrUrl) {
                    $i = 1;
                        } else {
                    $this->parameters['alias'] = $url[1];
                    $i = 2;
                }
                for ( ; $i < $count; $i++ ) {
                    if(!$key) {
                        //в ки попал элемент "collor" и он там и остается хранится. При этом в свойстве параметерс
                        // создали ячейку с массивом ки
                        $key = $url[$i];
                        $this->parameters[$key] = '';
                    } else {
                        // на следующей итерациии - мы переходим вот сюда, потому что в ки - уже что-то хранится.
                        // И мы берем и в ячейку масива параметерс с названием "collor" - записываемто,
                        // что приходит на следующей итерациии цикла. А это пр. "red". И обнуляем ключ. чтобы снова пара ключ/значение была записана

                        $this->parameters[$key] = $url[$i];
                        $key = '';
                    }
                }

            } // Если нет - свойств параметра будет пустое
//            exit(); - Закоментировали, - нужен был для тестов

        } else {
            //В этой ситуации нет смысла продолжать другие действия
            try {
                //Выбросим исключение базового класса \Exception и сообщение
                throw new \Exception ('incorrect site directory' );
            }
            catch(\Exception $e) {
            //Завершим работу скрипта, вызвав у убъекта исключения метод getMessage()
                exit($e->getMessage());
            }
        }
    }
    /*** На вход должны передать маршрут и массив */
    private function createRoute($var, $arr) {
        $route = [];

        //Если не пуст эллемент пути который соответствует контроллеру
        if(!empty($arr[0])) {
            //Если  существует такой элиас маршрутов, то должны взять маршрут: его разобрать и сохранить в роут
            if($this->routes[$var]['routes'][$arr[0]]) {
                $route = explode('/', $this->routes[$var]['routes'][$arr[0]]);

                $this->controller .= ucfirst($route[0] . 'Controller');
            } else {
           //Учитываем что маршрут не описан , но контроллер есть - в массиве есть нулевой эллемент
                $this->controller .= ucfirst($arr[0] . 'Controller');
            }
        } else {
         //Если - нет нулевого элемента - по сутти - массив $arr  -пуст. Тогда дефолтные элементы подключаем
                $this->controller .= $this->routes['default']['controller'];
        }
        //Определяем, какие же подключатся методы
        //Если $route[1] есть в массиве и  не пуст, то во входной метод запишем роут1. Если его нет ли пуст -
        //то в инпутМетод запишется, то что записано в элементе массива -  inputMethod. То есть, то что предлагается по умолчанию

        $this->inputMethod = $route[1] ? $route[1] : $this->routes['default']['inputMethod'];
        $this->outputMethod = $route[2] ? $route[2] : $this->routes['default']['outputMethod'];

        return;
    }



}

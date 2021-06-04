<?php

namespace core\base\controllers;
use core\base\exceptions\RouteException;
use core\base\settings\Settings;

class RouteController extends BaseController
{

    use Singleton;

    protected $routes;


    /*** Работаем в необъектном контексте, а в статическом. $this-> не доступен потому что $this-> ссылка на объект   ***/
// getInstance - тож будет тянуться из трейта

    /***блокируем клонирование объекта из вне */
// __clone в трейт

        /***блокируем создание объекта из вне */
    private function __construct()
    {
        $adress_str = $_SERVER['REQUEST_URI'];

        if(!empty($_SERVER['QUERY_STRING'])) {
            $adress_str = substr($adress_str, 0, strpos($adress_str, $_SERVER['QUERY_STRING']) - 1);
        }
        //В пременную $path мы сохранили обрезанную строку в которой содержится имя выполнения скрипта.
        // Если константа PATH не совпадет с именем скрипта - не подгрузятся ни контроллеры ни изображения, - ничего.
        // В этой ситуации нет смысла продолжать другие действия   **
        $path = substr($_SERVER['PHP_SELF'], 0, strpos($_SERVER['PHP_SELF'], 'index.php'));
        //Поетому **
        if ($path === PATH) {
            /***Если символ / стаит в конце строки - мы должны перенаправить пользователя на ссылку без этого символа */
            // Надо чтобы слеш не являлся крайним слешем в константе PATH
            if (strrpos($adress_str, '/') === strlen($adress_str) - 1 && strrpos($adress_str, '/') !== strlen(PATH) - 1 ) {
                // $this это псевдо переменная, когда методы вызываются в контексте объекта, ссылка на вызывающий объект

                $this->redirect(rtrim($adress_str, '/'), 301);
            }
            //Сохраним маршруты в наше свойство routes
        $this->routes = Settings::get('routes');
        //если свойства routes - не получены - мы не сможем продолжать выполнение скрипта. Генерируем исключение

            if (!isset($this->routes)) throw new RouteException('Отсутствуют маршруты в базовых настройках', 1);
        //Если скрипт выбрасывает исключение, скрипт автоматически переходит в index.php в блок catch() и работа скрипта завершается

            /*** ПРОВЕРЯЕМ, НЕ В АДМИНКУ ЛИ ПЫТАЕТСЯ ПОПАСТЬ ЧЕЛОВЕК ОТПРАВИВШИЙ ЗАПРОС */

            $url = explode('/', substr($adress_str, strlen(PATH)));
            //Проверяем на соответствие адрестной строки до первого слеша алиасу пути админа
            if($url[0] === $this->routes['admin']['alias']) {
                /*** Пути админки */
                //Выкидываем admin из массива и сдвигаем cлед элемент массива на ключ "0"
                array_shift($url);
                // Если $url[0]- существует, то существует ли дирректория конкретно для этого плагина
                if(isset($url[0]) && $url[0] !== '' && is_dir($_SERVER['DOCUMENT_ROOT'] . PATH . $this->routes['plugins']['path'] . $url[0] )) {                    //Если выполнится - начинаем делать что-то для плагина,  ае сли нет - то пониммаем что попали в административную панел
                    $plugin = array_shift($url);
                    // В $pluginSettings пишем фактический путь настроек для плагина
                    // Вот так и получили к ShopSettings для пути /admin/shop/...
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
                        // Наша система должна знать каким образом програмист хочет указать наличие дополнительной
                        // вложенной дирректории плагинов
                        //Если элемент существует, то в переменную $dir запишем $this->routes['plugins']['dir']

                        if (isset($this->routes['plugins']['dir'])) {
                            $dir = '/' . $this->routes['plugins']['dir'] . '/';
                        } else {
                            $dir = '/';
                        }
                        // Учитывая что разработчики плагинов для нашего фреймворка могут не оч хорошо знать соглашение об именованиях,
                        // внутреннюю логику роботы фреймворма и директоррию могут указать некоректно/как угодно.
                        // Здесь мы должны предусмотреть защиту от этого следующим образом.
                        $dir = str_replace('//', '/', $dir); // Двойные слеши обрежутся и заменятся на одиночный слеш.

                        $this->controller = $this->routes['plugins']['path'] . $plugin . $dir;
                        $hrUrl = $this->routes['plugins']['hrUrl'];
                        $route = 'plugins';
                    }
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
                //Таким образом наша система поймет работать ли ей с ЧПУ (Создавать ли массив параметров)
                $hrUrl = $this->routes['user']['hrUrl'];
                //Определение места подключения контроллеров (впихнули в него namespace)
                $this->controller = $this->routes['user']['path'];
                $route = 'user';
            }
//            После контроллера и метода в адресной стоке может быть набор параметров.
//                    Договоримся так: Если есть ЧПУ, то:
//        /catalog/iphone-11s/color/red
//                    *controller
//                    *alias
//                    * параметры вида: key/value
//        Номинального ключа не будет иметь - только то, что стоит после контроллера

            $this->createRoute($route, $url);
            // Если есть параметр: color, и т.д.
            if(isset($url[1])) {
                // Сохраняем единожды в переменную и не мучим кажд раз функ каунт
                // посчитав один раз - экономим ресурс сервера
                $count = count($url);
              //На первой итерации ключ - пуст
                $key = '';

                //Если работаем не с ЧПУ
                if($hrUrl !== true) {
                    $i = 1;
                        } else {
                    $this->parameters['alias'] = $url[1];
                    $i = 2;
                }
                for ( ; $i < $count; $i++ ) {
                    if($key === '') {
                        //в ки попал элемент "collor" и он там и остается хранится. При этом в свойстве параметерс
                        // создали ячейку с массивом ки
                        $key = $url[$i];
                        //Создадим ячейку массива внутри св-ва parameters
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
//            exit(); //- Закоментировали, - нужен был для тестов

        } else {
            //В этой ситуации нет смысла продолжать другие действия

                //Выбросим исключение базового класса \Exception и сообщение
                throw new RouteException ('incorrect site directory', 1);

        }
    }
    /*** На вход должны передать маршрут и массив */
    private function createRoute($var, $arr) {
        $route = [];
        if(!empty($arr[0])) {

            if(isset($this->routes[$var]['routes'][$arr[0]])) {
                $route = explode('/', $this->routes[$var]['routes'][$arr[0]]);
                $this->controller .= ucfirst($route[0] . 'Controller');
            } else {
                $this->controller .= ucfirst($arr[0] . 'Controller');
            }

        } else {
            $this->controller .= $this->routes['default']['controller'];
        }
        //Определяем, какие же подключатся методы
        //Если $route[1] есть в массиве и  не пуст, то во входной метод запишем роут1. Если его нет ли пуст -
        //то в инпутМетод запишется, то что записано в элементе массива -  inputMethod.
        // То есть, то что предлагается по умолчанию

        // Так с isset - не работает
//        $this->inputMethod = $route[1] ? isset($route[1]) : $this->routes['default']['inputMethod'];
//        $this->outputMethod = $route[2] ? isset($route[2]) : $this->routes['default']['outputMethod'];

        // Так - работает
        $this->inputMethod = isset($route[1]) ? $route[1] : $this->routes['default']['inputMethod'];
        $this->outputMethod = isset($route[2]) ? $route[2] : $this->routes['default']['outputMethod'];

        return;

    }
}

<?php


namespace core\base\controllers;


use core\base\exceptions\RouteException;
use core\base\settings\Settings;
abstract class BaseController
{
    use \core\base\controllers\BaseMethods;

    protected $page;
    protected $errors;

    protected $controller;
    protected $inputMethod;
    protected $outputMethod;
    protected $parameters;

    protected $styles;
    protected $scripts;

    public function route() {
        $controller = str_replace('/', '\\', $this->controller);

        try {
            // ReflectionMethod проверил существование  метода request в этом классе $controller
            // Если да, то при помощи метода invoke у объекта new $controller вызвал метод
            // request и передал в него массив аргументов $args
            $object = new \ReflectionMethod($controller, 'request');

            $args = [
                'parameters' => $this->parameters,
                'inputMethod' => $this->inputMethod,
                'outputMethod' => $this->outputMethod
            ];

            $object->invoke(new $controller, $args);
        }
        catch(\ReflectionException $e) {
            throw new RouteException($e->getMessage());

        }

    }
    public function request($args) {
        // Принимаем аргументы
        $this->parameters = $args['parameters'];

        $inputData =  $args['inputMethod'];
        $outputData =  $args['outputMethod'];
        //$this - хранит указатель на текущий объект

        $data = $this->$inputData();
        // Существует ли метод у объекта переданого первым параметром.
        if(method_exists($this, $outputData)) {
            $page = $this->$outputData($data);
            if ($page) $this->page = $page;
        }
        elseif ($data){
            $this->page = $data;
        }

        if($this->errors) {
            $this->writeLog($this->errors);
        }
        // Для каждого метода свой функционал потому как ООП подход
        $this->getPage();
    }
    // Метод шаблонизатор - собирает страницу
    protected function render($path = '', $parameters = []) {
    // Из массива в текущей символьной таблице - создает переменные стиля ключ:значение
        // Эту таблицу параметров надо сделать доступной для шаблона и в этом нам помогает "буфер обмена"

        //v2 - ликвидация системы утечки памти/
        extract($parameters);
        if(!$path) {
        // v3 Если будем работать в рамках админки и ничего не передадим методу $render, будут пытаться подключаться шаблоны пользовательской части. Что никак не подходит.


            $class = new \ReflectionClass($this);
            // '\\' - в конце строки добавляет слеш в конец пути.
            $space = str_replace('\\', '/',$class->getNamespaceName() . '\\');
            $routes = Settings::get('routes');
            // Три последовательные проверки
            if($space === $routes['user']['path']) $template = TEMPLATE;
            else $template = ADMIN_TEMPLATE;

            //ReflectionClass представляет информацию о классе
            ///strtolower преобразует строку в  indexcontroller
            /// explode - поскольку разделитель controller, разобьет строку на массив с единственным значением index,
            $path = TEMPLATE . explode('controller', strtolower($class->getShortName()))[0];
        }
        // Все что выводится в буфер обмена, не выводится на экран браузера а копируется в буфер обмена
        //Открывает текущий буфер обмена


        ob_start();

        // Внутри шаблона нам будут доступны переменные , которые мы экспортировали в текущую символьную таблицу
        if(!@include_once  $path . '.php') throw new RouteException('missing template - ' . $path);

        //вернет в переменную темлейт наш файл индекс пхп. И с темплейт попадет то что будет extract в файле index.php
        return ob_get_clean();
    }

    protected function getPage() {
        if(is_array($this->page)) {
            foreach ($this->page as $block) echo $block;
        } else {
            echo $this->page;
        }
        exit();
    }




    protected function init($admin = false)
    {

        if (!$admin) {
            if (USER_CSS_JS['styles']) {
                // тримом отрежем возможный / перед css/style.css, к примеру
                foreach (USER_CSS_JS['styles'] as $item) $this->styles[] = PATH . TEMPLATE . trim($item, '/');
            }
            if (USER_CSS_JS['scripts']) {
                foreach (USER_CSS_JS['scripts'] as $item) $this->scripts[] = PATH . TEMPLATE . trim($item, '/');
            }
        } else {
            if (ADMIN_CSS_JS['styles']) {
                // тримом отрежем возможный / перед css/style.css, к примеру
                foreach (ADMIN_CSS_JS['styles'] as $item) $this->styles[] = PATH . ADMIN_TEMPLATE . trim($item, '/');
            }
            if (ADMIN_CSS_JS['scripts']) {
                foreach (ADMIN_CSS_JS['scripts'] as $item) $this->scripts[] = PATH . ADMIN_TEMPLATE . trim($item, '/');
            }
        }
    }

}
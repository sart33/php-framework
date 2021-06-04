<?php


namespace core\base\controllers;


use core\base\exceptions\RouteException;
use core\base\settings\Settings;
abstract class BaseController
{
    use \core\base\controllers\BaseMethods;

    protected $page;
    protected $header;
    protected $content;
    protected $footer;
    protected $errors;

    protected $routes;
    protected $controller;
    protected $inputMethod;
    protected $outputMethod;
    protected $parameters;

    protected $template;
    protected $styles;
    protected $scripts;

    protected $userId;

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
        // Вызвать метод название которого хранится в переменной $inputData
        //$this - хранит указатель на текущий объект ($this->$...())
        // $this->($a) - Если без значка доллара - то обращаемся к свойству “a” по имени.
        // Если с $ - то обращаемся к свойству, которое хранится внутри переменной “а”.
        // Со знаком доллара - часто в цикле foreach, где имена переменным даются внутри этого цикла.
        $data = $this->$inputData();
        // Существует ли метод у объекта переданого первым параметром.
        if(method_exists($this, $outputData)) {
            $page = $this->$outputData($data);
            if(!empty($page)) $this->page = $page;
        }
        //К $outputData мы и так имеем доступ, но в него можем еще передавать и массивы аргументов $data

        //Теперь надо вызвать у нашего контроллера выходной метод, который соберет шаблоны и вернет все эти данные в переменную $page.
        //Будем полностью хранить страницу в переменной.
        elseif (!empty($data)) {
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
        // В текущей символьной таблице (области видимости этого метода). Интерпритатор создаст символьную таблицу и
        //здесь необходимо разобрать этот массив параметров

        //v2 - ликвидация системы утечки памти/
        extract($parameters);
        if(empty($path)) {
            //ReflectionClass представляет информацию о классе
            ///strtolower преобразует строку в  indexcontroller
            $class = new \ReflectionClass($this);
            // '\\' - в конце строки добавляет слеш в конец пути.
            $space = str_replace('\\', '/',$class->getNamespaceName() . '/');
            $routes = Settings::get('routes');

            // Проверяем сначала пути пользователя, поскольку скорость именно для пользователя - должна быть максимальной
            if($space === $routes['user']['path']) $template = TEMPLATE;
            else $template = ADMIN_TEMPLATE;

            /// explode - поскольку разделитель controller, разобьет строку на массив с единственным значением index,
            $path = $template . explode('controller', strtolower(($class)->getShortName()))[0];
            // Все что выводится в буфер обмена, не выводится на экран браузера а копируется в буфер обмена
        }
        //Открывает текущий буфер обмена


        ob_start(); //Открывает текущий буфер обмена
        // Поскольку внутри этой функции (области видимости) будет находится наш шаюлон,
        //! - то внутри шаблона будут доступны и все переменные которые экспортировали с помощью extract()!

        if(empty(@include_once $path . '.php')) throw new RouteException('missing template - ' . $path);

        //вернет в переменную темлейт наш файл индекс пхп. И с темплейт попадет то что будет extract в файле index.php
        return ob_get_clean();
    }


    protected function getPage() {
        if(is_array($this->page)) {
            foreach ($this->page as $block) echo $block;
        } else {
            echo $this->page;
        }
        exit;
    }




    protected function init($admin = false)
    {

        if(empty($admin)) {
            if (isset(USER_CSS_JS['styles'])) {
                // тримом отрежем возможный / перед css/style.css, к примеру
                foreach (USER_CSS_JS['styles'] as $item) $this->styles[] = PATH . TEMPLATE . trim($item, '/');
            }
            if (isset(USER_CSS_JS['scripts'])) {
                foreach (USER_CSS_JS['scripts'] as $item) $this->scripts[] = PATH . TEMPLATE . trim($item, '/');
            }
        } else {
            if (isset(ADMIN_CSS_JS['styles'])) {
                // тримом отрежем возможный / перед css/style.css, к примеру
                foreach (ADMIN_CSS_JS['styles'] as $item) $this->styles[] = PATH . ADMIN_TEMPLATE . trim($item, '/');
            }
            if (isset(ADMIN_CSS_JS['scripts'])) {
                foreach (ADMIN_CSS_JS['scripts'] as $item) $this->scripts[] = PATH . ADMIN_TEMPLATE . trim($item, '/');
            }
        }
    }

}
<?php

namespace core\user\controllers;

use core\base\controllers\BaseController;

class IndexController extends BaseController {

    protected  $name;
//    // trait - подключается через конструкцию use, но вне тела метода.
//    use trait1, trait2 {
//        //Методом who первого трейта заменить метод who второго трейта.
//        trait1::who insteadof trait2;
//        // А если надо использовать одноименнный метод и однго трейта и другого, то...
//
//        trait2::who as who2;
//        //Но, псевдоним можно объявить только для такого метода, который уже был замещен иным методом
//    }

    protected function inputData() {
        //Трейт - это механизм обеспечения повторного использования кода в языках с поддержкой только одиночного наследования,
//        $this->who();
//        $this->who2();


        exit();
        // $template - В этой переменной на теперешний момент доступен вообще весь шаблон
//        $template = $this->render(false, ['name' => 'Masha']);
//
//        exit($template);
        $name = 'Ivan';
        $content = $this->render('', compact('name'));
        $header = $this->render(TEMPLATE . 'header');
        $footer = $this->render(TEMPLATE . 'footer');

            return compact('header', 'content', 'footer');

//        echo '<h1 style="color: red">'; $name;
    }

//    protected function outputData() {
//        // В $vars - принимаем переменные $content, $header, $footer
//        $vars = func_get_arg(0);
////
////        exit($this->render('', $vars));
//        $this->page = $this->render(TEMPLATE . 'templater', $vars);
//    }
}
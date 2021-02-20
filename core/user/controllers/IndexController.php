<?php

namespace core\user\controllers;

use core\base\controllers\BaseController;

class IndexController extends BaseController {

    protected  $name;

    protected function inputData() {
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

    protected function outputData() {
        // В $vars - принимаем переменные $content, $header, $footer
        $vars = func_get_arg(0);
//
//        exit($this->render('', $vars));
        $this->page = $this->render(TEMPLATE . 'templater', $vars);
    }
}
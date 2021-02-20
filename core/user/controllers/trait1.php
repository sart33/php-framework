<?php


namespace core\user\controllers;

// trait - подключается через конструкцию use, но вне тела метода.
trait trait1
{

    public function who() {
        echo 'trait1<br>';
    }
}
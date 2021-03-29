<?php


namespace core\admin\controllers;


class ShowController extends BaseAdmin
{
    protected function inputData() {
        // Для начала - надо вызвать метод у родителя.
//        parent::inputData()
        // Вроде норм, но у нас еще будут плагины.
        // Плагины будут наследоваться от этих контроллеров (сейчас конкретно - это ShowController).
        // Поетому в BaseAdmin - создадим небольшой метод, который просто будет вызывать метод самого BaseAdmin.
        $this->execBase();
        // Этот метод ничего не будет возвращать - поскольку это служебные методы они заполнняют свойства наших классов.
        $this->createTableData();

        $this->createData();

    }
    protected function outputData() {

    }
}
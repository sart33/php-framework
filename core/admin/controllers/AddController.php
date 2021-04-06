<?php


namespace core\admin\controllers;


class AddController extends BaseAdmin
{
    protected function inputData()
    {
        if(!$this->userId) $this->execBase();

        // Этот метод ничего не будет возвращать - поскольку это служебные методы они заполнняют свойства наших классов.
        $this->createTableData();

        $this->createOutputData();
    }


}
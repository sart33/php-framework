<?php


namespace core\admin\controllers;


use core\base\settings\Settings;
use core\base\settings\ShopSettings;

class ShowController extends BaseAdmin
{
    protected function inputData() {
        // Для начала - надо вызвать метод у родителя.
//        parent::inputData()
        // Вроде норм, но у нас еще будут плагины.
        // Плагины будут наследоваться от этих контроллеров (сейчас конкретно - это ShowController).
        // Поетому в BaseAdmin - создадим небольшой метод, который просто будет вызывать метод самого BaseAdmin.
        if(!$this->userId) $this->execBase();
        // Этот метод ничего не будет возвращать - поскольку это служебные методы они заполнняют свойства наших классов.
        $this->createTableData();

        $this->createData(['fields' => 'content']);

        //При обычном подходе  - нам потребуется такая конструкция. Но перепилывать код фреймворка от проекта к проекту
        // - так себе решение. Придется залазить во многие методы в том числе и методы плагинов.
        // Задумка - кодировать в пользовательской части, в административной - сделать ее развертывание максимально быстрым.
//        switch ($this->data) {
//            case 'teachers':
//                break;
//        }
        // Поетому, мы напишем метод, который в зависимости от таблиц, если найдет подобного рода файлы -
        // подключит классы этих файлов. Классс этого файла - вызовет у него некий базовый метод, который должен быть
        // по дефолту и дальше именно в этих классах мы будем осуществлять кодирование.
        // После завершения кодирования в этих классах - модифицируем свойства, которые здесь есть.
        // А при переходе к следующему проекту, мы либо удалим эту папку, либо удалим из нее файлы и все -
        // чистый аккуратный проект который можно аналогично модифицировать под каждый конкретный проект.

        // Это будет часто используемый метод, потому и пишем его в BaseAdmin.
        return $this->expansion(get_defined_vars());

    }

    protected function createData($arr = []) {

        $fields = [];
        $order = [];
        $orderDirection = [];



        if(!$this->columns['id_row']) return $this->data = [];

        // Воспользуемся здесь псевдонимом.
        $fields[] = $this->columns['id_row'] . ' as id';
        if($this->columns['name']) $fields['name'] = 'name';
        if($this->columns['img']) $fields['img'] = 'img';
        // В метод get мы присылаем массив. А имена ячейкам массива мы даем.
        // поскольку проверяя в цикле - есть ли эти ячейки. Если - нет - будем искать пр. name в других записях.
        // С img - аналогично.
        if(count($fields) < 3) {
            foreach ($this->columns as $key => $item) {
                if(!$fields['name'] && strpos($key, 'name') !== false) {
                    $fields['name'] = $key . ' as name';
                }
                if(!$fields['img'] && strpos($key, 'img') === 0) {
                    $fields['img'] = $key . ' as img';
                }
            }
        }
        if($arr['fields']) {
            if (is_array($arr['fields'])) {
                // Склейка массивов.  который в классе Settings находится.
                $fields = Settings::instance()->arrayMergeRecursive($fields, $arr['fields']);

            } else {
                $fields[] = $arr['fields'];
            }

        }

        if($this->columns['parent_id']) {
            if(!in_array('parent_id', $fields))  $fields[] = 'parent_id';
            $order[] = 'parent_id';
        }
        //Если есть ячейка - позиция в меню - то добаляем ее в $order, чтобы иметь возможность по ней сотртироваться.
        if($this->columns['menu_position']) $order[] = 'menu_position';
        //Еще может существовать поле $date. Сортировка по дате.
        elseif ($this->columns['date']) {

            if($order) $orderDirection = ['ASC', 'DESC'];
            else $orderDirection[] = 'DESC';

            $order[] = 'date';
            // После этого всего - еще надо склеить два массива.
        }
        if($arr['order']) {
            // если массив- клеим
            if(is_array($arr['order'])) {
                // Склейка массивов.  который в классе Settings находится.
                $order = Settings::instance()->arrayMergeRecursive($order, $arr['order']);
            } else {
                //Если нет добавляем как ячейку в массив с числовыми ключами $order
                $order[] = $arr['order'];
            }

        }
        if($arr['order_direction']) {
            // если массив- клеим
            if(is_array($arr['order_direction'])) {
                // Склейка массивов.  который в классе Settings находится.
                $orderDirection = Settings::instance()->arrayMergeRecursive($orderDirection, $arr['order_direction']);
            } else {
                //Если нет добавляем как ячейку в массив с числовыми ключами $order
                $orderDirection[] = $arr['order_direction'];
            }
        }



        $this->data = $this->model->get($this->table, [
            'fields' => $fields,
            'order' => $order,
            'order_direction' => $orderDirection
        ]);

    }

}
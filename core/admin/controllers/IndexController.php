<?php


namespace core\admin\controllers;


use core\base\controllers\BaseController;
use core\admin\models\Model;

class IndexController extends BaseController
{

    protected function inputData() {

        $db = Model::instance();
//        create
//        reed
//        update
//        delete

    //   "WHERE id = (SELECT id FROM students)"; - Если предполагается, что придет только один запрос то может быть именно такое условие.
//        $query = "SELECT category.id, category.name, product.id as p_id, product.model as p_name
//from product left join category on product.category_id = category.id";

        $table = 'teachers';

//        $query = "(SELECT t1.name, t2.fio FROM t1 LEFT JOIN t2 ON t1.parent_id = t2.id WHERE t1.parent_id = 1)
//        UNION (SELECT t1.name, t2.fio FROM t1 LEFT JOIN t2 ON t1.parent_id = t2.id WHERE t2.id = 1)
//        ORDER BY 1 ASC";

        // %Masha = Маша в конце строки, Masha% - нач. с Маша, %Masha% - Маша - в любом месте строки.
//        $query = "SELECT * FROM  teachers WHERE name LIKE '%Masha'";
//        $ids = ['1',2,3];

        $color = ['red', 'blue', 'black'];

        $res = $db->get($table, [
            'fields' => ['id', 'name'],
            'where' => ['name' => "O'Raily"],
            // Чтобы здесь не подавать нумерованные строковые ключи и их разбирать по еще неизвестным каким-то операндам
//            'operand' => ['IN', '<>'],
//            'condition' => ['AND', 'OR'],
            'order' => [1, 'name'],
            'order_direction' => ['ASC', 'DESC'],
            'limit' => '1',
            'join' => [
                [
                    // Каждый следующий элемент 'join' - будет присоединяться к предидущему.

                    // Сюда, мы должны иметь возможность передавать как асоциативный массив, так и обычный - числовой.
                    // Именно для этого появляется это поле     'table' => 'join_table1',
                    'table' => 'join_table1',
                    'fields' => ['id as j_id', 'name as j_name'],
                    // Тип присоединения
                    'type' => 'left',
                    'where' => ['name' => 'Sasha'],
                    'operand' => ['='],
                    'condition' => ['OR'],
                    //  Признак присоединения/ Будут два варианта. 1. Явно указать к какой таблице присоединять
                    // (По дефолту предидущая таблица)
                    'on' => [
                        'table' => 'teachers',
                        'fields' => ['id', 'parent_id']
                    ]
                ],
                'join_table2' => [
                    'table' => 'join_table2',
                    'fields' => ['id as j2_id', 'name as j2_name'],
                    // Тип присоединения
                    'type' => 'left',
                    'where' => ['name' => 'Sasha'],
                    'operand' => ['<>'],
                    'condition' => ['AND'],
                    //  Признак присоединения/ Будут два варианта. 1. Явно указать к какой таблице присоединять
                    // (По дефолту предидущая таблица)
                    'on' => ['id', 'parent_id']



                ]
//                // Есть такая ситуация, когда надо стыковать одну и ту же таблицу (многие ко многим - когда ч/з третью таблицу стыкуем себя к себе)
//
            ]
        ]);


    }

}
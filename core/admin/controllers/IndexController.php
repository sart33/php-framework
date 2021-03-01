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
            'limit' => '1'
        ])[0];


        exit('id ='  .$res['id'] . ' Name = ' . $res['name']);
    }

}
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

//        $query = "SELECT category.id, category.name, product.id as p_id, product.model as p_name
//from product left join category on product.category_id = category.id";

        $table = 'teachers';
        $res = $db->get($table, [
            'fields' => ['id', 'name'],
            'where' => ['fio'=> 'Smirnova', 'name' => 'Masha', 'surname' => 'Sergeevna'],
            'operand' => ['=', '<>'],
            'condition' => ['AND'],
            'order' => ['fio', 'name'],
            'order_direction' => ['ASC', 'DESC'],
            'limit' => '1'


        ]);


        exit('I am - admin panel');
    }

}
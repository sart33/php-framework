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


        $table = 'teachers';

        $color = ['red', 'blue', 'black'];

        $res = $db->delete($table, [
            'where' => ['id' => 16],
            'join' => [
               ['table' => 'students',  'on' => ['students_id', 'id']]
            ]
        ]);


        exit('id ='  .$res['id'] . ' Name = ' . $res['name']);
    }

}
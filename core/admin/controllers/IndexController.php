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

//        $stringC =  json_encode($table);
//        echo $stringC . '<br>';
//        echo '<pre>';
//        print_r(json_decode($stringC));
//        echo '</pre>';
        $files = [];
//        $files['img'] = '';

        // Примерно то, что можно вытянуть из пост массива. Надо понимать что нейм йди - далеко не то, место,
        // где мы можем хранить первичный ключ.
        '<input type="hidden" name="id" value="5">';


        $_POST['id'] = 8;
        $_POST['name'] = 'Elena';
        $_POST['content'] = "<p>New's book</p>";

        $res = $db->edit($table
            , [
//            'fields' => ['id' => 2, 'name' => 'Svetlana'],
            'files' => $files,
//            'where' => ['id' => 1]
        ]);
//        , [
//            'fields' => ['name' => 'Katherina Ivanovna', 'content' => 'Hello_two'],
//            'where' => ['name' => "O'Raily"],
//            'limit' => '1'
//        ]);


        exit('id ='  .$res['id'] . ' Name = ' . $res['name']);
    }

}
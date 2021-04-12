<?php


namespace core\admin\controllers;


//use core\admin\models\Model;
use core\base\models\BaseModel;
use core\base\settings\Settings;


class AddController extends BaseAdmin
{
    protected function inputData()
    {
        if(!$this->userId) $this->execBase();

        // Этот метод ничего не будет возвращать - поскольку это служебные методы они заполнняют свойства наших классов.
        $this->createTableData();

        $this->createForeignData();

        $this->createRadio();

        $this->createOutputData();


//        $this->model->showForeiginKeys($this->table);
    }

    protected function createForeignProperty($arr, $rootItems) {
        if(in_array($this->table, $rootItems['tables'])) {
            $this->foreignData[$arr['COLUMN_NAME']][0]['id'] = 0;
            $this->foreignData[$arr['COLUMN_NAME']][0]['name'] = $rootItems['name'];

        }
        $columns = $this->model->showColumns($arr['REFERENCED_TABLE_NAME']);
        $name = '';
        if($columns['name']) {
            $name = 'name';
        } else {
            foreach ($columns as $key => $value) {
                if(strpos($key, 'name') !== false) {
                    $name = $key . 'as name';
                }
            }
            if (!$name) $name = $columns['id_row'] . ' as name';
        }

        if($this->data) {
            // Если ссылаемся сами на себя
            if ($arr['REFERENCED_TABLE_NAME'] === $this->table) {
                //В $this->columns['id_row'] - лежит строка id.
                $where[$this->columns['id_row']] = $this->data[$this->columns['id_row']];
                // Дальше в $operand[] мы должны
                $operand[] = '<>'; // Т.е. - нам не нужно получать В этот список и свои данные.
                // Теперь надо формировать наш вс вами елемент массива  $foreignData['COLUMN_NAME'],
                // В нем может что-то быть, а может и не быть ничего - поэтому сразу в эту ячейку мы сохранять ничего не будем.
                // Сохраним в другую переменную - потом проверим и в цикле обойдем. Потому что может быть пустая - может быть не пустая.

            }

        }
        $foreign = $this->model->get($arr['REFERENCED_TABLE_NAME'],
            ['fields' => [$arr['REFERENCED_COLUMN_NAME'] . ' as id', $name],
                'where' => $where,
                'operand' => $operand
            ]);

        if($foreign) {
            if($this->foreignData[$arr['COLUMN_NAME']]) {
                foreach ($foreign as $value) {
                    $this->foreignData[$arr['COLUMN_NAME']][] = $value;
                }

            } else {
                $this->foreignData[$arr['COLUMN_NAME']] = $foreign;

            }
        }
    }

    protected function createForeignData($settings = false) {

        if(!$settings) $settings = Settings::instance();

        $rootItems = $settings::get('rootItems');

        $keys = $this->model->showForeignKeys($this->table);

        if($keys) {
            foreach ($keys as $item) {

                $this->createForeignProperty($item, $rootItems);

            }

        } elseif ($this->columns['parent_id']) {

            $arr['COLUMN_NAME'] = 'parent_id';
            $arr['REFERENCED_COLUMN_NAME'] = $this->columns['id_row'];
            $arr['REFERENCED_TABLE_NAME'] = $this->table;

            $this->createForeignProperty($arr, $rootItems);

        }
    return;
   }

}
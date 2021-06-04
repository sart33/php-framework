<?php


namespace core\admin\controllers;


use core\admin\models\Model;
use core\base\models\BaseModel;
use core\base\settings\Settings;


class AddController extends BaseAdmin
{
    protected function inputData()
    {
        if(empty($this->userId)) $this->execBase();

        // Этот метод ничего не будет возвращать - поскольку это служебные методы они заполнняют свойства наших классов.
        $this->createTableData();

        $this->createForeignData();

        $this->createMenuPosition();

        $this->createRadio();

        $this->createOutputData();


    }


    protected function createForeignProperty($arr, $rootItems) {
        if(in_array($this->table, $rootItems['tables'])) {
            $this->foreignData[$arr['COLUMN_NAME']][0]['id'] = 0;
            $this->foreignData[$arr['COLUMN_NAME']][0]['name'] = $rootItems['name'];

        }
        $columns = $this->model->showColumns($arr['REFERENCED_TABLE_NAME']);
        $name = '';
        $where =[];
        $operand = [];
        if(!empty($columns['name'])) {
            $name = 'name';
        } else {
            foreach ($columns as $key => $value) {
                if(strpos($key, 'name') !== false) {
                    $name = $key . 'as name';
                }
            }
            if(empty($name)) $name = $columns['id_row'] . ' as name';
        }

        if(!empty($this->data)) {
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
        $foreign = $this->model->get($arr['REFERENCED_TABLE_NAME'], [
                'fields' => [$arr['REFERENCED_COLUMN_NAME'] . ' as id', $name],
                'where' => $where,
                'operand' => $operand
            ]);

        if(!empty($foreign)) {
            if(!empty($this->foreignData[$arr['COLUMN_NAME']])) {
                foreach ($foreign as $value) {
                    $this->foreignData[$arr['COLUMN_NAME']][] = $value;
                }
            } else {
                $this->foreignData[$arr['COLUMN_NAME']] = $foreign;
            }
        }
    }

    // Метод создающий внешние ключи!?
    protected function createForeignData($settings = false) {

        if(empty($settings)) $settings = Settings::instance();

        $rootItems = $settings::get('rootItems');
        // Если ссылаемся сами на себя

        $keys = $this->model->showForeignKeys($this->table);

        if(!empty($keys)) {
            foreach ($keys as $item) {

                $this->createForeignProperty($item, $rootItems);
            }

        } elseif (!empty($this->columns['parent_id'])) {

            $arr['COLUMN_NAME'] = 'parent_id';
            $arr['REFERENCED_COLUMN_NAME'] = $this->columns['id_row'];
            $arr['REFERENCED_TABLE_NAME'] = $this->table;

            $this->createForeignProperty($arr, $rootItems);

        }

        return;
   }

   protected function createMenuPosition($settings = false) {
    // Если тут пусто, - то все -дальше не едем)
       if(isset($this->columns['menu_position'])) {
           if (empty($settings)) $settings = Settings::instance();
            $rootItems = $settings->get('rootItems');
            // Дальше проверяем - есть ли внашей таблице parent_id, или просто - взять и посчитать записи.
           if (!empty($this->columns['parent_id'])) {
                // Если есть корневая директория - то надо посчитать сколько есть корневых директорий, т.е. - сколько полей.
                // Проверяем наличие нашей таблице в $rootItems['tables']
                if(in_array($this->table, $rootItems['tables'])) {
                    $where = 'parent_id IS NULL OR parent_id = 0';
                } else {
                    // Если есть parent_id то по логике должны быть и внешние ключи. Вот и запросим эти внешние ключи.
                    $parent = $this->model->showForeignKeys($this->table, 'parent_id')[0]; // Тут мы пришли к причине зачем,
                    // в методе модели был указа ключ $key/ Здесь - мы подадим второй необязательный параметр!:
                    //ключ - ограничивающий нашу выборку и уточняющий ее.
                    // Дальше придет AND COLUMN_NAME = 'parent_id' .
                    // Именно parent_id - должен ссылаться на какие-то внешние таблицы,
                    // потому что именно по этому критерию - мы и определяем родителя.
                    if(!empty($parent)) {

                        if($this->table === $parent['REFERENCED_COLUMN_NAME']) {
                            $where = 'parent_id IS NULL OR parent_id = 0';

                        } else {
                            // Мы должны получить поля из вот этой таблицы REFERENCED_TABLE_NAME. Даст возможность наиболее
                            // удобный результат получить для первичной сортировки
                            $columns = $this->model->showColumns($parent['REFERENCED_TABLE_NAME']);
                            //Если есть parent_id - сортировку таблиц запускаем именно по нему. Если parent_id - нет,
                            // запускаем сортировку по REFERENCED_COLUMN_NAME
                            if (!empty($columns['parent_id'])) $order[] = 'parent_id';

                            else $order[] = $parent['REFERENCED_COLUMN_NAME'];
                            //   Дальше надо получить идентификатор самой первого элемента вот в этой таблице -
                            // исходя из нашей сортировки
                            $id = $this->model->get($parent['REFERENCED_TABLE_NAME'], [
                                //Надо получить колонку $parent['REFERENCED_TABLE_NAME'](ее значение).
                                // Чтобы исходя из него - посчитать количество элементов нашей таблицы.
                                'fields' => [$parent['REFERENCED_COLUMN_NAME']],
                                'order' => $order,
                                'limit' => '1'
                                // Вернуть нам надо нулевой элемент той выборки, которая пришла и вернуть то поле,
                                // которое и запрашивали - $parent['REFERENCED_COLUMN_NAME']. Возвращаем (Ложим в id):
                            ])[0][$parent['REFERENCED_COLUMN_NAME']];

                            if(!empty($id)) $where = ['parent_id' => $id];
                        }

                    } else {
                        // Если родитель не пришел, то
                        $where = 'parent_id IS NULL OR parent_id = 0';
                    }
                }
            }

            $menuPos = $this->model->get($this->table, [
                'fields' => ['COUNT(*) as count'],
                'where' => $where,
                'no_concat' => true
                ])[0]['count'] + 1;

            for($i = 1; $i <= $menuPos; $i++) {
                $this->foreignData['menu_position'][$i-1]['id'] = $i;
                $this->foreignData['menu_position'][$i-1]['name'] = $i;
            }
        }
        return;
   }

}
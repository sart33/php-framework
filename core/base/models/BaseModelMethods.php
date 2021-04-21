<?php


namespace core\base\models;


abstract class BaseModelMethods
{
    protected $sqlFunc = ['NOW()'];

    protected function createFields($set, $table = false)
    {
        //Если в $set['fields'] - что-то пришло тогда он им и останется. Если нет - то появится * (т.е. - выбрать все)
        $set['fields'] = (is_array($set['fields']) && !empty($set['fields'])) ? $set['fields'] : ['*'];

        $table = ($table && !$set['no_concat']) ? $table . '.' : '';

        $fields = '';

        foreach ($set['fields'] as $field) {
            $fields .= $table . $field . ',';
        }
        return $fields;

    }

    protected function createOrder($set, $table = false)
    {

        $table = ($table && !$set['no_concat']) ? $table . '.' : '';

        $orderBy = '';

        if (is_array($set['order']) && !empty($set['order'])) {
            $set['order_direction'] = (is_array($set['order_direction']) && !empty($set['order_direction'])) ? $set['order_direction'] : ['ASC'];

            $orderBy = 'ORDER BY ';
            $directCount = 0;
            foreach ($set['order'] as $order) {
                if ($set['order_direction'][$directCount]) {
                    $orderDirection = strtoupper($set['order_direction'][$directCount]);
                    $directCount++;

                } else {
                    $orderDirection = strtoupper($set['order_direction'][$directCount - 1]);
                }

                if(is_int($order))  $orderBy .= $order . ' ' . $orderDirection . ',';
                else $orderBy .= $table . $order . ' ' . $orderDirection . ',';

                //  "ORDER BY id ASC, name DESC"
            }

            $orderBy = rtrim($orderBy, ',');
        }

        return $orderBy;
    }

    protected function createWhere($set, $table = false, $instruction = 'WHERE') {

        $table = ($table && !$set['no_concat']) ? $table . '.' : '';

        $where = '';
        // Существуют ситуации - когда $where надо передать просто строкой,
        // например: поскольку просто неудобно формировать массив.
        if(is_string($set['where'])) {
            return $instruction  . ' ' . trim($set['where']);

        }

        if(is_array($set['where']) && !empty($set['where'])) {

            $set['operand'] = (is_array($set['operand']) && !empty($set['operand'])) ? $set['operand'] : ['='];
            $set['condition'] = (is_array($set['condition']) && !empty($set['condition'])) ? $set['condition'] : ['AND'];

            $where = $instruction;

            $oCount = 0;
            $cCount = 0;

            foreach ($set['where'] as $key => $item) {

                $where .= ' ';
                if($set['operand'][$oCount]) {
                    $operand = $set['operand'][$oCount];
                    $oCount++;
                } else {
                    $operand = $set['operand'][$oCount - 1];
                }

                if($set['condition'][$cCount]) {
                    $condition = $set['condition'][$cCount];
                    $cCount++;
                } else {
                    $condition = $set['condition'][$cCount - 1];
                }
//                "=<> IN (SELECT * FROM table) NOT LIKE"

                if($operand === 'IN' || $operand === 'NOT IN') {

//                    "SELECT";
                    if (is_string($item) && strpos($item, 'SELECT') === 0) {
                        $inStr =  $item;
                    } else {
                        if(is_array($item)) $tempItem = $item;
                        else $tempItem = explode(',', $item);

                        $inStr = '';

                        foreach ($tempItem as $value) {
                            $inStr .= "'" . addslashes(trim($value)) . "',";
                        }
                    }

                    $where .= $table . $key . ' ' . $operand .  ' (' . trim($inStr, ',') . ') ' . $condition;
                }
                // Возвращает порядковый номер вхождения в случе нахождения и false - в случае не нахождения.
                elseif (strpos($operand, 'LIKE') !== false) {
                    $likeTemplate = explode('%',$operand );
                    foreach ($likeTemplate as $ltKey => $lt) {
                        if(!$lt) {
                            if(!$ltKey) {
                                $item = '%' . $item;
                            } else {
                                $item .= '%';

                            }
                        }
                    }
                    $where .= $table . $key . ' LIKE ' . "'" . addslashes($item) . "' $condition";

                } else {
                    if (strpos($item, 'SELECT') === 0) {
                        $where .= $table . $key . $operand . ' (' . $item . ") $condition";
                    } else {
                        $where .= $table . $key . $operand . "'" . addslashes($item) . "' $condition";

                    }
                }

            }

            $where = substr($where, 0, strrpos($where, $condition));

        }

        return $where;


    }

    protected function createJoin($set, $table, $newWhere = false) {

        $fields = '';
        $join = '';
        $where = '';
        $tables = '';

        if($set['join']) {
            $joinTable = $table;

            foreach ($set['join'] as $key => $item) {

                if(is_int($key)) {
                    if(!$item['table']) continue;
                    else $key = $item['table'];
                }

                if ($join) $join .= ' ';

                if ($item['on']) {
                    $joinFields = [];

                    switch (2) {

                        case count($item['on']['fields']);
                            $joinFields = $item['on']['fields'];
                            break;

                        case count($item['on']);
                            $joinFields = $item['on'];
                            break;

                        default:
                            // continue 2 выведя из switch, перекинет нас на след. итерацию цикла foreach;
                            continue 2;
                            break;
                    }

                    if(!$item['type']) $join .= 'LEFT JOIN ';
                    else $join .= trim(strtoupper($item['type'])). ' JOIN ';

                    $join .= $key . ' ON ';

                    if($item['on']['table']) $join .= $item['on']['table'];
                    else $join .= $joinTable;

                    $join .= '.' . $joinFields[0] . '=' . $key . '.' . $joinFields[1];

                    $joinTable = $key;
                    $tables .= ', ' . trim($joinTable);

                    if($newWhere) {
                        if($item['where']) {
                            $newWhere = false;
                        }

                        $groupCondition = 'WHERE';

                    } else {
                        $groupCondition = $item['group_condition'] ? strtoupper($item['group_condition']) : 'AND';
                    }

                    $fields .= $this->createFields($item, $key);
                    $where .= $this->createWhere($item, $key, $groupCondition);

                }
            }
        }
        return compact('fields', 'join', 'where', 'tables');
    }


    protected function createInsert($fields,  $files, $except) {

        $insertArr = [];

        $insertArr['fields'] = '(';

        $arrayType = array_keys($fields)[0];

        if(is_int($arrayType)) {

            $checkFields = false;

            $countFields = 0;

            foreach ($fields as $i => $item) {
//            for($i = $arrayType; $i < count($fields); $i++) {

                $insertArr['values'] .= '(';

                if (!$countFields) $countFields = count($item);

                $j = 0;

                foreach ($item as $key => $value) {
                    if($except && in_array($key, $except)) continue;
                    if(!$checkFields) $insertArr['fields'] .= $key . ',';
                    if(in_array($value, $this->sqlFunc)) {
                            $insertArr['values'] .= $value . ',';
                    } elseif ($value === 'NULL' || $value === NULL) {
                        $insertArr['values'] .= "NULL" . ',';
                    } else {
                        $insertArr['values'] .= "'" . addslashes($value) . "',";
                    }
                    $j++;

                    // Если он меньше $countFields. То мы должны догнать до $countFields с пустыми строками.

                    if($j === $countFields) break;
                }
                if ($j < $countFields) {
                    for (; $j< $countFields; $j++) {
                        $insertArr['values'] .=  "NULL" . ',';

                    }
                }
                $insertArr['values'] =  rtrim($insertArr['values'],',') .'),';

                if(!$checkFields) $checkFields = true;

            }
        } else {
            $insertArr['values'] = '(';

            if($fields) {
                foreach ($fields as $key => $value) {
                    if($except && in_array($key, $except)) continue;
                    $insertArr['fields'] .= $key . ',';
                    if(in_array($value, $this->sqlFunc)) {
                        $insertArr['values'] .= $value . ',';
                    } elseif ($value === 'NULL' || $value === NULL) {
                        $insertArr['values'] .= "NULL" . ',';
                    } else {
                        $insertArr['values'] .= "'" . addslashes($value) . "',";
                    }

                }
            }

            if($files) {

                foreach ($files as $row => $file) {
                    $insertArr['fields'] .= $row . ',';

                    if(is_array($file)) $insertArr['values'] .= "'" . addslashes(json_encode($file)) . "',";
                    else $insertArr['values'] .= "'" . addslashes($file) . "',";
                }
            }
            $insertArr['values'] = rtrim($insertArr['values'], ',') . ')';

        }
        $insertArr['fields'] =  rtrim($insertArr['fields'],',') . ')';

        $insertArr['values'] =  rtrim($insertArr['values'],',');

        return $insertArr;

    }


    protected function createUpdate($fields, $files, $except) {

        $update = '';
        if($fields) {
            foreach($fields as $row => $value) {

                if($except && in_array($row, $except)) continue;

                // Дальше необходимо осуществить проверку, - не пришла ли у нас функция $value.
                // Тримить $value - нельзя - потому что из БД может прииидти целая статья, которая будет нач. с табуляции (4-х пробелов).
                // И тогда верстка статьйи полезет.

                // Дальше,  в нашу переменную $update Мы должны вставить поле.
                $update .= $row . '=';

                if (in_array($value, $this->sqlFunc)) {
                    $update .= $value . ',';

                } elseif($value === null) {
                    $update .= "NULL" . ',';

                }
                else {
                    // Все экранируем, потому что вне массива - х/з что может приидти.
                    $update .= "'" . addslashes($value) . "',";
                }

            }
        }

        // Пробежимся теперь по массиву $files
        if($files) {

            foreach ($files as $row => $file) {

                //  'img' = 'icon.png'; - все норм - храним строкой. А, если галерея??
                // Либо в виде JSON строки:  JSON строка - это строковое представление массива.
                // Это может быть полезно при сохранении массива в базе данных.

//                 Проверяем - масив ли file или нет. Галерею будем передвать в виде массива данных.
//
//                     $file = 'main_ing.webp';
//                   $file['gallery_img'] = ['1.jpg', '2.png, 3.webp'];
//                'img' = 'icon.png';
//                    '"teacher_str"'

                $update .= $row . '=';

                // Если $file массив то в $update добавим к строке value JSON строку с экранированием слешей
                if(is_array($file)) $update .= "'" . addslashes(json_encode($file)) . "',";
                else $update .= "'" . addslashes($file) . "',";
            }

        }

        return rtrim($update, ',');

    }
}
<?php


namespace core\base\models;


use core\base\controllers\Singleton;
use core\base\exceptions\DbException;
use http\Params;

class BaseModel
{
    use Singleton;

    protected $db;

    private function __construct()
    {
        $this->db = @new \mysqli(HOST, USER, PASS, DB_NAME);

        if ($this->db->connect_error) {
            throw new DbException('Error connecting to database: '
                . $this->db->connect_errno . ' ' . $this->db->connect_error);
        }

        $this->db->query("SET NAMES UTF8");
    }

    /**
     * @param $query
     * @param string $crud = r - SELECT, c - INSERT, u - UPDATE, d - DELETE
     * @param false $return_id
     * @return array|bool|int|string
     * @throws DbException
     */
    final public function query($query, $crud = 'r', $return_id = false)
    {
        //  В свойстве db - хранится объект библиотеки mysqli в котором уже хранится идентификатор подключения
        // и тут можно уже использовать метод query объекта mysqli
        // В $result приходит объект содержащий выборку из базы даных.
        $result = $this->db->query($query);
        if ($this->db->affected_rows === -1) {
            throw new DbException('error in SQL query: ' . $query . ' - ' . $this->db->errno . ' ' . $this->db->error);
        }
        // При помощи оператора множественного выбора switch мы проверяем а что находится в переменной $crud
        switch ($crud) {

            case 'r':
                // num_rows - это свойство нашего объекта $result
                if ($result->num_rows) {
                    $res = [];

                    for ($i = 0; $i < $result->num_rows; $i++) {
                        $res[] = $result->fetch_assoc();
                    }
                    return $res;
                }

                return false;

                break;

            case 'c':

                if ($return_id) return $this->db->insert_id;

                return true;

                break;

            default:

                return true;

                break;

        }
    }

        /**
        @param $table - Таблицы базы даных
        'fields' => ['id', 'name'],
        'where' => ['name' => 'masha', 'surname' => 'Sergeevna', 'fio' => 'Andrey', 'car' => 'Porshe', 'color' => $color],
        'operand' => ['IN', 'LIKE%', '<>', '=', 'NOT IN'],
        'condition' => [ 'AND', 'OR'],
        'order' => [1, 'name'],
        'order_direction' => ['ASC', 'DESC'],
        'limit' => '1',
        'join'=> [
        [
        'table' => 'join_table1',
        'fields' => ['id as j_id','name as j_name'],
        'type' => 'left',
        'where' => ['name' => 'Sasha'],
        'operand' => ['='],
        'condition' => ['OR'],
        'on' => ['id', 'parent_id'],
         * 'group_condition' => 'AND'
         *
        ],
        'join_table1' => [
        'table' => 'join_table2',
        'fields' => ['id as j2_id','name as j2_name'],
        'type' => 'left',
        'where' => ['name' => 'Sasha'],
        'operand' => ['<>'],
        'condition' => ['AND'],
        'on' => [
        'table' => 'teachers',
        'fields' => ['id', 'parent_id']
        ]
        ],
        ]


    ]);
         */
    // Сюда придет некий массив данных - понятный для контроллера
    final public function get($table, $set = [])
    {
        //А наша модель эти параметры разберет, преобразует в SQL запрос. SQL запрос  - отправят в
        // (for ($i=0; $i < $result->num_rows; $i++) {
        //                        $res[] = $result->fetch_assoc();
        //                    }

        //(стр 44). Тут соберется в понятный для индексного контроллера вид ( core\admin\controllers\IndexController) и
        // передаст в переменную $res

        // Т.е. Вся работа с ДБ идет через модели


        $fields = $this->createFields($set, $table);

        $order = $this->createOrder($set, $table);

        $where = $this->createWhere($set, $table);

        if(!$where) $newWhere = true;
        else $newWhere = false;
        $joinArr = $this->createJoin($set, $table, $newWhere);

        $fields .= $joinArr['fields'];
        $join = $joinArr['join'];
        $where .= $joinArr['where'];

//        "id,name,fio,"

        $fields = rtrim($fields, ',');

        $limit = $set['limit'] ? 'LIMIT ' . $set['limit'] : '';

        $query = "SELECT $fields FROM $table $join $where $order $limit";

//        exit($query);
        return $this->query($query);

    }

    protected function createFields($set, $table = false)
    {
        //Если в $set['fields'] - что-то пришло тогда он им и останется. Если нет - то появится * (т.е. - выбрать все)
        $set['fields'] = (is_array($set['fields']) && !empty($set['fields'])) ? $set['fields'] : ['*'];

        $table = $table ? $table . '.' : '';

        $fields = '';

        foreach ($set['fields'] as $field) {
            $fields .= $table . $field . ',';
        }
        return $fields;

    }

    protected function createOrder($set, $table = false)
    {

        $table = $table ? $table . '.' : '';

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

        $table = $table ? $table . '.' : '';

        $where = '';

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
            return compact('fields', 'join', 'where');
        }
}
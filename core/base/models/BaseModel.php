<?php


namespace core\base\models;


use core\base\controllers\Singleton;
use core\base\exceptions\DbException;
use http\Params;

class BaseModel extends BaseModelMethods
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

    /**
     * @param $table - таблица для вставки данных
     * @param array $set - массив параметров:
     * fields => [ поле => значение]; если не указан - то обрабатывается $_POST [поле => значение]
     * разрешена передача например NOW() в качестве MySQL функции - обычно строкой
     * files => [поле => значение]; можна подать массив вида [ поле => массив значений ]];
     * except => ['исключение 1', 'исключение 2'] - исключает данные элементы массива из добавления в запрос
     * return_id => true|false - возвращать или нет идентификатор вставленной записи
     * @return mixed
     */

    // 26-й урок: Добавили возможность $set указывать пустым массивом, подозреваем, что у нас есть что-то в посте.
    // Тогда достаточно $table, а система сама разберет массив $_POST.
    final public function add($table, $set = []) {

        $set['fields'] = (is_array($set['fields']) && !empty($set['fields'])) ? $set['fields'] : $_POST;


        if(!$set['fields'] && !$set['files']) return false; // Что продолжать если везде - пусто.
        $set['files'] = (is_array($set['files']) && !empty($set['files'])) ? $set['files'] : false;
        $set['return_id'] = $set['return_id'] ? true : false;
        $set['except'] = (is_array($set['except']) && !empty($set['except'])) ? $set['except'] : false;

        // До этого запроса -мы должны принять некий массив вставки. Собирать эти данные будет иной метод -  createInsert().
        // Mетод вернет fields, values.

        // Наш $insertArr из createInsert BaseModelMethods поппадает сюда. Если что-то пришло :
        //        $query = "INSERT INTO $table ({$insertArr['fields']}) VALUES ({$insertArr['values']}))";
        //        Если - нет : false
        $insertArr = $this->createInsert($set['fields'],  $set['files'], $set['except']);
        if ($insertArr) {
            $query = "INSERT INTO $table ({$insertArr['fields']}) VALUES ({$insertArr['values']})";
            return $this->query($query, 'c', $set['return_id']);
        }

        return false;


    }

    final public function showColumns($table) {
        $query = "SHOW COLUMNS FROM $table";
        $res = $this->query($query);

        $columns = [];
        //
        if($res) {
            foreach ($res as $row) {
                $columns[$row['Field']] = $row;
                //Надо в корень результирующего массива положить ячейку, которая явсляется первичным ключем
                if ($row['Key'] === 'PRI') $columns['id_row'] = $row['Field'];
            }
        }

        return $columns;
    }
    final public function edit($table, $set = []) {

        $set['fields'] = (is_array($set['fields']) && !empty($set['fields'])) ? $set['fields'] : $_POST;


        if(!$set['fields'] && !$set['files']) return false; // Что продолжать если везде - пусто.
        $set['files'] = (is_array($set['files']) && !empty($set['files'])) ? $set['files'] : false;

        $set['except'] = (is_array($set['except']) && !empty($set['except'])) ? $set['except'] : false;

        if(!$set['all_rows']) {
            // Метод createWhere() - написан нами настолько хорошо и многофункционально. Что не дополнительных проверок,
            // не других действий с $set['where'] - мы выполнять не будем - это отлично сделает метод createWhere() - самостоятельно.
            if($set['where']) {
                //
                $where = $this->createWhere($set);
            } else {
                // Мы хотим обновить все данные, которые пришли посредством массива $_POST. Но надо найти критерий
                // по какому мы будем обновлять эти данные в БД.
                // Мы знаемто, что это должен быть первичный ключ.

                $columns = $this->showColumns($table);
                if(!$columns) return false;
                if($columns['id_row'] && $set['fields'][$columns['id_row']]) {
                 $where = 'WHERE ' . $columns['id_row'] . '=' . $set['fields'][$columns['id_row']];
                 // Поскольку  у нас автоинкрементное поле, значит не надо его дальше кидать в запрос.
                 // Поэтому unset $set['fields'][$columns['id_row']]
                    unset($set['fields'][$columns['id_row']]);
                }
            }
        }
        $update = $this->createUpdate($set['fields'],  $set['files'], $set['except']);
        // Так должен выглядеть запрос UPDATE:  $query = "UPDATE teachers SET name ='Masha', surname = 'Ivanovna' WHERE id = 1";
        $query = "UPDATE $table SET $update $where";

        return $this->query($query, 'u');
    }


    /**
    @param $table - Таблицы базы даных
    'fields' => ['id', 'name'],
    'where' => ['name' => 'masha', 'surname' => 'Sergeevna', 'fio' => 'Andrey', 'car' => 'Porshe', 'color' => $color],
    'operand' => ['IN', 'LIKE%', '<>', '=', 'NOT IN'],
    'condition' => [ 'AND', 'OR'],
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

    public function delete($table, $set) {

        $table = trim($table);
        $where = $this->createWhere($set, $table);
        $columns = $this->showColumns($table);
        // $columns для рабочей таблицы - должны быть в любом случае. Проверяем - если пришли какие-то поля - будем обновлять эти поля .
        // А если $set['fields'] - не пришли, тогда мы будем удалять что-то из таблицы
        
        if(!$columns) return false;

        if(is_array($set['fields']) && !empty($set['fields'])) {
            // Первое, что проверяем - а не прислали ли сюда поле с первичным ключем.
            if($columns['id_row']) {
                // Чтобы удалить элемент из массива - мало знать, что он есть. Надо получить его ключ.
                // array_search — Осуществляет поиск данного значения в массиве и возвращает ключ первого найденного
                // элемента в случае удачи или false если не найден
               $key = array_search($columns['id_row'], $set['fields']);
               // Если есть ключ, удаляем ячейку его содержащую.
               if($key !== false) unset($set['fields']['key']);

            }
            $fields = [];
            foreach ($set['fields'] as $field) {
                $fields[$field] = $columns[$field]['Default'];
            }
            //
            $update = $this->createUpdate($fields, false, false);

            $query = "UPDATE $table SET $update $where";

        } else {

            $joinArr = $this->createJoin($set, $table);
            $join = $joinArr['join'];
            $joinTables = $joinArr['tables'];
            // Должны обязательно указать первую нашу $table - иначе ничего не удалится, будет еще и натыкано кучу запятых.
            // Учитывая что есть $joinTables, которые пришли с запятыми вначале.
            // А если $joinTables - не будет, то здесбь будет одна таблица и просто сработает полный синтаксис запроса
            //  $query = "DELETE FROM category, products FROM category LEFT JOIN products ON category.id = products.id WHERE id = 1";
            $query = 'DELETE ' . $table . $joinTables . ' FROM ' . $table . ' ' . $join . ' ' . $where;

        }
        return $this->query($query, 'd');
    }
}
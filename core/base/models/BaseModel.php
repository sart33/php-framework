<?php


namespace core\base\models;


use core\base\controllers\Singleton;
use core\base\exceptions\DbException;

class BaseModel
{
    use Singleton;

    protected $db;

    private function __construct()
    {
        $this->db = @new \mysqli(HOST, USER, PASS, DB_NAME);

        if($this->db->connect_error) {
            throw new DbException('Error connecting to database: '
            . $this->db->connect_errno . ' ' . $this->db->connect_error);
        }

        $this->db->query("SET NAMES UTF8");
    }

    final public function query($query, $crud = 'r', $return_id = false) {
    //  В свойстве db - хранится объект библиотеки mysqli в котором уже хранится идентификатор подключения
        // и тут можно уже использовать метод query объекта mysqli
        // В $result приходит объект содержащий выборку из базы даных.
        $result = $this->db->query($query);
        if ($this->db->affected_rows === -1) {
            throw new DbException('error in SQL query: ' .$query . ' - ' . $this->db->errno . ' ' . $this->db->error);
        }
    // При помощи оператора множественного выбора switch мы проверяем а что находится в переменной $crud
        switch ($crud) {

            case 'r':
                // num_rows - это свойство нашего объекта $result
                if($result->num_rows) {
                    $res = [];

                    for ($i=0; $i < $result->num_rows; $i++) {
                        $res[] = $result->fetch_assoc();
                    }
                    return $res;
                }

                return false;

                break;

            case 'c':

                if($return_id) return $this->db->insert_id;

                return true;

                break;

            default:

                return true;

                break;

        }
    }

    /**
     *         $res = $db->get($table, [
    'fields' => ['id', 'name'],
    'where' => ['fio'=> 'Smirnova', 'name' => 'Masha', 'surname' => 'Sergeevna'],
    'operand' => ['=', '<>'],
    'condition' => ['AND'],
    'order' => ['fio', 'name'],
    'order_direction' => ['ASC', 'DESC'],
    'limit' => '1'


    ]);
     */
    // Сюда придет некий массив данных - понятный для контроллера
    final public function get($table, $set = []) {
        //А наша модель эти параметры разберет, преобразует в SQL запрос. SQL запрос  - отправят в
        // (for ($i=0; $i < $result->num_rows; $i++) {
        //                        $res[] = $result->fetch_assoc();
        //                    }

        //(стр 44). Тут соберется в понятный для индексного контроллера вид ( core\admin\controllers\IndexController) и
        // передаст в переменную $res

        // Т.е. Вся работа с ДБ идет через модели


        $fields = $this->createFields($table, $set);
        $where = $this->createWhere($table, $set);
        $joinArr = $this->createJoin($table, $set);

        $fields .= $joinArr['fields'];
        $join = $joinArr['join'];
        $where .= $joinArr['where'];

//        "id,name,fio,"

        $fields = rtrim($fields, ',');

        $order = $this->createOrder($table, $set);

        $limit = $set['limit'] ? $set['limit'] : '';

        $query = "SELECT $fields FROM $table $join $where $order $limit";

        return $this->query($query);

    }
}
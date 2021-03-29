<?php


namespace core\admin\controllers;


use core\admin\models\Model;
use core\base\controllers\BaseController;
use core\base\exceptions\RouteException;
use core\base\settings\Settings;

abstract class BaseAdmin extends BaseController
{
    protected $model;

    protected $table;
    protected $columns;
    protected $data;

    protected $menu;
    protected $title;


    // Этот абстрактный класс будет отвечать за сборку нашей страницы.
    // За подключения хедера и футера.
    //  А раз он отвечает за статические блоки, то именно он должен выполнить инициализацию скриптов и стилей.
    

    protected function inputData() {
        $this->init(true);
        $this->title = 'VG engine';

        if (!$this->model) $this->model = Model::instance();
        if (!$this->menu) $this->menu = Settings::get('projectTables');
        // Заголовки ответов браузеру. При работе с изображениями - могут возникнуть большие проблемы,
        // связанные с кешированием файлов браузером. поэтому будем сразу отправлять заголовки что не надо это кешировать.
        // Метод отправляющий заголовки с запретом на кеширование.

        $this->sendNoCacheHeaders();
    }

    protected function outputData() {

    }

    protected function sendNoCacheHeaders() {
        header("Last-Modified: " . gmdate("D, d m Y H:i:s") . " GMT");
        header("Cache-Control: no-cache, must-revalidate" );
        header("Cache-Control: max-age=0");
        // Этот заголовок - исключительно, для IE. post-chek - говорит IE о том, что ему необходимо проверить данные обязательно,
        // после того как он эти данные загрузит. Покажет пользователю данные - а дальше их все-рвно надо проверить.
        // pre-chek - говорит, что данные необходимо обязательно проверить перед показом кеша. Два этих значения выставленные в ноль скажут браузеру, что ему необходимо загружать эти данныее в обязательном порядке.
        header("Cache-Control: post-chek=0, pre-chek = 0");

        // Ранеее могли видеть еще два заголовка:
//        header("Expires: data"); // - давно устарел и его полностью переопределяет "Cache-Control".
//        header("Pragma "); // - Устарел - более 20 лет назад.

    }

    protected function execBase() {
        self::inputData();
    }

    protected function createTableData() {
        // Если до этого свойство $thisTable -нигде не было заполненно - то надо будет в этом методе с ним поработать.
        if(!$this->table) {
            // Таблица может приидти в свойстве parameters - которое сформировал роут контроллер.
            // И надо проверить - пришло ли что-то в параметр. Пришло - ключ нулевого элемента параметров- и есть наша таблица.
            // Не пришло ничего - значит надо откуда-то эту табличку тащить.
            //               $parameters = [
//                   'teachers' => ''
//               ]; - в этом случае ($this->parameters['teachers']; - выдаст false.
            // А $this->parameters - будет true / Т.е. Эта проверка подойдет
            if($this->parameters) $this->table = array_keys($this->parameters)[0];
                else $this->table = Settings::get('defaultTable');


        }
        $this->columns = $this->model->showColumns($this->table);

        if(!$this->columns) new RouteException('no fields in this table - ' . $this->table, 2);

    }

    protected function createData($arr = [], $add = true ) {
        // Если массив $arr - пришел и флаг ($add) - стоит в true,
        // то то что есть в $arr надо добавить к базовому запросу.
        // Если false - то работаем только с массивом $arr
        $fields = [];
        $order = [];
        $orderDirection = [];

        if ($add) {

         if(!$this->columns['id_row']) return $this->data = [];

        // Воспользуемся здесь псевдонимом.
         $fields[] .= $this->columns['id_row'] . ' as id';
         if($this->columns['name']) $fields['name'] = 'name';
            if($this->columns['img']) $fields['img'] = 'img';
            // В метод get мы присылаем массив. А имена ячейкам массива мы даем.
            // поскольку проверяя в цикле - есть ли эти ячейки. Если - нет - будем искать пр. name в других записях.
            // С img - аналогично.
            if(count($fields) < 3) {
                foreach ($this->columns as $key => $item) {
                    if(!$fields['name'] && strpos($key, 'name') !== false) {
                        $fields['name'] = $key . ' as name';
                    }
                    if(!$fields['img'] && strpos($key, 'img') === 0) {
                        $fields['img'] = $key . ' as img';
                    }
                }
            }
            if($arr['fields']) {
                // Склейка массивов.  который в классе Settings находится.
                $fields = Settings::instance()->arrayMergeRecursive($fields, $arr['fields']);
            }

            if($this->columns['parent_id']) {
                if(!in_array('parent_id', $fields))  $fields[] = 'parent_id';
                    $order[] = 'parent_id';
                }
            //Если есть ячейка - позиция в меню - то добаляем ее в $order, чтобы иметь возможность по ней сотртироваться.
            if($this->columns['menu_position']) $order[] = 'menu_position';
            //Еще может существовать поле $date. Сортировка по дате.
            elseif ($this->columns['date']) {


                if($order) $orderDirection = ['ASC', 'DESC'];
                    else $orderDirection = ['DESC'];

                $order[] = 'date';
                // После этого всего - еще надо склеить два массива.
            }
            if($arr['order']) {
                // Склейка массивов.  который в классе Settings находится.
                $order = Settings::instance()->arrayMergeRecursive($order, $arr['order']);
            }
            if($arr['order_direction']) {
                // Склейка массивов.  который в классе Settings находится.
                $orderDirection = Settings::instance()->arrayMergeRecursive($orderDirection, $arr['order_direction']);
            }

        } else {

            if(!$arr) return $this->data = [];

            $fields = $arr['fields'];
            $order = $arr['order'];
            $orderDirection = $arr['orderDirection'];

        }

        $this->data = $this->model->get($this->table, [
            'fields' => $fields,
            'order' => $order,
            'order_direction' => $orderDirection
        ]);

        exit();
    }
 }
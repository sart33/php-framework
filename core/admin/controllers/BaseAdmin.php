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

    protected function expansion($args = []) {

        //Сначала из таблицы формируем "файл-нейм"?
//        файл 'StudTeachExpansion' - расширение таблицы 'stud_teach';
        $fileName = explode('_', $this->table);
        $className = '';
        foreach ($fileName as $item) $className .= ucfirst($item);
        $class = Settings::get('expansion') . '/' .$className . 'Expansion';
        //Рефлекшеном и записью в лог при выбросе исключения - пользоваться не будем,
        // потому что каж раз писать в лог это исключение не рационально.

        // Проверяем поетому так:
        // Существовует ли файл и доступен ли он для чтения.
        if(is_readable($_SERVER['DOCUMENT_ROOT'] . PATH . $class . '.php')) {
            $class = str_replace('/', '\\', $class);
            // Дальше - создать этот класс. К нему мы будем обращаться неоднократно с точки зрения работы с кодом.
            // В showController - отобразим, дальше в каком-то иметоде еще. Но, если, в showController - это не принципиально,
            // то метод эдит будет работать с данными, когда он их получает из БД.
            // Плюс метод эдит будет еще и модифицировать эти данные - т.е - технически два действия (принять и отдать).
            // Следовательно если многократно вызывать expansion и не отработать его по шаблону синглтон - получим утечки памяти.
            $exp = $class::instance();

            $res = $exp->expansion($args);
        }

    }
 }
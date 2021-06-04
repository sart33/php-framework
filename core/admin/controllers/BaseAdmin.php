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
    protected $foreignData;

    protected $adminPath;

    protected $menu;
    protected $title;

    protected $translate;
    protected $blocks = [];

    // Этот абстрактный класс будет отвечать за сборку нашей страницы.
    // За подключения хедера и футера.
    //  А раз он отвечает за статические блоки, то именно он должен выполнить инициализацию скриптов и стилей.
    

    protected function inputData() {
        $this->init(true);
        $this->title = 'VG engine';

        if (empty($this->model)) $this->model = Model::instance();
        if (empty($this->menu)) $this->menu = Settings::get('projectTables');
        if (empty($this->adminPath)) $this->adminPath = PATH . Settings::get('routes')['admin']['alias'] . '/';

        // Заголовки ответов браузеру. При работе с изображениями - могут возникнуть большие проблемы,
        // связанные с кешированием файлов браузером. поэтому будем сразу отправлять заголовки что не надо это кешировать.
        // Метод отправляющий заголовки с запретом на кеширование.

        $this->sendNoCacheHeaders();
    }

    protected function outputData() {

        if(empty($this->content)) {
            //        func_get_args() — Возвращает массив, содержащий аргументы функции
//        Возвращает массив, в котором каждый элемент является копией соответствующего члена списка аргументов пользовательской функции.
            $args = func_get_arg(0);
            $vars = (!empty($args)) ? $args : [];
            //Путь к нашему представлению
//            if(!$this->template) $this->template = ADMIN_TEMPLATE . 'show';
            //Контент сформировали. Еще нужен хедер и футер.
            $this->content = $this->render($this->template, $vars);
        }
        $this->header = $this->render(ADMIN_TEMPLATE . 'includes/header');
        $this->footer = $this->render(ADMIN_TEMPLATE . 'includes/footer');

        return $this->render(ADMIN_TEMPLATE . 'layouts/default');

    }

    protected function sendNoCacheHeaders() {
        header("Last-Modified: " . gmdate("D, d m Y H:i:s") . " GMT");
        header("Cache-Control: no-cache, must-revalidate" );
        header("Cache-Control: max-age=0");
        // Этот заголовок - исключительно, для IE. post-chek - говорит IE о том, что ему необходимо проверить данные обязательно,
        // после того как он эти данные загрузит. Покажет пользователю данные - а дальше их все-рвно надо проверить.
        // pre-chek - говорит, что данные необходимо обязательно проверить перед показом кеша. Два этих значения выставленные в ноль скажут браузеру, что ему необходимо загружать эти данныее в обязательном порядке.
        header("Cache-Control: post-check=0, pre-check=0");

        // Ранеее могли видеть еще два заголовка:
//        header("Expires: data"); // - давно устарел и его полностью переопределяет "Cache-Control".
//        header("Pragma "); // - Устарел - более 20 лет назад.

    }

    protected function execBase() {
        self::inputData();
    }

    protected function createTableData($settings = false) {
        // Если до этого свойство $thisTable -нигде не было заполненно - то надо будет в этом методе с ним поработать.
        if(empty($this->table)) {
            // Таблица может приидти в свойстве parameters - которое сформировал роут контроллер.
            // И надо проверить - пришло ли что-то в параметр. Пришло - ключ нулевого элемента параметров- и есть наша таблица.
            // Не пришло ничего - значит надо откуда-то эту табличку тащить.
            //               $parameters = [
//                   'teachers' => ''
//               ]; - в этом случае ($this->parameters['teachers']; - выдаст false.
            // А $this->parameters - будет true / Т.е. Эта проверка подойдет
            if(!empty($this->parameters)) $this->table = array_keys($this->parameters)[0];
                else {
                    if(empty($settings)) $settings = Settings::instance();
                    $this->table = $settings::get('defaultTable');
                }

        }

        $this->columns = $this->model->showColumns($this->table);

        if(empty($this->columns)) new RouteException('no fields in this table - ' . $this->table, 2);

    }


    protected function expansion($args = [], $settings = false) {

        //Сначала из таблицы формируем "файл-нейм"?
//        файл 'StudTeachExpansion' - расширение таблицы 'stud_teach';
        $fileName = explode('_', $this->table);
        $className = '';
        foreach ($fileName as $item) $className .= ucfirst($item);
        if($settings === false)  {
            $path = Settings::get('expansion');
        } elseif(is_object($settings)) {
            $path = $settings::get('expansion');
        } else {
            $path = $settings;
        }

        $class = $path  . $className . 'Expansion';

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

             foreach ($this as $name => $value) {
                 // Здесь созданы новые сво-ва в них записаны - новые значения и абсолютно никаких ссылок здесь нет.
//                $exp->$name = &$value;
                 $exp->$name = &$this->$name;
             }

             return $exp->expansion($args);

         } else {

             $file = $_SERVER['DOCUMENT_ROOT'] . PATH . $path . $this->table . '.php';

             extract($args);

             if(is_readable($file)) return include $file;

        }
        return false;
    }

    protected function createOutputData($settings = false) {
        if(!is_object($settings)) $settings = Settings::instance();

        $blocks = $settings->get('blockNeedle');
        $this->translate = $settings->get('translate');

        if(empty($blocks) || !is_array($blocks)) {
            foreach ($this->columns as $name => $item ) {
                if($name === 'id_row') continue;
                if(empty($this->translate[$name])) $this->translate[$name][] = $name;

                $this->blocks[0][] =  $name;
           }

           return;
        }

        $default = array_keys($blocks)[0];
        foreach ($this->columns as $name => $item) {
            if($name === 'id_row') continue;

            $insert = false;

            foreach ($blocks as $block => $value) {
                if(!array_key_exists($block, $this->blocks))  $this->blocks[$block] = [];
                if(in_array($name, $value)) {
                    $this->blocks[$block][] = $name;
                    $insert = true;
                    break;
                }
            }

            // Проверяем - произошла ли вставка.

            if($insert === false) $this->blocks[$default][] = $name;
            if(empty($this->translate[$name])) $this->translate[$name][] = $name;


        }
        return;

    }

    protected function createRadio($settings = false) {
        if(empty($settings)) $settings = Settings::instance();

        $radio = $settings::get('radio');

        if (!empty($radio)) {
            foreach ($this->columns as $name => $item) {
                if(!empty($radio[$name])) {
                    $this->foreignData[$name] = $radio[$name];
                }
            }
        }
    }

 }
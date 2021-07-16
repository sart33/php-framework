<?php


namespace core\admin\controllers;


use core\admin\models\Model;
use core\base\models\BaseModel;
use core\base\controllers\BaseController;
use core\base\exceptions\RouteException;
use core\base\settings\Settings;
use libraries\FileEdit;

abstract class BaseAdmin extends BaseController
{
    protected $model;
    protected $table;
    protected $columns;

    protected $foreignData;

    protected $adminPath;

    protected $menu;
    protected $title;

    protected $alias;
    protected $fileArray;

    protected $messages;

    protected $translate;
    protected $blocks = [];

    protected $templateArr;
    protected $formTemplates;
    protected $noDelete;

    // Этот абстрактный класс будет отвечать за сборку нашей страницы.
    // За подключения хедера и футера.
    //  А раз он отвечает за статические блоки, то именно он должен выполнить инициализацию скриптов и стилей.


    protected function inputData()
    {
        $this->init(true);
        $this->title = 'VG engine';

        if (empty($this->model)) $this->model = Model::instance();
        if (empty($this->menu)) $this->menu = Settings::get('projectTables');
        if (empty($this->adminPath)) $this->adminPath = PATH . Settings::get('routes')['admin']['alias'] . '/';
        if (empty($this->templateArr)) $this->templateArr = Settings::get('templateArr');
        if (empty($this->formTemplates)) $this->formTemplates = Settings::get('formTemplates');
        if (empty($this->messages)) $this->messages = include $_SERVER['DOCUMENT_ROOT'] . PATH . Settings::get('messages') . '/informationMessages.php';

        // Заголовки ответов браузеру. При работе с изображениями - могут возникнуть большие проблемы,
        // связанные с кешированием файлов браузером. поэтому будем сразу отправлять заголовки что не надо это кешировать.
        // Метод отправляющий заголовки с запретом на кеширование.


        $this->sendNoCacheHeaders();
    }

    protected function outputData()
    {

        if (empty($this->content)) {
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

    protected function sendNoCacheHeaders()
    {
        header("Last-Modified: " . gmdate("D, d m Y H:i:s") . " GMT");
        header("Cache-Control: no-cache, must-revalidate");
        header("Cache-Control: max-age=0");
        // Этот заголовок - исключительно, для IE. post-chek - говорит IE о том, что ему необходимо проверить данные обязательно,
        // после того как он эти данные загрузит. Покажет пользователю данные - а дальше их все-рвно надо проверить.
        // pre-chek - говорит, что данные необходимо обязательно проверить перед показом кеша. Два этих значения выставленные в ноль скажут браузеру, что ему необходимо загружать эти данныее в обязательном порядке.
        header("Cache-Control: post-check=0, pre-check=0");

        // Ранеее могли видеть еще два заголовка:
//        header("Expires: data"); // - давно устарел и его полностью переопределяет "Cache-Control".
//        header("Pragma "); // - Устарел - более 20 лет назад.

    }

    protected function execBase()
    {
        self::inputData();
    }

    protected function createTableData($settings = false)
    {
        // Если до этого свойство $thisTable -нигде не было заполненно - то надо будет в этом методе с ним поработать.
        if (empty($this->table)) {
            // Таблица может приидти в свойстве parameters - которое сформировал роут контроллер.
            // И надо проверить - пришло ли что-то в параметр. Пришло - ключ нулевого элемента параметров- и есть наша таблица.
            // Не пришло ничего - значит надо откуда-то эту табличку тащить.
            //               $parameters = [
//                   'teachers' => ''
//               ]; - в этом случае ($this->parameters['teachers']; - выдаст false.
            // А $this->parameters - будет true / Т.е. Эта проверка подойдет
            if (!empty($this->parameters)) $this->table = array_keys($this->parameters)[0];
            else {
                if (empty($settings)) $settings = Settings::instance();
                $this->table = $settings::get('defaultTable');
            }

        }

        $this->columns = $this->model->showColumns($this->table);

        if (empty($this->columns)) new RouteException('no fields in this table - ' . $this->table, 2);

    }


    protected function expansion($args = [], $settings = false)
    {

        //Сначала из таблицы формируем "файл-нейм"?
//        файл 'StudTeachExpansion' - расширение таблицы 'stud_teach';
        $fileName = explode('_', $this->table);
        $className = '';
        foreach ($fileName as $item) $className .= ucfirst($item);
        if ($settings === false) {
            $path = Settings::get('expansion');
        } elseif (is_object($settings)) {
            $path = $settings::get('expansion');
        } else {
            $path = $settings;
        }

        $class = $path . $className . 'Expansion';

        //Рефлекшеном и записью в лог при выбросе исключения - пользоваться не будем,
        // потому что каж раз писать в лог это исключение не рационально.

        // Проверяем поетому так:
        // Существовует ли файл и доступен ли он для чтения.
        if (is_readable($_SERVER['DOCUMENT_ROOT'] . PATH . $class . '.php')) {
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

            if (is_readable($file)) return include $file;

        }
        return false;
    }

    protected function createOutputData($settings = false)
    {
        if (!is_object($settings)) $settings = Settings::instance();

        $blocks = $settings->get('blockNeedle');
        $this->translate = $settings->get('translate');
// Если blockNeedle не массивили вообще пуста: мы создаем массив, в нулевом элементе которого лежат все поля.
        if (empty($blocks) || !is_array($blocks)) {
            foreach ($this->columns as $name => $item) {
                if ($name === 'id_row') continue;
                if (empty($this->translate[$name])) $this->translate[$name][] = $name;

                $this->blocks[0][] = $name;
            }

            return;
        }

        $default = array_keys($blocks)[0];
        foreach ($this->columns as $name => $item) {
            if ($name === 'id_row') continue;

            $insert = false;

            foreach ($blocks as $block => $value) {
                if (!array_key_exists($block, $this->blocks)) $this->blocks[$block] = [];
                if (in_array($name, $value)) {
                    $this->blocks[$block][] = $name;
                    $insert = true;
                    break;
                }
            }

            // Проверяем - произошла ли вставка.

            if ($insert === false) $this->blocks[$default][] = $name;
            if (empty($this->translate[$name])) $this->translate[$name][] = $name;


        }
        return;

    }

    protected function createRadio($settings = false)
    {
        if (empty($settings)) $settings = Settings::instance();

        $radio = $settings::get('radio');

        if (!empty($radio)) {
            foreach ($this->columns as $name => $item) {
                if (!empty($radio[$name])) {
                    $this->foreignData[$name] = $radio[$name];
                }
            }
        }
    }

    protected function checkPost($settings = false)
    {

        if (!empty($this->isPost())) {
            $this->clearPostFields($settings);
            $this->table = $this->clearStr($_POST['table']);
            unset ($_POST['table']);
            if (!empty($this->table)) {
                $this->createTableData($settings); // Внутри которого ShowColumns проверит есть ли данные о таблицах и если нет -
                // екзеппшн. Это позволит отсеять вредоносный/нежелательный код пришедший методом POST.
                $this->editData();
            }
        }
    }

    protected function addSessionData($arr = [])
    {
        if (empty($arr)) $arr = $_POST;

        foreach ($arr as $key => $item) {
            $_SESSION['res'][$key] = $item;
        }

        $this->redirect();

    }

    protected function countChar($str, $counter, $answer, $arr)
    {
        if (mb_strlen($str) > $counter) {
            $str_res = mb_str_replace('$1', $answer, $this->messages['count']);
            $str_res = mb_str_replace('$2', $counter, $str_res);

            $_SESSION['res']['answer'] = '<div class="error">' . $str_res . '</div>';
            $this->addSessionData($arr);

        }
    }

    protected function emptyFields($str, $answer, $arr = [])
    {

        if (empty($str)) {
            $_SESSION['res']['answer'] = '<div class="error">' . $this->messages['empty'] . ' ' . $answer . '</div>';
            $this->addSessionData($arr);
        }

    }

    protected function clearPostFields($settings, &$arr = [])
    {
        // Этот метод - должен уметь обрабатывать и не только пост но и др массивы.
        if (empty($arr)) $arr = &$_POST; // & - Ссылка
        if (empty($settings)) $settings = Settings::instance();

//       $id = !empty($_POST[$this->columns['id_row']]) ?: false;


        $validate = $settings::get('validation');
        if (empty($this->translate)) $this->translate = $settings::get('translate');

        foreach ($arr as $key => $item) {
            // Кажд элемент может быть массивом, - поэтому метод будет рекурсивным
            if (is_array($item)) {
                $this->clearPostFields($settings, $item); // Вот и рекурсия в случае если массив
            } else {
                if (is_numeric($item)) {
                    // $item - это отдельная переменная интерфейса Итератор, которая никак не взаимодействует с нашим массивом.
                    $arr[$key] = $this->clearNum($item);
                }

                if (!empty($validate)) {

//                    if(!empty(array_key_exists($key, $validate))) {

//                    }
                    if (!empty($validate[$key])) {
                        if (!empty($this->translate[$key])) {
                            $answer = $this->translate[$key][0];
                        } else {
                            $answer = $key;
                        }

                        if (!empty($validate[$key]['crypt'])) {
                            if (!empty($id)) {
                                if (empty($item)) {
                                    unset ($arr[$key]);
                                    continue;
                                }

                                $arr[$key] = md5($item);
                            }

                        }

                        if (!empty($validate[$key]['empty'])) $this->emptyFields($item, $answer);
                        if (!empty($validate[$key]['trim'])) $arr[$key] = trim($item);
                        if (!empty($validate[$key]['int'])) $arr[$key] = $this->clearNum($item);
                        if (!empty($validate[$key]['count'])) $this->countChar($item, $validate[$key]['count'], $answer, $arr);

                    }
                }
            }
        }

        return true;
    }

    protected function editData($returnId = false)
    {

        $id = false;
        $method = 'add';
        $where = [];
        // Если существует вот эта ячейка, то переменную id (обязательно почистив (clearNum))
        if (!empty($_POST[$this->columns['id_row']])) {
            $id = is_numeric($_POST[$this->columns['id_row']]) ?
                $this->clearNum($_POST[$this->columns['id_row']]) :
                $this->clearStr($_POST[$this->columns['id_row']]);
            if (!empty($id)) {
                $where = [$this->columns['id_row'] => $id];
                $method = 'edit';
            }
        }

        foreach ($this->columns as $key => $item) {
            if (!empty($item['Type'])) {
//                if ($key === 'id_row') continue;
                if ($item['Type'] === 'date' || $item['Type'] === 'datetime') {
                    // Еще один вариант записи if
                    empty($_POST[$key]) && $_POST[$key] = 'NOW()';
                }
            }
        }

        $this->createFile();

        $this->createAlias($id);

        $this->updateMenuPosition();

        $except = $this->checkExceptFields();

//        $except = ['content'];

        $res_id = $this->model->$method($this->table, [
            'files' => $this->fileArray,
            'where' => $where,
            'return_id' => true,
            'except' => $except
        ]);

        if (empty($id) && $method === 'add') {
            $_POST[$this->columns['id_row']] = $res_id;
            $answerSuccess = $this->messages['addSuccess'];
            $answerFail = $this->messages['addFail'];

        } else {
            $answerSuccess = $this->messages['editSuccess'];
            $answerFail = $this->messages['editFail'];
        }

        $this->expansion(get_defined_vars());

        $result = $this->checkAlias($_POST[$this->columns['id_row']]);

        if($res_id) {

            $_SESSION['res']['answer'] = '<div class="success">' . $answerSuccess . '</div>';

            if(empty($returnId)) $this->redirect();

            return $_POST[$this->columns['id_row']];

        } else {
            $_SESSION['res']['answer'] = '<div class="error">' . $answerFail . '</div>';

            if(empty($returnId)) $this->redirect();
        }

    }


    protected function createAlias($id = false) {

        if(!empty($this->columns['alias'])) {

        // Не всегда нужно поле в админке для заполнения элиаса. Иногда лучше "поберечь контент менеджера"))
            if (empty($_POST['alias'])) {
                if (!empty($_POST['name'])) {
                    $aliasStr = $this->clearStr($_POST['name']);
                } else {
                    foreach ($_POST as $key => $item) {
                        if (!empty($item) && strpos($key, 'name') !== false) {
                            $aliasStr = $this->clearStr($item);
                            break;
                        }
                    }
                }
            } else {

                $aliasStr = $_POST['alias'] = $this->clearStr($_POST['alias']); // Сокращенная форма: одна строка вместо двух.

            }

            $textModify = new \libraries\TextModify();
            $alias = $textModify->translit($aliasStr);
//            $alias = 'teachers_111';

            $where['alias'] = $alias;
            $operand = '=';

            if(!empty($id)) {
                $where[$this->columns['id_row']] = $id;
                $operand[] = '<>';
            }

        // Как-то так: WHERE alias = 'alias' AND WHERE id <> 'id'
        $resAliasArr = $this->model->get($this->table, [
                'fields' => ['alias'],
                'where' => $where,
                'operand' => $operand,
                'limit' => '1'

            ]);

            if(is_array($resAliasArr)) {
                $resAlias = array_shift($resAliasArr);

            } else {$resAlias = $resAliasArr; }

            if(empty($resAlias)) {

                $_POST['alias'] = $alias;

            } else {

                $this->alias = $alias;
                $_POST['alias'] = '';

                }

            if(!empty($_POST['alias']) && !empty($id)) {
                method_exists($this, 'ckeckOldAlias') && $this->checkOldAlias($id);

            }
        }
    }

    protected function checkAlias($id) {

        if (!empty($id)) {
            if(!empty($this->alias)) {
                $this->alias .= '_' . $id;
                $this->model->edit($this->table, [
                    'fields' => ['alias' => $this->alias],
                    'where' => [$this->columns['id_row'] => $id]
                ]);
                return true;
            }
        }
        return false;
    }

    public function createFile() {

        $fileEdit = new FileEdit();
        $this->fileArray = $fileEdit->addFile();

    }

    protected function updateMenuPosition() {

    }

    protected function checkExceptFields($arr = []) {

        if(empty($arr)) $arr = $_POST;

        $except = [];

        if(!empty($arr)) {

            foreach ($arr as $key => $item) {
                if(empty($this->columns[$key])) $except[] = $key;
            }
            
        }

        return $except;

    }

}
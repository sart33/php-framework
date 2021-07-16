<?php


namespace core\admin\controllers;

use core\base\controllers\BaseMethods;

class CreatesitemapController extends BaseAdmin
{

    use BaseMethods;
// Клас с методами парсящими сайт

// Lkz 404? 403 и т.д.
    protected array $all_links = [];
    protected array $temp_links = [];
    protected array $bad_links = [];

    protected int $maxLinks = 200;
    protected string $parsingLogFile = 'parsing_log.txt';
    protected array $fileArr = ['jpg', 'png', 'jpeg', 'jif', 'webp', 'xls', 'xlsx', 'mkv', 'avi', 'mp4', 'mp3', 'mpeg'];

    protected array $filterArr = [
      'url' => [],
      'get' => []
    ];



    public function inputData($linksCounter = 1, $redirect = true) {

        $linksCounter = $this->clearNum($linksCounter);

        if(!function_exists('curl_init')) {

            $this->cancel(0, 'Library CURL as absent. Creation of sitemap impossible', '', true);

        }

        if(empty($this->userId)) $this->execBase();

        if(empty($this->checkParsingTable())) {

            $this->cancel(0, 'You have problem width database table parsing data', '', true);

        }

        set_time_limit(0);

        //Надо узнать хранится ли что-то сейчас в БД. Если цикл парсинга был закончен - будет удаляться все,
        // что есть в данных полях таблицы.
                $table_rows = [];
        if(!empty($this->model->get('parsing_data'))) {
            $reserve = $this->model->get('parsing_data')[0];



            foreach ($reserve as $name => $item) {

                $table_rows[$name] = '';

                // $ Здесь - берем свойство из переменной
                if(!empty($item)) $this->$name = json_decode($item);
                elseif($name === 'all_links' || $name === 'temp_links') $this->$name = [SITE_URL];
                
            }

            $this->maxLinks = (int)$linksCounter > 1 ? ceil($this->maxLinks / $linksCounter) : $this->maxLinks;


            while (!empty($this->temp_links)) {

                $tempLinksCount = count($this->temp_links);

                $links = $this->temp_links;
                $this->temp_links = [];

                if($tempLinksCount > $this->maxLinks) {

                //Массив, который хранится в массиве $links мы поделим на заданное кол-во частей в большую сторону.
                 $links = array_chunk($links, ceil($tempLinksCount / $this->maxLinks));

                 $countChunks = count($links);

                 for ($i = 0; $i < $countChunks; $i++) {

                     $this->parsing($links[$i]);


                    // Разрегистрируем, то, что уже распарсено. И не трогаем то, что еще - нет.
                     unset ($links[$i]);

                     if(!empty($links)) {

                         foreach ($table_rows as $name => $item) {
                            // Если - да, - то делаем деструктивное присваивание.
                             if($name === 'temp_links') $table_rows[$name] = json_encode(array_merge(...$links));
                                else $table_rows[$name] = json_encode($this->$name);
                         }
                         // На следующую итерацию - на всякий случай сохранили
                         $this->model->edit('parsing_data', [
                            'fields' => $table_rows
                         ]);
                     }
                 }


                } else {

                    $this->parsing($links);
                }

                foreach ($table_rows as $name => $item) {
                    $table_rows[$name] = json_encode($this->$name);
                }

                $this->model->edit('parsing_data', [
                    'fields' => $table_rows
                ]);
            }

            foreach ($table_rows as $name => $item) {
                $table_rows[$name] = '';
            }

        }
        $this->model->edit('parsing_data', [
            'fields' => $table_rows
        ]);

        if(!empty($this->all_links)) {

            foreach ($this->all_links as $key => $link) {

                if (empty($this->filter($link)) || in_array($link, $this->bad_links)) unset($this->all_links[$key]);
            }

        }

//        До createSitemap() - автор предлагает проходить еще раз в массиве и уже там чистить массив all_links
//        это даст возможность получать больше контента со страниц с динамической пагинацией на JS в том числе того,
//        который появляется при скролинге страницы вниз. Либо без скролинга, но с применением фильтров этот контент
//        появляется на странице с автодополннием контента.
//
//        А если - возьмем и удалим ссылку - то по ней больше парсер - никогда в жизни не пройдет.
//        Она не попадет в массив $urls на вход метода parsing/
//        Итого: Все собираем, а чистим - потом фильтром.

//        Обходим масссив еще раз:

        if (!empty($this->all_links)) {

            foreach ($this->all_links as $key => $link) {

                if(empty($this->filter($link))) unset($this->all_links[$key]);
                
            }
        }

        $this->createSitemap();

        if(!empty($redirect)) {

        empty($_SESSION['res']['answer']) && $_SESSION['res']['answer'] = '<div class="success">Site is created</div>';

        $this->redirect();

    } else {

            $this->cancel(1, 'Sitemap is created! ' . count($this->all_links) . ' links', '', true);
        }
    }



    protected function parsing($urls)
    {

        if(!$urls) return;

        $curlMulti = curl_multi_init();

        $curl = [];

        foreach ($urls as $i => $url) {
            $curl[$i] = curl_init();
            curl_setopt($curl[$i], CURLOPT_URL, $url);
            curl_setopt($curl[$i], CURLOPT_RETURNTRANSFER, true);
            // Возвращать ли заголовки, - Да.
            curl_setopt($curl[$i], CURLOPT_HEADER, true);
            // Следовать ли curl-у за редиректами, тоже - Да.
            curl_setopt($curl[$i], CURLOPT_FOLLOWLOCATION, 1);
            // Таймаут ожидания ответа от сервера
            curl_setopt($curl[$i], CURLOPT_TIMEOUT, 120);
//            curl_setopt($curl[$i], CURLOPT_CONNECTTIMEOUT, 78);
            // Часто страницы сжаты gzip-ом. Браузер раскодирует ее автоматически.
            // Но если не указать эту опцию и приидет сжатая страница - то не отработает не одна регулярка.
            // Регулярка не увидит эту страницу, хотя заголовки мы видеть будем ч/з браузер, но php не сработает.
            // А с этой настройкой - все - хорошо.
            curl_setopt($curl[$i], CURLOPT_ENCODING, 'gzip,deflate');

            curl_multi_add_handle($curlMulti, $curl[$i]);
        }

        do {

            // $active - активность соединения. Если $active = 0, - значит cURL все дескрипторы - обошел.
            $status = curl_multi_exec($curlMulti, $active);
            $info = curl_multi_info_read($curlMulti);

            if($info !== false) {

                if($info['result'] !== 0) {
                     $i = array_search($info['handle'], $curl);
                     $error = curl_errno($curl[$i]);
                     $message = curl_error($curl[$i]);
                     $header = curl_getinfo($curl[$i]);

                     if($error !== 0) {
                         $this->cancel(0, 'Error loading '. $header['url'] . ' http code: ' .
                             $header['http_code'] . ' error: ' . $error . ' message: ' . $message);
                     }
                }
            }


            if($status > 0) {
                // тут работаем относительно статуса выше относительно info.
                // Поэтому и используем две разних возможности сURL (curl_errno, curl_error и curl_multi_strerror
                // в последнем случае.
                $this->cancel(0, curl_multi_strerror($status));
            }

        } while ($status === CURLM_CALL_MULTI_PERFORM || $active);

        $result = [];

        foreach ($urls as $i => $url) {

            // Возвращает, то - что вернулось в память стека мультикурла. После необходимо удалить дескриптор $curl[$i]
            // из мультипотока curl_multi_
            $result[$i] = curl_multi_getcontent($curl[$i]);
            curl_multi_remove_handle($curlMulti, $curl[$i]);
            // Удалили. Теперь необходимо закрыть соединение $curl[$i]. Закрываем.
            curl_close( $curl[$i]);

            // u - поиск и по многобайтным кодировкам
            // i - регистронезависимый
            // s - многострочный поиск (убрали поскольку preg_match и так ищет многострочный поиск)
            if (!preg_match('/Content-Type:\s+text\/html/ui', $result[$i])) {

                $this->bad_links[] = $url;

                $this->cancel(0, 'Incorrect content type ' . $url);

                continue;

            }
            if (!preg_match('/HTTP\/\d\.?\d?\s+20\d/ui', $result[$i])) {

                $this->bad_links[] = $url;

                $this->cancel(0, 'Incorrect server code ' . $url);

                continue;

            }

            $this->createLinks($result[$i]);
        }

        curl_multi_close($curlMulti);

    }

    protected function createLinks($content)
    {

        if (!empty($content)) {

            preg_match_all('/<a\s*?[^>]*?href\s*?=\s*?(["\'])(.+?)\1[^>]*?>/ui', $content, $links);

            if (!empty($links[2])) {
//            $links[2] = [];
//            $links[2][0] = 'www.google.com/image.jpg/hallo';
                foreach ($links[2] as $link) {
                    if ($link === '/' || $link === SITE_URL . '/') continue;


                    foreach ($this->fileArr as $ext) {
                        if (!empty($ext)) {
                            $ext = addslashes($ext);
                            $ext = str_replace('.', '\.', $ext);

                            //В кешированных файлах строка не заканчивается расширением и пробелом-
                            // могут идти какие угодно символы и наша проверка не сработает
                            if (preg_match('/' . $ext . '(\s*$|\?[^\/]*$)/ui', $link)) {

                                continue 2;
                            }
                        }
                    }
                    if (strpos($link, '/') === 0) {
                        $link = SITE_URL . $link;
                    }
                    $siteUrl = mb_str_replace('.', '\.', mb_str_replace('/', '\/', SITE_URL));
                    if (!in_array($link, $this->bad_links) &&
                        !preg_match('/^(' . $siteUrl . ')?\/?#[^\/]*?$/ui', $link)
                        && strpos($link, SITE_URL) === 0 && !in_array($link, $this->all_links)) {

                            $this->temp_links[] = $link;
                            $this->all_links[] = $link;
                    }
                }
            }
        }
    }

    protected function filter($link)
    {
        if(!empty($this->filterArr)) {

        foreach ($this->filterArr as $type => $values)
            if(!empty($values)) {
            foreach ($values as $item) {
                    $item = str_replace('/', '\/', addslashes($item));

                if($type === 'url') {

                    if (preg_match('/^[^\?]*' . $item . '/ui', $link)) {
//                        exit;
                        return false;
                    }
                }

                if($type === 'get') {

                    // '?name=masha&surname=ivanova&amp;secondname=ivanovna';
                    // В случае, если указан дополнительный параметр matches, он будет заполнен результатами поиска.
                    // Элемент $matches[0] будет содержать часть строки, соответствующую вхождению всего шаблона,
                    // $matches[1] - часть строки, соответствующую первой подмаске и так далее.
                    if(preg_match('/(\?|&amp;|=|&)'. $item .'(=|&amp;|&|$)/ui', $link, $matches)) {
                        return false;
                    }
                }
            }
        }
    }

        return true;
    }


    // Создали метод создающий таблицу, которая будет хранить временные данные для парсинга - на случай падения сервера.
    protected function checkParsingTable() {

        $tables = $this->model->showTables();

        if(!in_array('parsing_data', $tables)) {

            $query = 'CREATE TABLE parsing_data (all_links longtext, temp_links longtext, bad_links longtext)';

            if(empty($this->model->query($query, 'c')) ||
               empty($this->model->add('parsing_data', ['fields' => ['all_links' => '', 'temp_links' => '', 'bad_links' => '']]))) {
                return false;
            }
        }
        return true;
    }


    // Метод пишущий в лог и завершающий работу скрипта в соответствующих ситуациях
    protected function cancel($success = 0, $message = '', $logMessage = '', $exit = false) {

        $exitArr = [];

        $exitArr['success'] = $success;
        $exitArr['message'] = !empty($message) ? $message : 'ERROR_PARSING';
        $logMessage = !empty($logMessage) ? $logMessage : $exitArr['message'];

        $class = 'success';

        if (empty($exitArr['success'])) {

            $class = 'error';

            $this->writeLog($logMessage, 'parsing_log.txt');

        }
        if($exit) {
            $exitArr['message'] = '<div class="' . $class . '">' . $exitArr['message'] .'</div>';
            exit(json_encode($exitArr));
        }
    }



    protected function createSitemap() {

        $dom = new \domDocument('1.0', 'utf-8');
        $dom->formatOutput = true;

        $root = $dom->createElement('urlset');
        $root->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $root->setAttribute('xmlns:xls', 'http://w3.org/2001/XMLSchema-instance');
        $root->setAttribute('xsi:schemaLocation','http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd');

        // Элемент $root создан при помощи  $dom->createElement,
        // но он хранится пока только в памяти php -он не находится еще в DOM документе....

        $dom->appendChild($root);

        $sxe = simplexml_import_dom($dom);

        if(!empty($this->all_links)) {

            $date = new \DateTime();
            $lastMod = $date->format('Y-m-d') . 'T' .  $date->format('H:i:s+01:00');

            foreach ($this->all_links as $item) {

                $elem = trim(mb_substr($item, mb_strlen(SITE_URL)), '/');
                $elem = trim('/', $elem);

                if(!empty($elem)) {
                    if(is_array($elem)) {
                        $count = '0.' . (count($elem) - 1);
                        $priority = 1 - (float)$count;
                    } else {
                        $priority = '0.5';
                    }
                    if ($priority == 1) $priority = '1.0';

                    $urlMain = $sxe->addChild('url');

                    $urlMain->addChild('loc', htmlspecialchars($item));

                    $urlMain->addChild('lastmod', $lastMod);

                    $urlMain->addChild('changefreq', 'weekly');

                    $urlMain->addChild('priority', $priority);
                }
            }
        }

        $dom->save($_SERVER['DOCUMENT_ROOT'] . PATH . 'sitemap.xml');
    }

}
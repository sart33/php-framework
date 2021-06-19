<?php


namespace core\admin\controllers;

use core\base\controllers\BaseMethods;

class CreatesitemapController extends BaseAdmin
{

    use BaseMethods;
// Клас с методами парсящими сайт

// Lkz 404? 403 и т.д.
    protected $linkArr = [];
    protected $parsingLogFile = 'parsing_log.txt';
    protected $fileArr = ['jpg', 'png', 'jpeg', 'jif', 'webp', 'xls', 'xlsx', 'mkv', 'avi', 'mp4', 'mp3', 'mpeg'];

    protected $filterArr = [
      'url' => ['order'],
      'get' => ['masha']
    ];



    protected function inputData() {

        if(!function_exists('curl_init')) {
            $this->writeLog('CURL library missing');
            $_SESSION['res']['answer'] = '<div class="error">CURL library missing. Unable to create sitemap.</div>';
            $this->redirect();
        }

        set_time_limit(0);

        if(file_exists($_SERVER['DOCUMENT_ROOT'] . PATH . 'log/' . $this->parsingLogFile));
            @unlink($_SERVER['DOCUMENT_ROOT'] . PATH . 'log/' . $this->parsingLogFile);

        $this->parsing(SITE_URL);
        $this->createSitemap();
        empty($_SESSION['res']['answer']) && $_SESSION['res']['answer'] = '<div class="success">Site is created</div>';
        $this->redirect();

    }

    protected function parsing($url, $index = 0)
    {

//        if(mb_strlen(SITE_URL) +1 === mb_strlen($url) &&
//            mb_strrpos($url, '/') === mb_strlen($url) - 1) return;


        $curl = curl_init();

        // Пока не используем многопоточную инициализацию библиотеки curl. На том сервере Дениса - мало ресурсов.
        // Кроме того - метод рекурсивный, а плодить дескрипторы в рекурсии - может быть критично при малых объемах памяти
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        // Возвращать ли заголовки, - Да.
        curl_setopt($curl, CURLOPT_HEADER, true);
        // Следовать ли curl-у за редиректами, тоже - Да.
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        // Таймаут ожидания ответа от сервера
        curl_setopt($curl, CURLOPT_TIMEOUT, 120);
        // Ограничим объем данных, которые будет загружать CURL
        curl_setopt($curl, CURLOPT_RANGE, 0 - 8388608);
        // Выполняет запрос cURL после прописывания всех необходимых параметров.
        $out = curl_exec($curl);
        // Уничтожаем дескриптор $curl и все ссылки на него.
        curl_close($curl);


//        echo ('CURL SUCCESS');
//        exit($out);

        // u - поиск и по многобайтным кодировкам
        // i - регистронезависимый
        // s - многострочный поиск (убрали поскольку preg_match и так ищет многострочный поиск)
        if (!preg_match('/Content-Type:\s+text\/html/ui', $out)) {

            unset($this->linkArr[$index]);

            $this->linkArr = array_values($this->linkArr);

            return;

        }

        if (!preg_match('/HTTP\/\d\.?\d?\s+20\d/ui', $out)) {

            $this->writeLog('Invalid link in parsing - ' . $url, $this->parsingLogFile);

            unset($this->linkArr[$index]);

            $this->linkArr = array_values($this->linkArr);

            $_SESSION['res']['answer'] = '<div class="error">Invalid link in parsing - ' . '<br>Sitemap is created</div>';

            return;
        }
//            $str = '<a    class="class" id="1" href="искомая ссылка" data-id="gdfgdfg">';



        preg_match_all('/<a\s*?[^>]*?href\s*?=\s*?(["\'])(.+?)\1[^>]*?>/ui', $out, $links);

        if (!empty($links[2])) {
            foreach ($links[2] as $link) {
                if($link === '/' || $link === SITE_URL . '/') continue;
                foreach ($this->fileArr as $ext) {
                    if (!empty($ext)) {
                        $ext = addslashes($ext);
                        $ext = str_replace('.', '\.', $ext);

                        if (preg_match('/' . $ext . '\s*$/ui', $link)) {
                            continue 2;
                        }
                    }
                }
                if (strpos($link, '/') === 0) {
                    $link = SITE_URL . $link;
                }
                if (!in_array($link, $this->linkArr) && strpos($link, '#') === false && strpos($link, SITE_URL) === 0) {

                    if ($this->filter($link)) {

                        $this->linkArr[] = $link;
                        $this->parsing($link, count($this->linkArr) - 1);
                    }
                }
            }
        }



    }






    protected function filter($link)
    {
//        $link = 'http//google.com/ord/id?Masha=ASC&amp;Masha=111';

        if(!empty($this->filterArr)) {

        foreach ($this->filterArr as $type => $values)
            if(!empty($values)) {
            foreach ($values as $item) {
                    $item = str_replace('/', '\/', addslashes($item));

                if($type === 'url') {

                    if (preg_match('/' . $item . '.*[\?|$]/ui', $link)) {
//                        exit;
                        return false;
                    }
//

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

    protected function createSitemap() {


    }

}
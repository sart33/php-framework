<?php


namespace core\base\exceptions;


use core\base\controllers\BaseMethods;

class DbException extends \Exception
{
    protected $messages;

    use BaseMethods;


    public function __construct($message = "", $code = 0)
    {
        // Так вызывается метод родительского класса. Так как наш метод переопределяет этот метод
        parent::__construct($message, $code);

        $this->messages = include 'messages.php';

        $error = !empty($this->getMessage()) ? $this->getMessage() : $this->messages[$this->getCode()];

        $error .= "\r\n" . 'file ' . $this->getFile() . "\r\n" . 'In line ' . $this->getLine() . "\r\n";

        //Закоментировал чобы ошибки были видны без лазанья в лог
        // if($this->messages[$this->getCode()]) $this->message = $this->messages[$this->getCode()];

        $this->writeLog($error, 'db_log.txt');
    }
}
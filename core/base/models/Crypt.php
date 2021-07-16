<?php


namespace core\base\models;


use core\base\controllers\Singleton;

class Crypt
{
    use Singleton;

    private string $cryptMethod = 'AES-128-gcm';
    private string $hashAlgoritm = 'sha256';
    private int $hashLength = 32;

    public function encrypt($str) {

    }

}
<?php
namespace Ant\Crypt;


interface CipherInterface
{
    public function encrypt($data, $key = null);

    public function decrypt($data, $Key = null);
}
<?php
namespace Ant\Network\Socks;


class Parser
{
    public function parseSocksHeader($data)
    {
        $len = ord($data{1});

        $methods = ord(substr($data, 2, $len));

        return [ord($data{0}), $methods];
    }

    public function parseUsernameAndPassword($data)
    {
        $length = 1;
        $ulen = ord($data{1});

        $username = substr($data, ++$length, $ulen);

        $length += $ulen;
        $plen = ord(substr($data, $length, 1));

        $password = substr($data, ++$length, $plen);

        return [$username, $password];
    }
}
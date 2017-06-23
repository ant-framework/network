<?php
use React\HttpClient\Factory;
use React\HttpClient\Response;

require 'vendor/autoload.php';

$client = stream_socket_client("127.0.0.1:8080");

$request = new \Ant\Http\Request('GET', "http://127.0.0.1");

fwrite($client, (string) $request);

echo fread($client, 8192);
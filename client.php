<?php

require_once __DIR__ . '/vendor/autoload.php';

$postman = new \Postman\Drivers\AMQP('rabbit');

$result = $postman->request('echo', '3');
echo "$result\n";
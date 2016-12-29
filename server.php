<?php

require_once __DIR__ . '/vendor/autoload.php';

$postman = new \Postman\Drivers\AMQP('rabbit');

$postman->subscribe('echo', function ($msq) {

    return $msq;
});
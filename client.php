<?php


use Ueef\Postbox\Postman;
use Ueef\Postbox\Encoders\JSON;
use Ueef\Postbox\Drivers\AMQP;
use Ueef\Postbox\Envelope;

require_once __DIR__ . '/vendor/autoload.php';

$postman = new Postman([
    'driver' => new AMQP(),
    'envelope' => new Envelope(['encoder' => new JSON()])
]);

$result = $postman->request(['test', 'echo'], ['key' => 'value']);
var_dump($result);
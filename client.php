<?php

use Postman\Drivers\AMQP;
use Postman\Gate;

require_once __DIR__ . '/vendor/autoload.php';

$gate = new Gate(new AMQP('rabbit'));

$result = $gate->request('test', 'eeecho', ['param' => 'text']);
var_dump($result);
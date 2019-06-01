<?php

use Ueef\Postbox\Drivers\AMQP;
use Ueef\Postbox\Envelope;
use Ueef\Postbox\Handlers\AbstractHandler;
use Ueef\Postbox\Postbox;
use Ueef\Postbox\Encoders\JSON;

require_once __DIR__ . '/vendor/autoload.php';

class Worker extends AbstractHandler
{
    public function task($args)
    {
        $time = $args['time'];
        sleep($time);
        return ['result' => "I am work $time sec"];
    }
}

$postbox = new Postbox([
    'driver' => new AMQP(),
    'envelope' => new Envelope(['encoder' => new JSON()])
]);
$postbox->wait('worker', new Worker());
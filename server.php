<?php

use Ueef\Postbox\Drivers\AMQP;
use Ueef\Postbox\Envelope;
use Ueef\Postbox\Handlers\AbstractHandler;
use Ueef\Postbox\Postbox;
use Ueef\Postbox\Encoders\JSON;

require_once __DIR__ . '/vendor/autoload.php';

class Test extends AbstractHandler
{
    public function eeecho($args)
    {
        return $args;
    }
}

$postbox = new Postbox([
    'driver' => new AMQP(['host' => 'rabbit']),
    'envelope' => new Envelope(['encoder' => new JSON()])
]);
$postbox->wait('test', new Test());
<?php

use Postman\Drivers\AMQP;
use Postman\Gate;

require_once __DIR__ . '/vendor/autoload.php';

class Test implements Postman\Interfaces\ServiceInterface
{
    private $name = 'test';

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function eeecho($args)
    {
        return $args;
    }
}

$gate = new Gate(new AMQP('rabbit'), new Test());
$gate->wait();
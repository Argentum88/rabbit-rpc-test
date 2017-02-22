<?php

require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('rabbit', 5672, 'guest', 'guest');
$channel = $connection->channel();
$channel->queue_declare('queue1', false, false, false, false);
$channel->queue_declare('queue2', false, false, false, false);

class One
{
    public $property = 1;

    public $channel;

    public function __construct(PhpAmqpLib\Channel\AMQPChannel $channel)
    {
        $this->channel = $channel;
        $this->channel->basic_consume('queue1', '', false, false, false, false, [$this, 'start']);
        //$this->channel->basic_consume('queue1', '', false, false, false, false, [$this, 'start2']);
    }

    public function start(AMQPMessage $msg)
    {
        echo " [x] Start work from queue1", "\n";
        $info = $msg->getBody();
        echo $info, "\n";
        echo "starter 1", "\n";
        echo " [x] Done work from queue1", "\n\n\n";

        $this->n($this->property);
        $this->property++;
    }

    public function start2(AMQPMessage $msg)
    {
        echo " [x] Start work from queue1", "\n";
        $info = $msg->getBody();
        echo $info, "\n";
        echo "starter 2", "\n";
        echo " [x] Done work from queue1", "\n\n\n";

        $this->n($this->property);
        $this->property++;
    }

    public function n($foo)
    {
        $handler = function (AMQPMessage $msg) use ($foo) {
            echo " [x] Start work from queue2", "\n";
            $info = $msg->getBody();
            echo $info, "\n";
            echo 'Контекст ', $foo, "\n";
            echo " [x] Done work from queue2", "\n\n\n";
        };

        $this->channel->basic_consume('queue2', '', false, true, false, false, $handler);

    }
}

$obj = new One($channel);

while (count($channel->callbacks)) {
    $channel->wait();
}

$channel->close();
$connection->close();
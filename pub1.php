<?php

require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('rabbit', 5672, 'guest', 'guest');
$channel = $connection->channel();

$msg = new AMQPMessage("message from pub1");
$channel->basic_publish($msg, '', 'queue1');

echo " [x] Sent ", "\n";

$channel->close();
$connection->close();
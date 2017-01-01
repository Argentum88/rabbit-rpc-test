<?php

require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('rabbit', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->queue_declare('rpc_queue', false, false, false, false);

echo " [x] Awaiting RPC requests\n";

$callback = function(AMQPMessage $req) {
	$n = intval($req->body);
	echo " [.] sleep(", $n, ")\n";

    sleep($n);
	$msg = new AMQPMessage("sleep $n second", ['correlation_id' => $req->get('correlation_id')]);

	$req->delivery_info['channel']->basic_publish($msg, '', $req->get('reply_to'));
	$req->delivery_info['channel']->basic_ack($req->delivery_info['delivery_tag']);
};

$channel->basic_qos(null, 1, null);
$channel->basic_consume('rpc_queue', '', false, false, false, false, $callback);

while(count($channel->callbacks)) {
    $channel->wait();
}

$channel->close();
$connection->close();

?>

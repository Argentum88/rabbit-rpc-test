<?php

require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class SleepRpcClient {
	private $connection;
	private $channel;
	private $callback_queue;
	private $response;
	private $corr_id;

	public function __construct()
	{
		$this->connection = new AMQPStreamConnection('rabbit', 5672, 'guest', 'guest');
		$this->channel = $this->connection->channel();

		list($this->callback_queue, ,) = $this->channel->queue_declare("", false, false, true, false);
		$this->channel->basic_consume($this->callback_queue, '', false, false, false, false, [$this, 'on_response']);
	}

	public function on_response(AMQPMessage $rep)
	{
		if($rep->get('correlation_id') == $this->corr_id) {
			$this->response = $rep->body;
		}
	}

	public function call($n)
	{
		$this->response = null;
		$this->corr_id = uniqid();

		$msg = new AMQPMessage(
			(string) $n,
			[
				'correlation_id' => $this->corr_id,
				'reply_to' => $this->callback_queue
			]
		);

		$this->channel->basic_publish($msg, '', 'rpc_queue');

		while(!$this->response) {

			$this->channel->wait();
		}

		return $this->response;
	}
};

$callback = function(AMQPMessage $req) {
    $n = intval($req->body);
    echo " [.] forwarding and doubles sleep($n)\n";

    $sleep_rpc = new SleepRpcClient();
    $response = $sleep_rpc->call($n*2);

    $msg = new AMQPMessage($response, ['correlation_id' => $req->get('correlation_id')]);

    $req->delivery_info['channel']->basic_publish(
        $msg, '', $req->get('reply_to'));
    $req->delivery_info['channel']->basic_ack(
        $req->delivery_info['delivery_tag']);
};

$conn = new AMQPStreamConnection('rabbit', 5672, 'guest', 'guest');
$channel = $conn->channel();
$channel->queue_declare('input_queue', false, false, false, false);
$channel->basic_qos(null, 1, null);
$channel->basic_consume('input_queue', '', false, false, false, false, $callback);

echo " [x] Awaiting RPC requests\n";

while(count($channel->callbacks)) {
    $channel->wait();
}

$channel->close();
$connection->close();

/*$sleep_rpc = new SleepRpcClient();
$response = $sleep_rpc->call(1);
echo " [.] Got ", $response, "\n";*/

?>

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

		$this->channel->basic_publish($msg, '', 'input_queue');

		while(!$this->response) {

			$this->channel->wait();
		}

		return $this->response;
	}
};

$sleep_rpc = new SleepRpcClient();
$response = $sleep_rpc->call(2);
echo " [.] Got ", $response, "\n";

?>

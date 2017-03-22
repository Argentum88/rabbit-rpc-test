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

	private $canSoftTerminate = true;

	public function __construct($connection, $channel)
	{
		$this->connection = $connection;
		$this->channel = $channel;

		list($this->callback_queue, ,) = $this->channel->queue_declare("", false, false, true, false);
		$this->channel->basic_consume($this->callback_queue, '', false, false, false, false, [$this, 'on_response']);

        pcntl_signal(SIGINT, function () {

        	var_dump($this->canSoftTerminate);
        	echo "\nstart signal handler\n";
        	if ($this->canSoftTerminate) {
        		exit;
			}

            posix_kill(posix_getpid(),SIGINT);
        });
	}

	public function on_response(AMQPMessage $rep)
	{
		if($rep->get('correlation_id') == $this->corr_id) {
			$this->response = $rep->body;
		}
	}

	public function call($n)
	{
		$this->canSoftTerminate = false;

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

			/* callback для очередных сообщенрий из input_queue тут не сработает
			т.к. qos = 1 и ещё не отправлен acknowledgement для предыдущего сообщения */
			$this->channel->wait();
		}

        $this->canSoftTerminate = true;
		return $this->response;
	}
};

$conn = new AMQPStreamConnection('rabbit', 5672, 'guest', 'guest');
$channel = $conn->channel();

$sleepRpcClient = new SleepRpcClient($conn, $channel);

$callback = function(AMQPMessage $req) use ($sleepRpcClient) {
    $n = intval($req->body);
    echo " [.] forwarding and triples sleep($n)\n";

    $response = $sleepRpcClient->call($n*3);

    $msg = new AMQPMessage($response, ['correlation_id' => $req->get('correlation_id')]);

    $req->delivery_info['channel']->basic_publish($msg, '', $req->get('reply_to'));
    $req->delivery_info['channel']->basic_ack($req->delivery_info['delivery_tag']);
};

$channel->queue_declare('input_queue', false, false, false, false);
$channel->basic_qos(null, 1, null);
$channel->basic_consume('input_queue', '', false, false, false, false, $callback);

echo " [x] Awaiting RPC requests\n";

while(count($channel->callbacks)) {
    $channel->wait();
}

$channel->close();
$connection->close();

?>

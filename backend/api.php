<?php

require_once __DIR__.'/vendor/autoload.php';

class Server
{
    private $logger;
    private $rabbitMQ;

    public function __construct(\Logger\Logger $logger, \RabbitMQ\RabbitMQWrapper $rabbitMQ)
    {
        $this->logger = $logger;
        $this->rabbitMQ = $rabbitMQ;
    }

    public function serve()
    {
        header("Access-Control-Allow-Origin: *");
        // Basic server, call function based on "method"
        $request = json_decode(file_get_contents('php://input'), true);
        try {
            $response = $this->{$request['method']}($request);
            $this->logger->log($request, $response['result']);
        } catch (\Exception $e) {
            $this->logger->log($request, $e->getMessage());
            http_response_code(500);
            $response = ['id' => $request['id'], 'error' => ['code' => $e->getCode(), 'message' => $e->getMessage()]];
        }
        echo json_encode($response);
    }

    protected function createDocument($request)
    {
        $rabbitmq = new \RabbitMQ\RabbitMQWrapper();
        $rabbitmq->publish('amq.direct', new \Swarrot\Broker\Message(json_encode($request)));

        $response = ['id' => $request['id'], 'result' => 'pending'];

        return $response;
    }
}

$rabbitMQ = new \RabbitMQ\RabbitMQWrapper();
$logger = new \Logger\Logger('api', $rabbitMQ);
(new Server($logger, $rabbitMQ))->serve();

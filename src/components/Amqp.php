<?php

declare(strict_types=1);

namespace tolmachov\amqp\components;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPSocketConnection;
use PhpAmqpLib\Message\AMQPMessage;
use yii\base\Component;
use yii\base\Exception;
use yii\helpers\Json;


/**
 * AMQP wrapper.
 *
 * @property AMQPSocketConnection $connection AMQP connection.
 * @property AMQPChannel $channel AMQP channel.
 */
class Amqp extends Component
{
    const TYPE_TOPIC = 'topic';
    const TYPE_DIRECT = 'direct';
    const TYPE_HEADERS = 'headers';
    const TYPE_FANOUT = 'fanout';

    /**
     * @var AMQPSocketConnection
     */
    protected static $ampqConnection;

    /**
     * @var AMQPChannel[]
     */
    protected $channels = [];

    /**
     * @var string
     */
    public $host = '127.0.0.1';

    /**
     * @var integer
     */
    public $port = 5672;

    /**
     * @var string
     */
    public $user;

    /**
     * @var string
     */
    public $password;

    /**
     * @var string
     */
    public $vhost = '/';

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();
        if ($this->user === null || $this->user === '') {
            throw new Exception("Parameter 'user' was not set for AMQP connection.");
        }
        if (self::$ampqConnection === null) {
            self::$ampqConnection = new AMQPSocketConnection(
                $this->host,
                $this->port,
                $this->user,
                $this->password,
                $this->vhost
            );
        }
    }

    /**
     * Returns AMQP connection.
     *
     * @return AMQPSocketConnection
     */
    public function getConnection(): AMQPSocketConnection
    {
        return self::$ampqConnection;
    }

    /**
     * Returns AMQP channel.
     *
     * @param string|null $channel_id
     *
     * @return AMQPChannel
     */
    public function getChannel(?string $channel_id = null): AMQPChannel
    {
        $index = $channel_id ?? 'default';
        if (!array_key_exists($index, $this->channels)) {
            $this->channels[$index] = $this->connection->channel($channel_id);
        }
        return $this->channels[$index];
    }

    /**
     * Sends message to the exchange.
     *
     * @param string $exchange
     * @param string $routing_key
     * @param string|array|object $message
     * @param string $type Use self::TYPE_DIRECT if it is an answer
     */
    public function send(string $exchange, string $routing_key, $message, string $type = self::TYPE_TOPIC): void
    {
        $message = $this->prepareMessage($message);
        if ($type === self::TYPE_TOPIC) {
            $this->channel->exchange_declare($exchange, $type, false, true, false);
        }
        $this->channel->basic_publish($message, $exchange, $routing_key);
    }

    /**
     * Sends message to the exchange and waits for answer.
     *
     * @param string $exchange
     * @param string $routing_key
     * @param string|array|object $message
     * @param int $timeout Timeout in seconds.
     *
     * @return string|null
     */
    public function ask(string $exchange, string $routing_key, $message, int $timeout): ?string
    {
        [$queueName] = $this->channel->queue_declare('', false, false, true, false);
        $message = $this->prepareMessage($message, [
            'reply_to' => $queueName,
        ]);
        // queue name must be used for answer's routing key
        $this->channel->queue_bind($queueName, $exchange, $queueName);

        $response = null;
        $callback = function (AMQPMessage $answer) use ($message, &$response) {
            $response = $answer->body;
        };

        $this->channel->basic_consume($queueName, '', false, false, false, false, $callback);
        $this->channel->basic_publish($message, $exchange, $routing_key);
        while (!$response) {
            // exception will be thrown on timeout
            $this->channel->wait(null, false, $timeout);
        }

        return $response;
    }

    /**
     * Listens the exchange for messages.
     *
     * @param string $exchange
     * @param string $routing_key
     * @param callable $callback
     * @param string $type
     */
    public function listen(string $exchange, string $routing_key, callable $callback, string $type = self::TYPE_TOPIC): void
    {
        [$queueName] = $this->channel->queue_declare();
        if ($type === self::TYPE_DIRECT) {
            $this->channel->exchange_declare($exchange, $type, false, true, false);
        }
        $this->channel->queue_bind($queueName, $exchange, $routing_key);
        $this->channel->basic_consume($queueName, '', false, true, false, false, $callback);

        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }

        $this->channel->close();
        $this->connection->close();
    }

    /**
     * Returns prepared AMQP message.
     *
     * @param string|array|object $message
     * @param array|null $properties
     *
     * @return AMQPMessage
     *
     * @throws Exception If message is empty.
     */
    public function prepareMessage($message, ?array $properties = null): AMQPMessage
    {
        if ($message === '' || $message === [] || $message === null) {
            throw new Exception('AMQP message can not be empty');
        }
        if (is_array($message) || is_object($message)) {
            $message = Json::encode($message);
        }

        return new AMQPMessage($message, $properties ?? []);
    }
}

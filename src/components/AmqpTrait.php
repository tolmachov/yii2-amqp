<?php

declare(strict_types=1);

namespace tolmachov\amqp\components;

use Yii;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * AMQP trait for controllers.
 *
 * @property Amqp $amqp AMQP object.
 * @property AbstractConnection $connection AMQP connection.
 * @property AMQPChannel $channel AMQP channel.
 */
trait AmqpTrait
{
    /**
     * @var Amqp
     */
    protected $amqpContainer;

    /**
     * Listened exchange.
     *
     * @var string
     */
    public $exchange = 'exchange';

    /**
     * Returns AMQP object.
     *
     * @return Amqp
     */
    public function getAmqp(): Amqp
    {
        if ($this->amqpContainer === null) {
            $this->amqpContainer = Yii::$app->amqp;
        }

        return $this->amqpContainer;
    }

    /**
     * Returns AMQP connection.
     *
     * @return AbstractConnection
     */
    public function getConnection(): AbstractConnection
    {
        return $this->amqp->getConnection();
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
        return $this->amqp->getChannel($channel_id);
    }

    /**
     * Sends message to the exchange.
     *
     * @param string $routing_key
     * @param string|array|object $message
     * @param string|null $exchange
     * @param string $type
     */
    public function send(string $routing_key, $message, ?string $exchange = null, string $type = Amqp::TYPE_TOPIC): void
    {
        $this->amqp->send($exchange ?? $this->exchange, $routing_key, $message, $type);
    }

    /**
     * Sends message to the exchange and waits for answer.
     *
     * @param string $routing_key
     * @param string|array|object $message
     * @param int $timeout Timeout in seconds.
     * @param string|null $exchange
     *
     * @return string|null
     */
    public function ask(string $routing_key, $message, int $timeout = 10, ?string $exchange = null): ?string
    {
        return $this->amqp->ask($exchange ?? $this->exchange, $routing_key, $message, $timeout);
    }
}

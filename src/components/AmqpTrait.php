<?php

namespace tolmachov\amqp\components;

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
    public function getAmqp()
    {
        if (empty($this->amqpContainer)) {
            $this->amqpContainer = Yii::$app->amqp;
        }

        return $this->amqpContainer;
    }

    /**
     * Returns AMQP connection.
     *
     * @return AbstractConnection
     */
    public function getConnection()
    {
        return $this->amqp->getConnection();
    }

    /**
     * Returns AMQP channel.
     *
     * @param string $channel_id
     *
     * @return AMQPChannel
     */
    public function getChannel($channel_id = null)
    {
        return $this->amqp->getChannel($channel_id);
    }

    /**
     * Sends message to the exchange.
     *
     * @param string $routing_key
     * @param string|array|AMQPMessage $message
     * @param string $exchange
     * @param string $type
     *
     * @return void
     */
    public function send($routing_key, $message, $exchange = null, $type = Amqp::TYPE_TOPIC)
    {
        $this->amqp->send($exchange ?: $this->exchange, $routing_key, $message, $type);
    }

    /**
     * Sends message to the exchange and waits for answer.
     *
     * @param string $routing_key
     * @param string|array|AMQPMessage $message
     * @param integer $timeout Timeout in seconds.
     * @param string $exchange
     *
     * @return string
     */
    public function ask($routing_key, $message, $timeout = 10, $exchange = null)
    {
        return $this->amqp->ask($exchange ?: $this->exchange, $routing_key, $message, $timeout);
    }
}

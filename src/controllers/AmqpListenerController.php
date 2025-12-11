<?php

declare(strict_types=1);

namespace tolmachov\amqp\controllers;

use PhpAmqpLib\Message\AMQPMessage;
use tolmachov\amqp\components\Amqp;
use tolmachov\amqp\components\AmqpInterpreter;
use yii\console\Exception;
use yii\helpers\Inflector;
use yii\helpers\Json;

/**
 * AMQP listener controller.
 */
class AmqpListenerController extends AmqpConsoleAbstractController
{
    /**
     * Interpreter classes for AMQP messages. This class will be used if interpreter class not set for exchange.
     *
     * @var array
     */
    public $interpreters = [];

    /**
     * @inheritdoc
     */
    public function actionRun(string $routingKey = '#', string $type = Amqp::TYPE_TOPIC): int
    {
        $this->amqp->listen($this->exchange, $routingKey, [$this, 'callback'], $type);
        return 0;
    }

    public function callback(AMQPMessage $msg): void
    {
        $routingKey = $msg->delivery_info['routing_key'];
        $method = 'read' . Inflector::camelize($routingKey);

        if (!isset($this->interpreters[$this->exchange])) {
            $interpreter = $this;
        } elseif (class_exists($this->interpreters[$this->exchange])) {
            $interpreter = new $this->interpreters[$this->exchange];
            if (!$interpreter instanceof AmqpInterpreter) {
                throw new Exception(sprintf("Class '%s' is not correct interpreter class.", $this->interpreters[$this->exchange]));
            }
        } else {
            throw new Exception(sprintf("Interpreter class '%s' was not found.", $this->interpreters[$this->exchange]));
        }

        if (method_exists($interpreter, $method)) {
            $info = [
                'exchange' => $msg->get('exchange'),
                'routing_key' => $msg->get('routing_key'),
                'reply_to' => $msg->has('reply_to') ? $msg->get('reply_to') : null,
            ];
            $interpreter->$method(Json::decode($msg->body, true), $info);
        } else {
            if (!isset($this->interpreters[$this->exchange])) {
                $interpreter = new AmqpInterpreter();
            }
            $interpreter->log(
                sprintf("Unknown routing key '%s' for exchange '%s'.", $routingKey, $this->exchange),
                $interpreter::MESSAGE_ERROR
            );
            // debug the message
            $interpreter->log(
                print_r(Json::decode($msg->body, true), true),
                $interpreter::MESSAGE_INFO
            );
        }
    }
}

<?php

namespace tolmachov\amqp\controllers;

use tolmachov\amqp\components\AmqpTrait;
use tolmachov\amqp\commands\Controller;

/**
 * AMQP console controller.
 */
abstract class AmqpConsoleController extends Controller
{
    use AmqpTrait;

    /**
     * @inheritdoc
     */
    public function options($actionId)
    {
        return array_merge(
            parent::options($actionId),
            ['exchange']
        );
    }
}

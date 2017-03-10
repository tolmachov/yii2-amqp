<?php

namespace tolmachov\amqp\controllers;

use tolmachov\amqp\components\AmqpTrait;

/**
 * AMQP console controller.
 */
abstract class AmqpConsoleAbstractController extends AbstractController
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

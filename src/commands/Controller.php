<?php

namespace tolmachov\amqp\commands;

/**
 * Console controller wrapper
 */
abstract class Controller extends \yii\console\Controller
{
    /**
     * @inheritdoc
     */
    public $defaultAction = 'run';

    /**
     * Whether every confirm can be skipped with answer 'yes'.
     *
     * @var bool
     */
    public $y = false;

    abstract public function actionRun();

    /**
     * @inheritdoc
     */
    public function options($actionId)
    {
        return array_merge(
            parent::options($actionId),
            ['y']
        );
    }

    /**
     * @inheritdoc
     */
    public function confirm($message, $default = false)
    {
        if ($this->y) {
            return true;
        }

        return parent::confirm($message, $default);
    }
}

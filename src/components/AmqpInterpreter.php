<?php

declare(strict_types=1);

namespace tolmachov\amqp\components;

use yii\helpers\Console;

/**
 * AMQP interpreter class.
 */
class AmqpInterpreter
{
    const MESSAGE_INFO = 0;
    const MESSAGE_ERROR = 1;

    /**
     * Logs info and error messages.
     *
     * @param string $message
     * @param int $type
     */
    public function log(string $message, int $type = self::MESSAGE_INFO): void
    {
        $format = [$type === self::MESSAGE_ERROR ? Console::FG_RED : Console::FG_BLUE];
        Console::stdout(Console::ansiFormat($message . PHP_EOL, $format));
    }
}
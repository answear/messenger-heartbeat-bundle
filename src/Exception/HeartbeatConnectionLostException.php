<?php

declare(strict_types=1);

namespace Answear\MessengerHeartbeatBundle\Exception;

use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

class HeartbeatConnectionLostException extends UnrecoverableMessageHandlingException
{
    public const MESSAGE = 'RabbitMQ transport exception. Cannot send heartbeat, connection lost by server';

    public static function createException(\Throwable $previous): self
    {
        return new self(self::MESSAGE, 0, $previous);
    }
}

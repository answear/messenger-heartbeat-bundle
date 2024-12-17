<?php

declare(strict_types=1);

namespace Answear\MessengerHeartbeatBundle\Exception;

use Symfony\Component\Messenger\Exception\StopWorkerException;

class KeepaliveException extends StopWorkerException
{
    private const MESSAGE = 'RabbitMQ transport exception. Cannot send heartbeat.';

    public static function createException(\Throwable $previous): self
    {
        return new self(self::MESSAGE, $previous);
    }
}

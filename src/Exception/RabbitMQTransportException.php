<?php

declare(strict_types=1);

namespace Answear\MessengerHeartbeatBundle\Exception;

use Symfony\Component\Messenger\Exception\StopWorkerExceptionInterface;
use Symfony\Component\Messenger\Exception\UnrecoverableExceptionInterface;

class RabbitMQTransportException extends \RuntimeException implements UnrecoverableExceptionInterface, StopWorkerExceptionInterface
{
    public const MESSAGE = 'RabbitMQ transport exception. Cannot send heartbeat.';

    public static function createException(\Throwable $previous): self
    {
        return new self(self::MESSAGE, $previous->getCode(), $previous);
    }
}

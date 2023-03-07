<?php

declare(strict_types=1);

namespace Answear\MessengerHeartbeatBundle\Middleware;

use Answear\MessengerHeartbeatBundle\Exception\HeartbeatConnectionLostException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

class TransportExceptionsMiddleware implements MiddlewareInterface
{
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        try {
            return $stack->next()->handle($envelope, $stack);
        } catch (\Throwable $throwable) {
            if ($this->isRabbitMQException($throwable)) {
                throw new HeartbeatConnectionLostException($throwable->getMessage(), 0, $throwable);
            }

            throw $throwable;
        }
    }

    private function isRabbitMQException(\Throwable $throwable): bool
    {
        return \str_contains($throwable->getMessage(), HeartbeatConnectionLostException::MESSAGE);
    }
}

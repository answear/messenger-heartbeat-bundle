<?php

declare(strict_types=1);

namespace Answear\MessengerHeartbeatBundle\Middleware;

use Answear\MessengerHeartbeatBundle\Exception\HeartbeatConnectionLostException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

class TransportExceptionsMiddleware implements MiddlewareInterface
{
    public function __construct(private ?LoggerInterface $logger = null)
    {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        try {
            return $stack->next()->handle($envelope, $stack);
        } catch (\Throwable $throwable) {
            if ($this->isRabbitMQException($throwable)) {
                $this->logger?->warning(
                    'HeartbeatConnectionLostException has been thrown.',
                    ['exception' => $throwable, 'envelopeClass' => get_class($envelope)]
                );

                throw new HeartbeatConnectionLostException($throwable->getMessage(), 0, $throwable);
            }

            $transportException = $this->getTransportException($throwable);
            if (null !== $transportException) {
                $this->logger?->warning(
                    'TransportException has been thrown.',
                    ['exception' => $throwable, 'envelopeClass' => get_class($envelope)]
                );

                throw $throwable;
            }

            throw $throwable;
        }
    }

    private function getTransportException(?\Throwable $throwable): ?TransportException
    {
        if (null === $throwable || $throwable instanceof TransportException) {
            return $throwable;
        }

        return $this->getTransportException($throwable->getPrevious());
    }

    private function isRabbitMQException(\Throwable $throwable): bool
    {
        return \str_contains($throwable->getMessage(), HeartbeatConnectionLostException::MESSAGE);
    }
}

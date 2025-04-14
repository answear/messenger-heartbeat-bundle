<?php

declare(strict_types=1);

namespace Answear\MessengerHeartbeatBundle\Service;

use Answear\MessengerHeartbeatBundle\Exception\RabbitMQTransportException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpTransport;
use Symfony\Component\Messenger\Exception\TransportException;

class SignalHandler
{
    private int $errorsCount = 0;

    public function __construct(private ?LoggerInterface $logger = null)
    {
    }

    /**
     * @var AmqpTransport[]
     */
    private array $transports = [];

    public function addTransport(AmqpTransport $transport): void
    {
        $this->transports[$transport::class] = $transport;
    }

    public function keepaliveTransports(): void
    {
        foreach ($this->transports as $transport) {
            $this->keepaliveTransport($transport);
        }
    }

    private function keepaliveTransport(AmqpTransport $transport): void
    {
        try {
            $this->updateLastActivityTime($transport);

            $transport->getMessageCount();
        } catch (\Throwable $throwable) {
            ++$this->errorsCount;
            $this->logger?->warning(
                '[Keepalive] Exception has been thrown during keepalive.',
                ['exception' => $throwable, 'errorCount' => $this->errorsCount]
            );

            $transportException = $this->getTransportException($throwable);
            if (null !== $transportException) {
                /*
                 * TransportException like AMQPQueueException("Server channel error: 406, message: PRECONDITION_FAILED - delivery acknowledgement on channel 1 timed out."),
                 * connection lost,
                 * message will probably be redelivered,
                 * don't requeue current message to prevent message duplication
                 */
                throw RabbitMQTransportException::createException($transportException);
            }
        }
    }

    private function getTransportException(?\Throwable $throwable): ?TransportException
    {
        if (null === $throwable || $throwable instanceof TransportException) {
            return $throwable;
        }

        return $this->getTransportException($throwable->getPrevious());
    }

    private function updateLastActivityTime(AmqpTransport $transport): void
    {
        $transportReflection = new \ReflectionClass($transport);
        $connection = $transportReflection->getProperty('connection')->getValue($transport);

        $reflection = new \ReflectionClass($connection);
        $property = $reflection->getProperty('lastActivityTime');
        $property->setValue($connection, time());
    }
}

<?php

declare(strict_types=1);

namespace Answear\MessengerHeartbeatBundle\Transport;

use Answear\MessengerHeartbeatBundle\Exception\KeepaliveException;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpTransport as BaseAmqpTransport;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\Connection;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\Transport\Receiver\KeepaliveReceiverInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class AmqpTransport extends BaseAmqpTransport implements KeepaliveReceiverInterface
{
    public function __construct(
        private Connection $connection,
        SerializerInterface $serializer,
    ) {
        parent::__construct($this->connection, $serializer);
    }

    public function keepalive(Envelope $envelope, ?int $seconds = null): void
    {
        try {
            $reflection = new \ReflectionClass($this->connection);
            $property = $reflection->getProperty('lastActivityTime');
            $property->setValue($this->connection, time());

            $this->getMessageCount();
        } catch (\Throwable $throwable) {
            $transportException = $this->getTransportException($throwable);
            if (null !== $transportException) {
                /*
                 * TransportException like AMQPQueueException("Server channel error: 406, message: PRECONDITION_FAILED - delivery acknowledgement on channel 1 timed out."),
                 * connection lost,
                 * message will be set as redelivered,
                 * need to stop worker and don't requeue current message to prevent message duplication
                 */
                throw new UnrecoverableMessageHandlingException($transportException->getMessage(), $transportException->getCode(), $transportException);
            }

            throw KeepaliveException::createException($throwable);
        }
    }

    public function getKeepaliveInterval(): int
    {
        return $this->getQueue()->getConnection()->getHeartbeatInterval();
    }

    private function getQueue(): \AMQPQueue
    {
        try {
            $queueNames = $this->connection->getQueueNames();

            return $this->connection->queue(reset($queueNames));
        } catch (\Throwable $throwable) {
            throw new \AMQPConnectionException('Connection without queue.', $throwable->getCode(), $throwable);
        }
    }

    private function getTransportException(?\Throwable $throwable): ?TransportException
    {
        if (null === $throwable || $throwable instanceof TransportException) {
            return $throwable;
        }

        return $this->getTransportException($throwable->getPrevious());
    }
}

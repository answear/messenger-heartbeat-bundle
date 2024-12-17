<?php

declare(strict_types=1);

namespace Answear\MessengerHeartbeatBundle\Transport;

use Answear\MessengerHeartbeatBundle\Exception\HeartbeatConnectionLostException;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpTransport as BaseAmqpTransport;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\Connection;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\TransportException;
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
            if ($throwable instanceof TransportException || $throwable->getPrevious() instanceof TransportException) {
                throw HeartbeatConnectionLostException::createException($throwable);
            }
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
}

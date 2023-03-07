<?php

declare(strict_types=1);

namespace Answear\MessengerHeartbeatBundle\Transport;

use Answear\MessengerHeartbeatBundle\Exception\HeartbeatConnectionLostException;
use Answear\MessengerHeartbeatBundle\Heartbeat\PCNTLHeartbeatSender;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpTransport as BaseAmqpTransport;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\Connection;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class AmqpTransport extends BaseAmqpTransport implements HeartbeatConnectionInterface
{
    public function __construct(
        private Connection $connection,
        private PCNTLHeartbeatSender $heartbeatSender,
        SerializerInterface $serializer
    ) {
        parent::__construct($this->connection, $serializer);
    }

    public function isConnected(): bool
    {
        try {
            return $this->getQueue()->getConnection()->isConnected();
        } catch (\AMQPConnectionException) {
            return false;
        }
    }

    public function getHeartbeat(): int
    {
        return $this->getQueue()->getConnection()->getHeartbeatInterval();
    }

    public function sendHeartbeat(): void
    {
        try {
            $this->getMessageCount();
        } catch (\Throwable $throwable) {
            if ($throwable instanceof TransportException || $throwable->getPrevious() instanceof TransportException) {
                throw HeartbeatConnectionLostException::createException($throwable);
            }
        }
    }

    public function registerHeartbeatSender(): void
    {
        $this->heartbeatSender->createForConnection($this)->register();
    }

    private function getQueue(): \AMQPQueue
    {
        foreach ($this->connection->getQueueNames() as $queueName) {
            return $this->connection->queue($queueName);
        }

        throw new \AMQPConnectionException('Connection without queue');
    }
}

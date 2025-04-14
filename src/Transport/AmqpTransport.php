<?php

declare(strict_types=1);

namespace Answear\MessengerHeartbeatBundle\Transport;

use Answear\MessengerHeartbeatBundle\Exception\RabbitMQTransportException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpTransport as BaseAmqpTransport;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\Connection;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class AmqpTransport extends BaseAmqpTransport implements AmqpHeartbeatTransportInterface
{
    private $errorsCount = 0;

    public function __construct(
        private Connection $connection,
        SerializerInterface $serializer,
        private ?LoggerInterface $logger = null,
    ) {
        parent::__construct($this->connection, $serializer);
    }

    public function get(): iterable
    {
        try {
            return parent::get();
        } catch (TransportException $transportException) {
            $this->logger?->warning(
                '[Keepalive] TransportException has been thrown.',
                ['exception' => $transportException]
            );

            throw RabbitMQTransportException::createException($transportException);
        }
    }

    public function getFromQueues(array $queueNames): iterable
    {
        try {
            return parent::getFromQueues($queueNames);
        } catch (TransportException $transportException) {
            $this->logger?->warning(
                '[Keepalive] TransportException has been thrown.',
                ['exception' => $transportException]
            );

            throw RabbitMQTransportException::createException($transportException);
        }
    }

    public function keepalive(): void
    {
        $this->logger?->info(
            '[Keepalive] Sending keepalive request.',
        );

        try {
            $this->updateLastActivityTime();

            $this->getMessageCount();
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

    private function updateLastActivityTime(): void
    {
        $reflection = new \ReflectionClass($this->connection);
        $property = $reflection->getProperty('lastActivityTime');
        $property->setValue($this->connection, time());
    }
}

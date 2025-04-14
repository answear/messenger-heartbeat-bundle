<?php

declare(strict_types=1);

namespace Answear\MessengerHeartbeatBundle\Transport;

use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpTransportFactory;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\Connection;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

class TransportFactory implements TransportFactoryInterface
{
    public function __construct(private ?LoggerInterface $logger = null)
    {
    }

    public function createTransport(#[\SensitiveParameter] string $dsn, array $options, SerializerInterface $serializer): TransportInterface
    {
        unset($options['transport_name']);

        return new AmqpTransport(Connection::fromDsn($dsn, $options), $serializer, $this->logger);
    }

    public function supports(string $dsn, array $options): bool
    {
        return (new AmqpTransportFactory())->supports($dsn, $options);
    }
}

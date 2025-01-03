<?php

declare(strict_types=1);

namespace Answear\MessengerHeartbeatBundle\Tests\Unit\Transport;

use Answear\MessengerHeartbeatBundle\Transport\AmqpTransport;
use Answear\MessengerHeartbeatBundle\Transport\TransportFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\Connection;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class TransportFactoryTest extends TestCase
{
    #[Test]
    #[DataProvider('provideSupportedScheme')]
    public function supportsValidScheme(string $dsn): void
    {
        $factory = new TransportFactory();

        $this->assertTrue($factory->supports($dsn, []));
    }

    #[Test]
    #[DataProvider('provideTransportData')]
    public function validTransport(string $dsn, array $options, array $expectedOptions): void
    {
        $factory = new TransportFactory();
        $transport = $factory->createTransport($dsn, $options, $this->createMock(SerializerInterface::class));
        $connection = $this->getInnerProperty($transport, 'connection');

        $this->assertInstanceOf(AmqpTransport::class, $transport);
        $this->assertInstanceOf(Connection::class, $connection);
        $this->assertSame($expectedOptions, $this->getInnerProperty($connection, 'connectionOptions'));
    }

    public static function provideSupportedScheme(): iterable
    {
        yield 'amqp' => ['amqp://host'];
        yield 'amqps' => ['amqps://host'];
    }

    public static function provideTransportData(): iterable
    {
        yield 'defaultWithReadTimeout' => [
            'amqp://rabbit-is-dead.elmer?read_timeout=1',
            [],
            [
                'delay' => [
                    'exchange_name' => 'delays',
                    'queue_name_pattern' => 'delay_%exchange_name%_%routing_key%_%delay%',
                ],
                'host' => 'rabbit-is-dead.elmer',
                'port' => 5672,
                'vhost' => '/',
                'read_timeout' => '1',
            ],
        ];

        yield 'defaultWithWriteTimeout' => [
            'amqp://rabbit-is-dead.elmer?write_timeout=1',
            [],
            [
                'delay' => [
                    'exchange_name' => 'delays',
                    'queue_name_pattern' => 'delay_%exchange_name%_%routing_key%_%delay%',
                ],
                'host' => 'rabbit-is-dead.elmer',
                'port' => 5672,
                'vhost' => '/',
                'write_timeout' => '1',
            ],
        ];
    }

    private function getInnerProperty($object, string $reflectionProperty)
    {
        $reflection = new \ReflectionClass($object);
        $reflectionProperty = $reflection->getProperty($reflectionProperty);

        return $reflectionProperty->getValue($object);
    }
}

<?php

declare(strict_types=1);

namespace Answear\MessengerHeartbeatBundle\Tests\Unit\Transport;

use Answear\MessengerHeartbeatBundle\Heartbeat\PCNTLHeartbeatSender;
use Answear\MessengerHeartbeatBundle\Transport\AmqpTransport;
use Answear\MessengerHeartbeatBundle\Transport\TransportFactory;
use Monolog\Test\TestCase;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\Connection;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class TransportFactoryTest extends TestCase
{
    /**
     * @test
     *
     * @dataProvider provideSupportedScheme
     */
    public function supportsValidScheme(string $dsn): void
    {
        $factory = new TransportFactory($this->createMock(PCNTLHeartbeatSender::class));

        $this->assertTrue($factory->supports($dsn, []));
    }

    /**
     * @test
     *
     * @dataProvider provideTransportData
     */
    public function validTransport(string $dsn, array $options, array $expectedOptions): void
    {
        $factory = new TransportFactory($this->createMock(PCNTLHeartbeatSender::class));
        $transport = $factory->createTransport($dsn, $options, $this->createMock(SerializerInterface::class));
        $connection = $this->getInnerProperty($transport, 'connection');

        $this->assertInstanceOf(AmqpTransport::class, $transport);
        $this->assertInstanceOf(Connection::class, $connection);
        $this->assertSame($expectedOptions, $this->getInnerProperty($connection, 'connectionOptions'));
    }

    public function provideSupportedScheme(): iterable
    {
        yield 'amqp' => ['amqp://host'];
        yield 'amqps' => ['amqps://host'];
    }

    public function provideTransportData(): iterable
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
        $reflectionProperty->setAccessible(true);

        return $reflectionProperty->getValue($object);
    }
}

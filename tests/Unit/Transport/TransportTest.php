<?php

declare(strict_types=1);

namespace Answear\MessengerHeartbeatBundle\Tests\Unit\Transport;

use Answear\MessengerHeartbeatBundle\Transport\AmqpTransport;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ClockMock;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpFactory;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\Connection;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class TransportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function keepaliveCallTest(): void
    {
        $time = strtotime('2024-09-17 09:00:00');
        ClockMock::withClockMock($time);

        $queue = $this->createMock(\AMQPQueue::class);
        $queue->expects($this->once())
            ->method('declareQueue')
            ->willReturn(21);

        $amqpFactory = $this->createMock(AmqpFactory::class);
        $amqpFactory->method('createQueue')
            ->willReturn($queue);

        $connection = new Connection([], [], ['queueName1' => []], $amqpFactory);
        $reflection = new \ReflectionClass($connection);

        $property = $reflection->getProperty('lastActivityTime');
        self::assertSame(0, $property->getValue($connection));

        $transport = new AmqpTransport(
            $connection,
            $this->createMock(SerializerInterface::class),
        );

        $transport->keepalive(new Envelope(new \stdClass()));

        $property = $reflection->getProperty('lastActivityTime');
        self::assertGreaterThan(0, $property->getValue($connection));

        ClockMock::withClockMock(false);
    }
}

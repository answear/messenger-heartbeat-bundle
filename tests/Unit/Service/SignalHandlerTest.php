<?php

declare(strict_types=1);

namespace Answear\MessengerHeartbeatBundle\Tests\Unit\Service;

use Answear\MessengerHeartbeatBundle\Service\SignalHandler;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ClockMock;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpFactory;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpTransport;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\Connection;

class SignalHandlerTest extends TestCase
{
    #[Test]
    public function skippedIfNoTransports(): void
    {
        $handler = new SignalHandler();
        $handler->keepaliveTransports();

        $this->expectNotToPerformAssertions();
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

        $handler = new SignalHandler();
        $handler->keepaliveTransports();

        $handler->addTransport(new AmqpTransport($connection));
        $handler->keepaliveTransports();

        $property = $reflection->getProperty('lastActivityTime');
        self::assertGreaterThan(0, $property->getValue($connection));

        ClockMock::withClockMock(false);
    }
}

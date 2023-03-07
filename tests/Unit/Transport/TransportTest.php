<?php

declare(strict_types=1);

namespace Answear\MessengerHeartbeatBundle\Tests\Unit\Transport;

use Answear\MessengerHeartbeatBundle\Heartbeat\PCNTLHeartbeatSender;
use Answear\MessengerHeartbeatBundle\Transport\AmqpTransport;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\Connection;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class TransportTest extends TestCase
{
    /**
     * @test
     */
    public function heartbeatRegistered(): void
    {
        $heartbeatSender = $this->createMock(PCNTLHeartbeatSender::class);
        $heartbeatSender->expects($this->once())
            ->method('createForConnection')
            ->willReturn($heartbeatSender);
        $heartbeatSender->expects($this->once())
            ->method('register');

        $transport = new AmqpTransport(
            $this->createMock(Connection::class),
            $heartbeatSender,
            $this->createMock(SerializerInterface::class),
        );

        $transport->registerHeartbeatSender();
    }

    /**
     * @test
     */
    public function getHeartbeat(): void
    {
        $amqpConnection = $this->createMock(\AMQPConnection::class);
        $amqpConnection->expects($this->once())
            ->method('getHeartbeatInterval')
            ->willReturn(30);
        $queue = $this->createMock(\AMQPQueue::class);
        $queue->expects($this->once())
            ->method('getConnection')
            ->willReturn($amqpConnection);

        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('getQueueNames')
            ->willReturn(['fakeQueue']);
        $connection->expects($this->once())
            ->method('queue')
            ->with('fakeQueue')
            ->willReturn($queue);

        $transport = new AmqpTransport(
            $connection,
            $this->createMock(PCNTLHeartbeatSender::class),
            $this->createMock(SerializerInterface::class),
        );

        $this->assertSame(30, $transport->getHeartbeat());
    }
}

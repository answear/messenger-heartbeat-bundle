<?php

declare(strict_types=1);

namespace Answear\MessengerHeartbeatBundle\Tests\Unit\Heartbeat;

use Answear\MessengerHeartbeatBundle\Heartbeat\PCNTLHeartbeatSender;
use Answear\MessengerHeartbeatBundle\Transport\HeartbeatConnectionInterface;
use PHPUnit\Framework\TestCase;

class PCNTLHeartbeatSenderTest extends TestCase
{
    /**
     * @test
     */
    public function registeredChecksConnectionAndHeartbeatValue(): void
    {
        $connection = $this->createMock(HeartbeatConnectionInterface::class);
        $connection->expects($this->once())
            ->method('isConnected')
            ->willReturn(true);
        $connection->expects($this->once())
            ->method('getHeartbeat');

        $heartbeatSender = (new PCNTLHeartbeatSender())->createForConnection($connection);
        $heartbeatSender->register();
    }

    /**
     * @test
     */
    public function errorConnectionInactive(): void
    {
        $this->expectException(\AMQPConnectionException::class);

        $connection = $this->createMock(HeartbeatConnectionInterface::class);
        $connection->method('isConnected')
            ->willReturn(false);

        $heartbeatSender = (new PCNTLHeartbeatSender())->createForConnection($connection);
        $heartbeatSender->register();
    }
}

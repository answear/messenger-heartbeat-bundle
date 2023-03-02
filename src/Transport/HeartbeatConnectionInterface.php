<?php

declare(strict_types=1);

namespace Answear\MessengerHeartbeatBundle\Transport;

interface HeartbeatConnectionInterface
{
    public function isConnected(): bool;

    public function sendHeartbeat(): void;

    public function getHeartbeat(): int;

    public function registerHeartbeatSender(): void;
}

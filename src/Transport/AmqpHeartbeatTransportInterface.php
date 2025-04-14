<?php

declare(strict_types=1);

namespace Answear\MessengerHeartbeatBundle\Transport;

interface AmqpHeartbeatTransportInterface
{
    public function keepalive(): void;
}

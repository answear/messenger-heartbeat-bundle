<?php

declare(strict_types=1);

namespace Answear\MessengerHeartbeatBundle\Service;

use Answear\MessengerHeartbeatBundle\Transport\AmqpHeartbeatTransportInterface;

class SignalHandler
{
    /**
     * @var AmqpHeartbeatTransportInterface[]
     */
    private array $transports = [];

    public function addTransport(AmqpHeartbeatTransportInterface $transport): void
    {
        $this->transports[get_class($transport)] = $transport;
    }

    public function sendHeartbeat(): void
    {
        foreach ($this->transports as $transport) {
            $transport->keepalive();
        }
    }
}

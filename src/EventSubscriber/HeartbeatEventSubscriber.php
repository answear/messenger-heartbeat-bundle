<?php

declare(strict_types=1);

namespace Answear\MessengerHeartbeatBundle\EventSubscriber;

use Answear\MessengerHeartbeatBundle\Transport\HeartbeatConnectionInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerStartedEvent;

class HeartbeatEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ContainerInterface $receiverLocator
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            WorkerStartedEvent::class => 'heartbeat',
        ];
    }

    public function heartbeat(WorkerStartedEvent $event): void
    {
        $transportNames = $event->getWorker()->getMetadata()->getTransportNames();

        foreach ($transportNames as $transportName) {
            $transport = $this->receiverLocator->get($transportName);

            if ($transport instanceof HeartbeatConnectionInterface) {
                $transport->registerHeartbeatSender();
            }
        }
    }
}

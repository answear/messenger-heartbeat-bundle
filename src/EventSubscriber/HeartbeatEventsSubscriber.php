<?php

declare(strict_types=1);

namespace Answear\MessengerHeartbeatBundle\EventSubscriber;

use Answear\MessengerHeartbeatBundle\Service\SignalHandler;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Event\ConsoleAlarmEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpTransport;
use Symfony\Component\Messenger\Event\WorkerStartedEvent;

class HeartbeatEventsSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ContainerInterface $receiverLocator,
        private SignalHandler $signalHandler,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleAlarmEvent::class => 'keepalive',
            WorkerStartedEvent::class => 'setupSignalHandler',
        ];
    }

    public function keepalive(ConsoleAlarmEvent $event): void
    {
        $this->signalHandler->keepaliveTransports();
    }

    public function setupSignalHandler(WorkerStartedEvent $event): void
    {
        $transportNames = $event->getWorker()->getMetadata()->getTransportNames();

        foreach ($transportNames as $transportName) {
            $transport = $this->receiverLocator->get($transportName);

            if ($transport instanceof AmqpTransport) {
                $this->signalHandler->addTransport($transport);
            }
        }
    }
}

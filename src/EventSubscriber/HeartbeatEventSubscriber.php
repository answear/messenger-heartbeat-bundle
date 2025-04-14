<?php

declare(strict_types=1);

namespace Answear\MessengerHeartbeatBundle\EventSubscriber;

use Answear\MessengerHeartbeatBundle\Service\SignalHandler;
use Answear\MessengerHeartbeatBundle\Transport\AmqpHeartbeatTransportInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Event\ConsoleAlarmEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerStartedEvent;

class HeartbeatEventSubscriber implements EventSubscriberInterface
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
            WorkerStartedEvent::class => 'addTransport',
        ];
    }

    public function keepalive(ConsoleAlarmEvent $event): void
    {
        if (!$this->isPcntlSupported()) {
            return;
        }

        $this->signalHandler->sendHeartbeat();
    }

    public function addTransport(WorkerStartedEvent $event): void
    {
        $transportNames = $event->getWorker()->getMetadata()->getTransportNames();

        foreach ($transportNames as $transportName) {
            $transport = $this->receiverLocator->get($transportName);

            if ($transport instanceof AmqpHeartbeatTransportInterface) {
                $this->signalHandler->addTransport($transport);
            }
        }
    }

    private function isPcntlSupported(): bool
    {
        return \function_exists('pcntl_signal');
    }
}

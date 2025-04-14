<?php

declare(strict_types=1);

namespace Answear\MessengerHeartbeatBundle\EventSubscriber;

use Answear\MessengerHeartbeatBundle\Exception\RabbitMQTransportException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\EventListener\SendFailedMessageForRetryListener as SymfonySendFailedMessageForRetryListener;
use Symfony\Component\Messenger\Stamp\ErrorDetailsStamp;

class SendFailedMessageForRetryListener implements EventSubscriberInterface
{
    public function __construct(
        private SymfonySendFailedMessageForRetryListener $decoratedSubscriber,
        private ?LoggerInterface $logger = null,
    ) {
    }

    public function onMessageFailed(WorkerMessageFailedEvent $event): void
    {
        /** @var ErrorDetailsStamp $errorStamp */
        foreach ($event->getEnvelope()->all(ErrorDetailsStamp::class) as $errorStamp) {
            if (RabbitMQTransportException::class === $errorStamp->getExceptionClass()) {
                $this->logger?->info('[Keepalive] Skip message with RabbitMQTransportException from retry or critical log.');

                return;
            }
        }

        $this->decoratedSubscriber->onMessageFailed($event);
    }

    public static function getSubscribedEvents(): array
    {
        return SymfonySendFailedMessageForRetryListener::getSubscribedEvents();
    }
}

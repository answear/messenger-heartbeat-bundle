<?php

declare(strict_types=1);

namespace Answear\MessengerHeartbeatBundle\EventSubscriber;

use Answear\MessengerHeartbeatBundle\Exception\RabbitMQTransportException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\EventListener\SendFailedMessageToFailureTransportListener as SymfonyFailedTransportListener;
use Symfony\Component\Messenger\Stamp\ErrorDetailsStamp;

class SendFailedMessageToFailureTransportListener implements EventSubscriberInterface
{
    public function __construct(
        private SymfonyFailedTransportListener $decoratedSubscriber,
        private ?LoggerInterface $logger = null,
    ) {
    }

    public function onMessageFailed(WorkerMessageFailedEvent $event): void
    {
        /** @var ErrorDetailsStamp $errorStamp */
        foreach ($event->getEnvelope()->all(ErrorDetailsStamp::class) as $errorStamp) {
            if (RabbitMQTransportException::class === $errorStamp->getExceptionClass()) {
                $this->logger?->info('[Keepalive] Skip message with RabbitMQTransportException from failure transport.');

                return;
            }
        }

        $this->decoratedSubscriber->onMessageFailed($event);
    }

    public static function getSubscribedEvents(): array
    {
        return SymfonyFailedTransportListener::getSubscribedEvents();
    }
}

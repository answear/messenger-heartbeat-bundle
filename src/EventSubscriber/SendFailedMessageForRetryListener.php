<?php

declare(strict_types=1);

namespace Answear\MessengerHeartbeatBundle\EventSubscriber;

use Answear\MessengerHeartbeatBundle\Exception\RabbitMQTransportException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\EventListener\SendFailedMessageForRetryListener as SymfonyForRetryListener;
use Symfony\Component\Messenger\Exception\RejectRedeliveredMessageException;
use Symfony\Component\Messenger\Stamp\ErrorDetailsStamp;

class SendFailedMessageForRetryListener implements EventSubscriberInterface
{
    public function __construct(
        private SymfonyForRetryListener $decoratedSubscriber,
        private ?LoggerInterface $logger = null,
    ) {
    }

    public function onMessageFailed(WorkerMessageFailedEvent $event): void
    {
        $envelope = $event->getEnvelope();
        $errorStamp = $envelope->last(ErrorDetailsStamp::class);

        if (RejectRedeliveredMessageException::class === $errorStamp?->getExceptionClass()) {
            $this->decoratedSubscriber->onMessageFailed($event);

            return;
        }

        if ($this->isRabbitMQTransportException($errorStamp)) {
            $this->logger?->info(
                '[Keepalive] Skip message with RabbitMQTransportException from retry or critical log.',
                ['messageClass' => $envelope->getMessage()::class]
            );

            return;
        }

        $this->decoratedSubscriber->onMessageFailed($event);
    }

    public static function getSubscribedEvents(): array
    {
        return SymfonyForRetryListener::getSubscribedEvents();
    }

    private function isRabbitMQTransportException(?ErrorDetailsStamp $stamp): bool
    {
        if (null === $stamp) {
            return false;
        }

        return RabbitMQTransportException::class === $stamp->getExceptionClass() || str_contains($stamp->getExceptionMessage(), RabbitMQTransportException::MESSAGE);
    }
}

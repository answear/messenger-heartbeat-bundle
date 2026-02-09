<?php

declare(strict_types=1);

namespace Answear\MessengerHeartbeatBundle\EventSubscriber;

use Answear\MessengerHeartbeatBundle\Exception\RabbitMQTransportException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageSkipEvent;
use Symfony\Component\Messenger\EventListener\SendFailedMessageToFailureTransportListener as BaseListener;
use Symfony\Component\Messenger\Exception\RejectRedeliveredMessageException;
use Symfony\Component\Messenger\Stamp\ErrorDetailsStamp;

class SendFailedMessageToFailureTransportListener implements EventSubscriberInterface
{
    /**
     * @param BaseListener $decoratedSubscriber
     */
    public function __construct(
        private EventSubscriberInterface $decoratedSubscriber,
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
                '[Keepalive] Skip message with RabbitMQTransportException from failure transport.',
                ['messageClass' => $envelope->getMessage()::class]
            );

            return;
        }

        $this->decoratedSubscriber->onMessageFailed($event);
    }

    public function onMessageSkip(WorkerMessageSkipEvent $event): void
    {
        $this->decoratedSubscriber->onMessageSkip($event);
    }

    public static function getSubscribedEvents(): array
    {
        $events = [
            WorkerMessageFailedEvent::class => ['onMessageFailed', -100],
            WorkerMessageSkipEvent::class => ['onMessageSkip', -100],
        ];
        if ($events !== BaseListener::getSubscribedEvents()) {
            throw new \RuntimeException('Subscribed events are not the same for failure transport listener.');
        }

        // write array again, not "return $events", due to error highlight
        return [
            WorkerMessageFailedEvent::class => ['onMessageFailed', -100],
            WorkerMessageSkipEvent::class => ['onMessageSkip', -100],
        ];
    }

    private function isRabbitMQTransportException(?ErrorDetailsStamp $stamp): bool
    {
        if (null === $stamp) {
            return false;
        }

        return RabbitMQTransportException::class === $stamp->getExceptionClass() || str_contains($stamp->getExceptionMessage(), RabbitMQTransportException::MESSAGE);
    }
}

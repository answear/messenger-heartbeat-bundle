<?php

declare(strict_types=1);

namespace Answear\MessengerHeartbeatBundle\EventSubscriber;

use Answear\MessengerHeartbeatBundle\Exception\HeartbeatConnectionLostException;
use Psr\Log\LoggerInterface;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\EventListener\SendFailedMessageToFailureTransportListener as SymfonyFailedTransportListener;
use Symfony\Component\Messenger\Exception\RejectRedeliveredMessageException;
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
        $errorStamp = $event->getEnvelope()->last(ErrorDetailsStamp::class);

        if (null !== $errorStamp && RejectRedeliveredMessageException::class === $errorStamp->getExceptionClass()) {
            $this->decoratedSubscriber->onMessageFailed($event);

            return;
        }

        if (null !== $errorStamp && HeartbeatConnectionLostException::class === $errorStamp->getExceptionClass()) {
            $this->logger?->info('Skip message with HeartbeatConnectionLostException from failure transport.');

            return;
        }

        if (null !== $errorStamp) {
            $flattenException = $errorStamp->getFlattenException();

            if ($flattenException instanceof FlattenException) {
                foreach ($flattenException->getAllPrevious() as $previous) {
                    if (HeartbeatConnectionLostException::class === $previous->getClass()) {
                        $this->logger?->info('Skip message with HeartbeatConnectionLostException from failure transport by FlattenException.');

                        return;
                    }
                }
            }
        }

        $this->decoratedSubscriber->onMessageFailed($event);
    }

    public static function getSubscribedEvents(): array
    {
        return SymfonyFailedTransportListener::getSubscribedEvents();
    }
}

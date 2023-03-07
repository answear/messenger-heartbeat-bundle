<?php

declare(strict_types=1);

namespace Answear\MessengerHeartbeatBundle\Heartbeat;

use Answear\MessengerHeartbeatBundle\Exception\HeartbeatConnectionLostException;
use Answear\MessengerHeartbeatBundle\Transport\HeartbeatConnectionInterface;

class PCNTLHeartbeatSender
{
    private ?HeartbeatConnectionInterface $connection;

    public function createForConnection(HeartbeatConnectionInterface $connection): self
    {
        if (!$this->isSupported()) {
            throw new \RuntimeException('Signal-based heartbeat sender is unsupported');
        }

        $sender = new self();
        $sender->connection = $connection;

        return $sender;
    }

    public function __destruct()
    {
        $this->unregister();
    }

    public function register(): void
    {
        if (!$this->isSupported()) {
            throw new \RuntimeException('Signal-based heartbeat sender is unsupported');
        }

        if (!$this->connection->isConnected()) {
            throw new \AMQPConnectionException('Unable to register heartbeat sender, connection is not active');
        }

        $heartbeat = $this->connection->getHeartbeat();

        if ($heartbeat > 0) {
            $interval = (int) \ceil($heartbeat / 2);
            if (0 === $interval) {
                throw new \AMQPConnectionException('Unable to register heartbeat sender, heartbeat value to low');
            }

            \pcntl_async_signals(true);
            $this->registerListener($interval);
            \pcntl_alarm($interval);
        }
    }

    private function unregister(): void
    {
        $this->connection = null;

        if ($this->isSupported()) {
            \pcntl_signal(SIGALRM, SIG_IGN);
        }
    }

    private function isSupported(): bool
    {
        return \extension_loaded('pcntl');
    }

    private function registerListener(int $interval): void
    {
        \pcntl_signal(
            SIGALRM,
            function () use ($interval) {
                try {
                    $this->handleSignal();
                    if (null !== $this->connection) {
                        \pcntl_alarm($interval);
                    }
                } catch (HeartbeatConnectionLostException $connectionLostException) {
                    $this->unregister();

                    throw $connectionLostException;
                }
            },
            true
        );
    }

    protected function handleSignal(): void
    {
        if (!$this->connection) {
            return;
        }

        if (!$this->connection->isConnected()) {
            $this->unregister();

            return;
        }

        $this->connection->sendHeartbeat();
    }
}

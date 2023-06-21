<?php

namespace Tests\Support;

use Psr\Log\LoggerInterface;

class HistoryLogger implements LoggerInterface
{
    public array $history = [];

    public function emergency(\Stringable|string $message, array $context = []): void
    {
        $this->setHistory(__FUNCTION__, $message, $context);
    }

    public function alert(\Stringable|string $message, array $context = []): void
    {
        $this->setHistory(__FUNCTION__, $message, $context);
    }

    public function critical(\Stringable|string $message, array $context = []): void
    {
        $this->setHistory(__FUNCTION__, $message, $context);
    }

    public function error(\Stringable|string $message, array $context = []): void
    {
        $this->setHistory(__FUNCTION__, $message, $context);
    }

    public function warning(\Stringable|string $message, array $context = []): void
    {
        $this->setHistory(__FUNCTION__, $message, $context);
    }

    public function notice(\Stringable|string $message, array $context = []): void
    {
        $this->setHistory(__FUNCTION__, $message, $context);
    }

    public function info(\Stringable|string $message, array $context = []): void
    {
        $this->setHistory(__FUNCTION__, $message, $context);
    }

    public function debug(\Stringable|string $message, array $context = []): void
    {
        $this->setHistory(__FUNCTION__, $message, $context);
    }

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $this->setHistory($level, $message, $context);
    }

    private function setHistory(mixed $level, \Stringable|string $message, array $context = []): void
    {
        $this->history[] = [
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ];
    }
}

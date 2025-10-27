<?php

namespace Tests\Support;

use Psr\Log\Test\TestLogger;
use Throwable;

class HttpTestLogger extends TestLogger
{
    /** @var array<int, array{
     *     level: string,
     *     message: string,
     *     context: array{request?: array, response?: array, exception?: Throwable, time?: float, uri?: string}
     * }> */
    public array $records = [];

    #[\Override]
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->records[] = [
            'level' => (string) $level,
            'message' => (string) $message,
            'context' => $context,
        ];
    }
}

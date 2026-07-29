<?php

namespace Tests\Support;

use Override;
use Psr\Log\Test\TestLogger;
use Stringable;
use Throwable;

class HttpTestLogger extends TestLogger
{
    /** @var list<array{
     *     level: string,
     *     message: string,
     *     context: array{request?: array, response?: array, exception?: Throwable, time?: ?float, uri?: mixed}
     * }> */
    #[Override]
    public array $records = [];

    #[Override]
    public function log($level, string|Stringable $message, array $context = []): void
    {
        $this->records[] = [
            'level' => (string) $level,
            'message' => (string) $message,
            'context' => $context,
        ];
    }
}

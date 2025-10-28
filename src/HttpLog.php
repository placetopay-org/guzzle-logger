<?php

namespace PlacetoPay\GuzzleLogger;

use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\TransferStats;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Throwable;

readonly class HttpLog
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function log(
        RequestInterface $request,
        ?ResponseInterface $response = null,
        ?Throwable $exception = null,
        ?TransferStats $stats = null,
    ): void {
        $this->logRequest($request);

        if ($stats !== null) {
            $this->logStats($stats);
        }

        if ($response !== null) {
            $this->logResponse($request, $response);
        } else {
            $this->logException($exception);
        }
    }

    private function logRequest(RequestInterface $request): void
    {
        $this->logger->info('Guzzle HTTP Request', $this->getRequestContext($request));
    }

    private function logResponse(RequestInterface $request, ResponseInterface $response): void
    {
        $this->logger->info('Guzzle HTTP Response', $this->getResponseContext($request, $response));
    }

    private function logException(?Throwable $exception): void
    {
        if ($exception === null) {
            return;
        }

        $this->logger->error('Guzzle HTTP Exception', ['exception' => $exception]);
    }

    private function logStats(TransferStats $stats): void
    {
        $this->logger->debug('Guzzle HTTP statistics', [
            'time' => $stats->getTransferTime(),
            'uri' => $stats->getEffectiveUri(),
        ]);
    }

    private function getRequestContext(RequestInterface $request): array
    {
        return [
            'request' => array_filter([
                'url' => $request->getUri()->__toString(),
                'body' => $this->formatBody($request),
                'method' => $request->getMethod(),
                'headers' => $request->getHeaders(),
                'version' => 'HTTP/'.$request->getProtocolVersion(),
            ]),
        ];
    }

    private function getResponseContext(RequestInterface $request, ResponseInterface $response): array
    {
        return [
            'response' => array_filter([
                'url' => $request->getUri()->__toString(),
                'body' => $this->formatBody($response),
                'status_code' => $response->getStatusCode(),
                'headers' => $response->getHeaders(),
                'version' => 'HTTP/'.$response->getProtocolVersion(),
                'message' => $response->getReasonPhrase(),
            ]),
        ];
    }

    private function formatBody(MessageInterface $value): array|string
    {
        if ($value->getBody()->isSeekable() === false || $value->getBody()->isReadable() === false) {
            return 'GuzzleLogger can not log response/request because the body is not seekable/readable.';
        }

        $stream = $value->getBody();
        $stream->rewind();
        $body = Utils::copyToString($stream);
        $stream->rewind();

        if (empty($body)) {
            return 'Empty content';
        }

        $json = json_decode($body, true);
        return $json ?? self::bodySummary($body);
    }

    private static function bodySummary(string $body): string
    {
        $size = strlen($body);

        $summary = mb_substr($body, 0, 120);
        if ($size > 120) {
            $summary .= ' (truncated...)';
        }

        return "Raw content summary: $summary";
    }
}

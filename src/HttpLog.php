<?php

namespace PlacetopayOrg\GuzzleLogger;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class HttpLog
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function log(
        RequestInterface $request,
        ResponseInterface $response = null,
        ?Throwable $exception = null
    ): void {
        $this->logRequest($request);

        if ($response !== null) {
            $this->logResponse($response);
        } else {
            $this->logException($exception);
        }
    }

    private function logRequest(RequestInterface $request): void
    {
        $this->logger->info('Guzzle HTTP Request', $this->getRequestContext($request));
    }

    private function logResponse(ResponseInterface $response): void
    {
        $this->logger->info('Guzzle HTTP Response', $this->getResponseContext($response));
    }

    private function logException(?Throwable $exception): void
    {
        if ($exception === null) {
            return;
        }

        $this->logger->error('Guzzle HTTP Exception', ['exception' => $exception]);
    }

    private function getRequestContext(RequestInterface $request): array
    {
        $context = [
            'request' => [
                'method' => $request->getMethod(),
                'headers' => $request->getHeaders(),
                'url' => $request->getUri()->__toString(),
                'version' => 'HTTP/'.$request->getProtocolVersion(),
            ],
        ];

        if ($request->getBody()->getSize() > 0) {
            $context['request']['body'] = $this->formatBody($request);
        }

        return $context;
    }

    private function getResponseContext(ResponseInterface $response): array
    {
        $context = [
            'response' => [
                'headers' => $response->getHeaders(),
                'status_code' => $response->getStatusCode(),
                'version' => 'HTTP/'.$response->getProtocolVersion(),
                'message' => $response->getReasonPhrase(),
            ],
        ];

        if ($response->getBody()->getSize() > 0) {
            $context['response']['body'] = $this->formatBody($response);
        }

        return $context;
    }

    private function formatBody(MessageInterface $response): array|string
    {
        $body = $response->getBody()->__toString();

        $json = json_decode($body, true);

        if (! empty($json)) {
            return $json;
        }

        return 'Could not json decode body';
    }
}

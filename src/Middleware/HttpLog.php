<?php

namespace PlacetopayOrg\GuzzleLogger\Middleware;

use PlacetopayOrg\GuzzleLogger\DTO\HttpLogConfig;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class HttpLog
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly HttpLogConfig $config,
    )
    {
    }

    public function log(RequestInterface $request, ResponseInterface $response = null): void
    {
        $this->logRequest($request);

        if ($response !== null) {
            $this->logResponse($response);
        }
    }

    private function logRequest(RequestInterface $request): void
    {
        $context = [];
        $context['request']['method'] = $request->getMethod();
        $context['request']['headers'] = $request->getHeaders();
        $context['request']['url'] = $request->getUri()->__toString();
        $context['request']['version'] = 'HTTP/'.$request->getProtocolVersion();

        if ($request->getBody()->getSize() > 0) {
            $context['request']['body'] = $this->formatBody($request);
        }

        $this->logger->info($this->config->message . ' Request', $context);
    }

    private function logResponse(ResponseInterface $response): void
    {
        $context = [];
        $context['response']['headers'] = $response->getHeaders();
        $context['response']['status_code'] = $response->getStatusCode();
        $context['response']['version'] = 'HTTP/'.$response->getProtocolVersion();
        $context['response']['message'] = $response->getReasonPhrase();

        if ($response->getBody()->getSize() > 0) {
            $context['response']['body'] = $this->formatBody($response);
        }

        $this->logger->info($this->config->message. ' Response', $context);
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

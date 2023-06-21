<?php

namespace PlacetopayOrg\GuzzleLogger\Middleware;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class HttpLogger
{
    public function __construct(private readonly LoggerInterface $logger)
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

        $this->logger->info('[REST-GATEWAYS] Guzzle HTTP Request', $context);
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

        $this->logger->info('[REST-GATEWAYS] Guzzle HTTP Request', $context);
    }

    private function formatBody(MessageInterface $request): string
    {
        $body = $request->getBody()->__toString();

        $json = json_decode($body, true);

        if (! empty($json)) {
            return $json;
        }

        return 'Could not json decode body';
    }
}

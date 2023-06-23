<?php

namespace PlacetopayOrg\GuzzleLogger\Middleware;

use PlacetopayOrg\GuzzleLogger\DTO\HttpLogConfig;
use PlacetopayOrg\GuzzleLogger\Helpers\ArrHelper;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class HttpLog
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly HttpLogConfig $config,
    ) {
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
        $this->logger->info($this->config->message.' Request', $this->getRequestContext($request));
    }

    private function logResponse(ResponseInterface $response): void
    {
        $this->logger->info($this->config->message.' Response', $this->getResponseContext($response));
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

        if ($fields = $this->config->fieldsToSanitize) {
            $this->sanitizer($context, $fields);
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

        if ($fields = $this->config->fieldsToSanitize) {
            $this->sanitizer($context, $fields);
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

    private function sanitizer(array &$data, array $fields): void
    {
        foreach ($fields as $key => $format) {
            if (is_numeric($key)) {
                $key = $format;
                $format = '***';
            }

            if (is_callable($format)) {
                if ($value = ArrHelper::get($data, $key)) {
                    $format = $format($value);
                } else {
                    continue;
                }
            }

            ArrHelper::set($data, $key, $format);
        }
    }
}

<?php

namespace PlacetopayOrg\GuzzleLogger\Middleware;

use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class HttpLogMiddleware
{
    private readonly HttpLogger $strategy;

    public function __construct(LoggerInterface $log)
    {
        $this->strategy = new HttpLogger($log);
    }

    public function __invoke(callable $handler): callable
    {
        return fn (RequestInterface $request, array $options = []): PromiseInterface => $handler($request, $options)
            ->then($this->onFulfilled($request, $options), $this->onRejected($request, $options));
    }

    private function onFulfilled(RequestInterface $request, array $options): callable
    {
        return function (ResponseInterface $response) use ($request, $options) {
            $this->strategy->log($request, $response);

            return $response;
        };
    }

    private function onRejected(RequestInterface $request, array $options): callable
    {
        return fn ($reason) => Create::rejectionFor($reason);
    }
}
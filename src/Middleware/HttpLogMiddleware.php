<?php

namespace PlacetopayOrg\GuzzleLogger\Middleware;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use PlacetopayOrg\GuzzleLogger\HttpLog;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

final class HttpLogMiddleware
{
    private readonly HttpLog $strategy;

    public function __construct(LoggerInterface $log)
    {
        $this->strategy = new HttpLog($log);
    }

    public function __invoke(callable $handler): callable
    {
        return fn (RequestInterface $request, array $options = []): PromiseInterface => $handler($request, $options)
            ->then(
                $this->onFulfilled($request, $options),
                $this->onRejected($request, $options)
            );
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
        return function (\Exception $reason) use ($request, $options) {
            if ($reason instanceof RequestException && $reason->hasResponse() === true) {
                $this->strategy->log($request, $reason->getResponse());

                return Create::rejectionFor($reason);
            }

            $this->strategy->log($request, null, $reason);

            return Create::rejectionFor($reason);
        };
    }
}

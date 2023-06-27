<?php

namespace PlacetoPay\GuzzleLogger\Middleware;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\TransferStats;
use PlacetoPay\GuzzleLogger\HttpLog;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

final class HttpLogMiddleware
{
    private readonly HttpLog $strategy;

    private bool $logStatistics = false;

    private ?TransferStats $stats = null;

    public function __construct(LoggerInterface $log)
    {
        $this->strategy = new HttpLog($log);
    }

    public function __invoke(callable $handler): callable
    {
        return function (RequestInterface $request, array $options) use ($handler): PromiseInterface {
            $this->setOptions($options);

            if ($this->logStatistics && ! isset($options['on_stats'])) {
                $options['on_stats'] = function (TransferStats $stats) {
                    $this->stats = $stats;
                };
            }

            return $handler($request, $options)
                ->then(
                    $this->onFulfilled($request),
                    $this->onRejected($request)
                );
        };
    }

    private function onFulfilled(RequestInterface $request): callable
    {
        return function (ResponseInterface $response) use ($request) {
            $this->strategy->log($request, $response, null, $this->stats);

            return $response;
        };
    }

    private function onRejected(RequestInterface $request): callable
    {
        return function (\Exception $reason) use ($request) {
            if ($reason instanceof RequestException && $reason->hasResponse() === true) {
                $this->strategy->log($request, $reason->getResponse(), null, $this->stats);

                return Create::rejectionFor($reason);
            }

            $this->strategy->log($request, null, $reason, $this->stats);

            return Create::rejectionFor($reason);
        };
    }

    private function setOptions(array $options): void
    {
        if (! isset($options['log'])) {
            return;
        }

        $options = $options['log'];

        $options = array_merge([
            'statistics' => $this->logStatistics,
        ], $options);

        $this->stats = null;
        $this->logStatistics = $options['statistics'];
    }
}

<?php

namespace Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use PlacetopayOrg\GuzzleLogger\DTO\HttpLogConfig;
use PlacetopayOrg\GuzzleLogger\Middleware\HttpLogMiddleware;
use Tests\Support\HistoryLogger;

class HttpLogMiddlewareTest extends TestCase
{
    private MockHandler $mockHandler;

    private HistoryLogger $logger;

    public function setUp(): void
    {
        parent::setUp();
        $this->mockHandler = new MockHandler();
        $this->logger = new HistoryLogger();
    }

    public function test_log_successful_transaction(): void
    {
        $this->appendResponse(body: json_encode(['test' => 'test_log']))->getClient([], 'HTTP TEST')->get('/');

        $this->assertCount(2, $this->logger->history);
        $this->assertSame('info', $this->logger->history[0]['level']);
        $this->assertSame('HTTP TEST Request', $this->logger->history[0]['message']);
        $this->assertSame('HTTP TEST Response', $this->logger->history[1]['message']);
        $this->assertArrayHasKey('test', $this->logger->history[1]['context']['response']['body']);
        $this->assertSame('test_log', $this->logger->history[1]['context']['response']['body']['test']);
    }

    public function test_log_successful_transaction_with_default_message(): void
    {
        $this->appendResponse(body: json_encode(['test' => 'test_log']))->getClient()->get('/');
        $this->assertCount(2, $this->logger->history);
        $this->assertSame('info', $this->logger->history[0]['level']);
        $this->assertSame('Guzzle HTTP Request', $this->logger->history[0]['message']);
        $this->assertSame('info', $this->logger->history[1]['level']);
        $this->assertSame('Guzzle HTTP Response', $this->logger->history[1]['message']);
        $this->assertArrayHasKey('test', $this->logger->history[1]['context']['response']['body']);
        $this->assertSame('test_log', $this->logger->history[1]['context']['response']['body']['test']);
    }

    public function test_log_only_unsuccessful_Transaction()
    {
        try {
            $this->appendResponse(200)
                ->appendResponse(500);
            $client = $this->getClient([
                'log' => [
                    'requests' => false,
                ],
            ]);
            $client->get('/');
            $client->get('/');
        } catch (\Exception) {
        }

        $this->assertCount(2, $this->logger->history);
        $this->assertEquals('info', $this->logger->history[0]['level']);
        $this->assertEquals('Guzzle HTTP Request', $this->logger->history[0]['message']);
        $this->assertEquals('info', $this->logger->history[1]['level']);
        $this->assertEquals('Guzzle HTTP Response', $this->logger->history[1]['message']);
    }

    private function appendResponse(
        int $code = 200,
        array $headers = [],
        string $body = '',
        string $version = '1.1',
        string $reason = null,
    ): self {
        $this->mockHandler->append(new Response($code, $headers, $body, $version, $reason));

        return $this;
    }

    private function getClient(array $options = [], ?string $message = null): Client
    {
        $stack = HandlerStack::create($this->mockHandler);
        $httpConfig = $message ? new HttpLogConfig(message: $message) : null;
        $stack->unshift(new HttpLogMiddleware($this->logger, $httpConfig));
        $handler = array_merge(['handler' => $stack], $options);

        return new Client($handler);
    }
}

<?php

namespace Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use PlacetopayOrg\GuzzleLogger\LoggerWithSanitizer;
use PlacetopayOrg\GuzzleLogger\Middleware\HttpLogMiddleware;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\Test\TestLogger;

class HttpLogMiddlewareTest extends TestCase
{
    private MockHandler $mockHandler;

    private TestLogger $logger;

    public function setUp(): void
    {
        parent::setUp();
        $this->mockHandler = new MockHandler();
        $this->logger = new TestLogger();
    }

    public function test_log_successful_transaction(): void
    {
        $logger = new class($this->logger) extends AbstractLogger
        {
            public function __construct(private LoggerInterface $logger){}

            public function log($level, \Stringable|string $message, array $context = []): void
            {
                $this->logger->log($level, '[company/library] '. $message,$context);
            }
        };

        $this->appendResponse(body: json_encode(['test' => 'test_log']))
            ->getClient(logger: $logger)
            ->get('/');

        $this->assertCount(2, $this->logger->records);
        $this->assertSame('info', $this->logger->records[0]['level']);
        $this->assertSame('[company/library] Guzzle HTTP Request', $this->logger->records[0]['message']);
        $this->assertSame('[company/library] Guzzle HTTP Response', $this->logger->records[1]['message']);
        $this->assertArrayHasKey('test', $this->logger->records[1]['context']['response']['body']);
        $this->assertSame('test_log', $this->logger->records[1]['context']['response']['body']['test']);
    }

    public function test_log_successful_transaction_with_default_message(): void
    {
        $this->appendResponse(body: json_encode(['test' => 'test_log']))->getClient()->get('/');
        $this->assertCount(2, $this->logger->records);
        $this->assertSame('info', $this->logger->records[0]['level']);
        $this->assertSame('Guzzle HTTP Request', $this->logger->records[0]['message']);
        $this->assertSame('info', $this->logger->records[1]['level']);
        $this->assertSame('Guzzle HTTP Response', $this->logger->records[1]['message']);
        $this->assertArrayHasKey('test', $this->logger->records[1]['context']['response']['body']);
        $this->assertSame('test_log', $this->logger->records[1]['context']['response']['body']['test']);
    }

    public function test_log_successful_transaction_masking_data(): void
    {
        $requestBody = [
            'number' => 'value',
            'key1' => ['number' => 'value'],
            'key2' => ['key3' => ['number' => '4111111111111111']],
        ];

        $responseBody = [
            'number' => 'value',
            'key1' => ['number' => 'value2'],
            'key2' => ['key3' => ['number' => '4111111111111111']],
        ];

        $fieldsToSanitize = [
            /** request */
            'request.body.number',
            'request.body.key1.number' => '123',
            'request.body.key2.key3.number' => fn ($value) => preg_replace('/(\d{6})(\d{3,9})(\d{4})/', '$1*****$3', (string) $value),
            /** response */
            'response.body.key2.key3.number' => fn ($value) => preg_replace('/(\d{6})(\d{3,9})(\d{4})/', '$1*****$3', (string) $value),
        ];

        $this->appendResponse(body: json_encode($responseBody))
            ->getClient(fieldsToSanitize: $fieldsToSanitize)
            ->send(new Request('POST', '/', [], json_encode($requestBody)));

        $this->assertCount(2, $this->logger->records);

        $requestData = $this->logger->records[0]['context']['request']['body'];
        $this->assertSame('***', $requestData['number']);
        $this->assertSame('123', $requestData['key1']['number']);
        $this->assertSame('411111*****1111', $requestData['key2']['key3']['number']);

        $responseData = $this->logger->records[1]['context']['response']['body'];

        $this->assertSame('value', $responseData['number']);
        $this->assertSame('value2', $responseData['key1']['number']);
        $this->assertSame('411111*****1111', $responseData['key2']['key3']['number']);
    }

    public function test_log_unsuccessful_Transaction()
    {
        try {
            $this->appendResponse()
                ->appendResponse(500);

            $client = $this->getClient();
            $client->get('/');
            $client->get('/');
        } catch (\Exception) {
        }

        $this->assertEquals('Guzzle HTTP Request', $this->logger->records[0]['message']);

        $this->assertEquals('Guzzle HTTP Response', $this->logger->records[1]['message']);
        $this->assertEquals(200, $this->logger->records[1]['context']['response']['status_code']);

        $this->assertEquals('Guzzle HTTP Request', $this->logger->records[2]['message']);

        $this->assertEquals('Guzzle HTTP Response', $this->logger->records[3]['message']);
        $this->assertEquals(500, $this->logger->records[3]['context']['response']['status_code']);
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

    private function getClient(array $options = [], array $fieldsToSanitize = [], ?LoggerInterface $logger = null): Client
    {
        $stack = HandlerStack::create($this->mockHandler);
        if (!$logger) {
            $logger = new LoggerWithSanitizer($this->logger, $fieldsToSanitize);
        }
        $stack->unshift(new HttpLogMiddleware($logger));
        $handler = array_merge(['handler' => $stack], $options);

        return new Client($handler);
    }
}

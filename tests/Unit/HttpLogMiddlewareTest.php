<?php

namespace Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use PlacetoPay\GuzzleLogger\LoggerWithSanitizer;
use PlacetoPay\GuzzleLogger\Middleware\HttpLogMiddleware;
use PlacetoPay\GuzzleLogger\ValueSanitizer;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\Test\TestLogger;

class HttpLogMiddlewareTest extends TestCase
{
    private MockHandler $mockHandler;

    private TestLogger $logger;

    #[\Override]
    public function setUp(): void
    {
        parent::setUp();
        $this->mockHandler = new MockHandler();
        $this->logger = new TestLogger();
    }

    public function test_log_successful_transaction_with_default_message(): void
    {
        $this->appendResponse(headers: ['Content-Type' => 'application/json'], body: json_encode(['res_param' => 'res_param_value']))
            ->getClient()
            ->get('/', [
                'json' => ['req_param' => 'req_param_value'],
                'headers' => ['Accept-Language' => 'es_CO'],
            ]);

        $this->assertCount(2, $this->logger->records);

        // Assert Request
        $this->assertSame('info', $this->logger->records[0]['level']);
        $this->assertSame('Guzzle HTTP Request', $this->logger->records[0]['message']);
        $this->assertSame('GET', $this->logger->records[0]['context']['request']['method']);
        $this->assertSame('https://example.com/', $this->logger->records[0]['context']['request']['url']);
        $this->assertSame('HTTP/1.1', $this->logger->records[0]['context']['request']['version']);
        $this->assertSame(['es_CO'], $this->logger->records[0]['context']['request']['headers']['Accept-Language']);
        $this->assertSame(['req_param' => 'req_param_value'], $this->logger->records[0]['context']['request']['body']);

        // Assert Response
        $this->assertSame('info', $this->logger->records[1]['level']);
        $this->assertSame('Guzzle HTTP Response', $this->logger->records[1]['message']);
        $this->assertSame(['Content-Type' => ['application/json']], $this->logger->records[1]['context']['response']['headers']);
        $this->assertSame('https://example.com/', $this->logger->records[1]['context']['response']['url']);
        $this->assertSame(200, $this->logger->records[1]['context']['response']['status_code']);
        $this->assertSame('HTTP/1.1', $this->logger->records[1]['context']['response']['version']);
        $this->assertSame('OK', $this->logger->records[1]['context']['response']['message']);
        $this->assertSame(['res_param' => 'res_param_value'], $this->logger->records[1]['context']['response']['body']);
    }

    public function test_log_successful_without_body_in_request_and_response(): void
    {
        $this->appendResponse(headers: ['Content-Type' => 'application/json'])
            ->getClient()
            ->get('/', [
                'headers' => ['Accept-Language' => 'es_CO'],
            ]);

        $this->assertCount(2, $this->logger->records);

        // Assert Request
        $this->assertSame('info', $this->logger->records[0]['level']);
        $this->assertSame('Guzzle HTTP Request', $this->logger->records[0]['message']);
        $this->assertFalse(isset($this->logger->records[0]['context']['request']['body']));

        // Assert Response
        $this->assertSame('info', $this->logger->records[1]['level']);
        $this->assertSame('Guzzle HTTP Response', $this->logger->records[1]['message']);
        $this->assertFalse(isset($this->logger->records[0]['context']['response']['body']));
    }

    public function test_log_not_successful_transaction()
    {
        try {
            $this->appendResponse(500)->getClient()->get('/');
        } catch (\Exception) {
        }

        $this->assertEquals('Guzzle HTTP Request', $this->logger->records[0]['message']);
        $this->assertEquals('Guzzle HTTP Response', $this->logger->records[1]['message']);
        $this->assertSame(500, $this->logger->records[1]['context']['response']['status_code']);
        $this->assertSame('Internal Server Error', $this->logger->records[1]['context']['response']['message']);
    }

    public function test_log_when_transfer_exception_occurs()
    {
        try {
            $this->mockHandler->append(new TransferException('internal server Error', 500));

            $this->getClient()->get('/');
        } catch (\Exception) {
        }

        $this->assertEquals('Guzzle HTTP Request', $this->logger->records[0]['message']);
        $this->assertEquals('Guzzle HTTP Exception', $this->logger->records[1]['message']);

        $exception = $this->logger->records[1]['context']['exception'];
        $this->assertInstanceOf(TransferException::class, $exception);
        $this->assertSame('internal server Error', $exception->getMessage());
        $this->assertSame(500, $exception->getCode());
    }

    public function test_message_logger_can_be_decorated(): void
    {
        $logger = new class($this->logger) extends AbstractLogger {
            public function __construct(private readonly LoggerInterface $logger)
            {
            }

            public function log($level, \Stringable|string $message, array $context = []): void
            {
                $this->logger->log($level, '[company/library] '.$message, $context);
            }
        };

        $this->appendResponse(body: json_encode(['test' => 'test_log']))
            ->getClient(logger: $logger)
            ->get('/');

        $this->assertSame('[company/library] Guzzle HTTP Request', $this->logger->records[0]['message']);
        $this->assertSame('[company/library] Guzzle HTTP Response', $this->logger->records[1]['message']);
    }

    public function test_log_successful_transaction_sanitizing_data(): void
    {
        $requestBody = [
            'email' => 'Jhon@example.com',
            'user' => ['document' => '1234567890'],
            'instrument' => [
                'card' => [
                    'number' => '4111111111111111',
                    'cvv' => '123',
                ],
            ],
        ];

        $responseBody = [
            'user' => ['username' => 'jhondoe123', 'name' => 'Jhon Doe'],
            'instrument' => ['card' => ['number' => '4111111111111111']],
        ];

        $fieldsToSanitize = [
            /** request */
            'request.body.email',
            'request.body.user.document' => '[FILTERED]',
            'request.body.instrument.card.number' => ValueSanitizer::CARD_NUMBER,
            'request.body.instrument.card.cvv' => ValueSanitizer::DEFAULT,
            /** response */
            'response.body.instrument.card.number' => fn ($value) => preg_replace('/(\d{6})(\d{3,9})(\d{4})/', '$1#####$3', (string) $value),
            'response.body.not_existing' => ValueSanitizer::DEFAULT,
        ];

        $this->appendResponse(body: json_encode($responseBody))
            ->getClient(logger: new LoggerWithSanitizer($this->logger, $fieldsToSanitize))
            ->send(new Request('POST', '/', [], json_encode($requestBody)));

        $requestData = $this->logger->records[0]['context']['request']['body'];
        $this->assertSame('****', $requestData['email']);
        $this->assertSame('[FILTERED]', $requestData['user']['document']);
        $this->assertSame('411111******1111', $requestData['instrument']['card']['number']);
        $this->assertSame('****', $requestData['instrument']['card']['cvv']);

        $responseData = $this->logger->records[1]['context']['response']['body'];
        $this->assertSame('jhondoe123', $responseData['user']['username']);
        $this->assertSame('Jhon Doe', $responseData['user']['name']);
        $this->assertSame('411111#####1111', $responseData['instrument']['card']['number']);
    }

    public function test_log_successful_transaction_with_transfer_stats()
    {
        $this->appendResponse(headers: ['Content-Type' => 'application/json'], body: json_encode(['res_param' => 'res_param_value']))
            ->getClient(options: ['log' => ['statistics' => true]])
            ->get('/', [
                'json' => ['req_param' => 'req_param_value'],
                'headers' => ['Accept-Language' => 'es_CO'],
            ]);

        $this->assertSame('debug', $this->logger->records[1]['level']);
        $this->assertEquals('Guzzle HTTP statistics', $this->logger->records[1]['message']);
        $this->assertNotNull($this->logger->records[1]['context']['time']);
        $this->assertNotNull($this->logger->records[1]['context']['uri']);
    }

    public function test_logs_error_when_json_decode_fails_in_response_body(): void
    {
        $this->appendResponse(
            headers: ['Content-Type' => 'text/html'],
            body: 'JSON not valid <html>'
        )->getClient()->get('/');

        $this->assertSame('Guzzle HTTP Request', $this->logger->records[0]['message']);
        $this->assertSame('Guzzle HTTP Response', $this->logger->records[1]['message']);

        $responseContext = $this->logger->records[1]['context']['response'];
        $this->assertSame('Failed to decode JSON from body: JSON not valid <html>', $responseContext['body']);
    }

    public function test_logs_summary_body_is_empty(): void
    {
        $this->appendResponse()->getClient()->get('/');

        $this->assertSame('Guzzle HTTP Request', $this->logger->records[0]['message']);
        $this->assertSame('Guzzle HTTP Response', $this->logger->records[1]['message']);

        $responseContext = $this->logger->records[1]['context']['response'];
        $this->assertSame('Failed empty response body', $responseContext['body']);
    }

    public function test_logs_summary_when_json_decode_fails_and_truncates_body(): void
    {
        $this->appendResponse(
            headers: ['Content-Type' => 'text/plain'],
            body: str_repeat('A', 131)
        )->getClient()->get('/');

        $this->assertSame('Guzzle HTTP Response', $this->logger->records[1]['message']);
        $responseContext = $this->logger->records[1]['context']['response'];

        $this->assertStringContainsString(
            'Failed to decode JSON from body: AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA (truncated...)',
            $responseContext['body']
        );
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

    private function getClient(?LoggerInterface $logger = null, array $options = []): Client
    {
        $stack = HandlerStack::create($this->mockHandler);

        if (! $logger) {
            $logger = new LoggerWithSanitizer($this->logger);
        }

        $stack->unshift(new HttpLogMiddleware($logger));

        $stack = array_merge([
            'handler' => $stack,
            'base_uri' => 'https://example.com',
        ], $options);

        return new Client($stack);
    }
}

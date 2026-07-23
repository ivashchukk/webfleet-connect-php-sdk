<?php

declare(strict_types=1);

namespace Webfleet\Connect\Tests;

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Webfleet\Connect\ClientOptions;
use Webfleet\Connect\Credentials;
use Webfleet\Connect\Exception\UnexpectedResponseException;
use Webfleet\Connect\Exception\WebfleetApiException;
use Webfleet\Connect\Http\NullRateLimiter;
use Webfleet\Connect\Tests\Support\QueueHttpClient;
use Webfleet\Connect\WebfleetClient;

final class TransportTest extends TestCase
{
    public function testBuildsAuthenticatedEncodedJsonRequest(): void
    {
        $http = new QueueHttpClient(new Response(200, ['Content-Type' => 'application/json'], '[{"value":1}]'));
        $client = $this->client($http);

        $response = $client->request('showSomething', [
            'filterstring' => 'Łódź & fleet',
            'enabled' => true,
            'tag' => ['one', 'two'],
            'ignored' => null,
        ]);

        self::assertSame([['value' => 1]], $response->rows);
        $request = $http->requests[0];
        self::assertSame('Basic ' . base64_encode('api-user:secret-password'), $request->getHeaderLine('Authorization'));

        parse_str($request->getUri()->getQuery(), $query);
        self::assertSame('demo-account', $query['account']);
        self::assertSame('api-key', $query['apikey']);
        self::assertSame('showSomething', $query['action']);
        self::assertSame('json', $query['outputformat']);
        self::assertSame('true', $query['useUTF8']);
        self::assertSame('true', $query['useISO8601']);
        self::assertStringNotContainsString('secret-password', (string) $request->getUri());
        self::assertStringNotContainsString('api-user', (string) $request->getUri());
        self::assertSame(2, substr_count($request->getUri()->getQuery(), 'tag='));
    }

    public function testRejectsReservedParameters(): void
    {
        $client = $this->client(new QueueHttpClient());

        $this->expectException(\InvalidArgumentException::class);
        $client->request('showTracks', ['ApiKey' => 'override']);
    }

    public function testCredentialsRedactSecretsWhenInspected(): void
    {
        $debug = print_r(new Credentials('demo-account', 'api-user', 'secret-password', 'api-key'), true);

        self::assertStringNotContainsString('secret-password', $debug);
        self::assertStringNotContainsString('api-key', $debug);
        self::assertStringContainsString('[redacted]', $debug);
    }

    public function testRaisesHeaderApiError(): void
    {
        $http = new QueueHttpClient(new Response(200, [
            'X-Webfleet-Errorcode' => '1001',
            'X-Webfleet-Errormessage' => 'Invalid credentials',
        ], ''));

        try {
            $this->client($http)->request('showTracks');
            self::fail('Expected an API exception.');
        } catch (WebfleetApiException $exception) {
            self::assertSame('1001', $exception->apiErrorCode);
            self::assertSame('showTracks', $exception->action);
            self::assertStringNotContainsString('api-key', $exception->getMessage());
        }
    }

    public function testRaisesPlainTextApiError(): void
    {
        $client = $this->client(new QueueHttpClient(new Response(200, [], '63,document is empty')));

        $this->expectException(WebfleetApiException::class);
        $this->expectExceptionMessage('(63): document is empty');
        $client->request('showTracks');
    }

    public function testRejectsMalformedJson(): void
    {
        $client = $this->client(new QueueHttpClient(new Response(200, [], '{broken')));

        $this->expectException(UnexpectedResponseException::class);
        $client->request('showTracks');
    }

    private function client(QueueHttpClient $http): WebfleetClient
    {
        return WebfleetClient::withHttpClient(
            new Credentials('demo-account', 'api-user', 'secret-password', 'api-key'),
            $http,
            new HttpFactory(),
            new ClientOptions(rateLimiting: false),
            new NullRateLimiter(),
        );
    }
}

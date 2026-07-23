<?php

declare(strict_types=1);

namespace Webfleet\Connect\Http;

use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Stringable;
use Webfleet\Connect\ClientOptions;
use Webfleet\Connect\Contract\RateLimiterInterface;
use Webfleet\Connect\Credentials;
use Webfleet\Connect\Exception\HttpException;
use Webfleet\Connect\Exception\TransportException;
use Webfleet\Connect\Exception\UnexpectedResponseException;
use Webfleet\Connect\Exception\WebfleetApiException;

final readonly class ApiTransport
{
    private const ENDPOINT = 'https://csv.webfleet.com/extern';

    /** @var list<string> */
    private const RESERVED_PARAMETERS = [
        'account',
        'username',
        'password',
        'sessiontoken',
        'apikey',
        'action',
        'lang',
        'outputformat',
        'useutf8',
        'useiso8601',
    ];

    public function __construct(
        private Credentials $credentials,
        private ClientOptions $options,
        private ClientInterface $httpClient,
        private RequestFactoryInterface $requestFactory,
        private RateLimiterInterface $rateLimiter,
    ) {}

    /**
     * @param array<string, null|bool|float|int|string|Stringable|list<bool|float|int|string|Stringable>> $parameters
     */
    public function request(string $action, array $parameters = []): RawResponse
    {
        if (1 !== preg_match('/^[A-Za-z][A-Za-z0-9_]*$/D', $action)) {
            throw new \InvalidArgumentException('A Webfleet action must contain only letters, numbers, and underscores and start with a letter.');
        }

        foreach (array_keys($parameters) as $name) {
            if (in_array(strtolower($name), self::RESERVED_PARAMETERS, true)) {
                throw new \InvalidArgumentException(sprintf('Parameter "%s" is managed by the SDK and cannot be overridden.', $name));
            }
        }

        $query = $this->encodeQuery([
            'lang' => $this->options->language,
            'account' => $this->credentials->account(),
            'apikey' => $this->credentials->apiKey(),
            'action' => $action,
            'outputformat' => 'json',
            'useUTF8' => true,
            'useISO8601' => true,
            ...$parameters,
        ]);

        $request = $this->requestFactory
            ->createRequest('GET', self::ENDPOINT . '?' . $query)
            ->withHeader('Accept', 'application/json')
            ->withHeader('Authorization', $this->credentials->basicAuthorization());

        $this->rateLimiter->acquire($action);

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $exception) {
            throw new TransportException($action, $exception);
        }

        $status = $response->getStatusCode();
        $body = trim((string) $response->getBody());
        $errorCode = trim($response->getHeaderLine('X-Webfleet-Errorcode'));
        if ('' !== $errorCode) {
            $message = trim($response->getHeaderLine('X-Webfleet-Errormessage'));
            throw new WebfleetApiException($action, $errorCode, '' !== $message ? $message : 'Unknown API error', $status);
        }

        $plainError = $this->plainTextError($body);
        if (null !== $plainError) {
            throw new WebfleetApiException($action, $plainError[0], $plainError[1], $status);
        }

        if ($status < 200 || $status >= 300) {
            throw new HttpException($action, $status);
        }

        if ('' === $body) {
            return new RawResponse($action, $status, $this->normalizeHeaders($response->getHeaders()), []);
        }

        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new UnexpectedResponseException($action, 'response body is not valid JSON');
        }

        if (is_array($decoded) && isset($decoded['errorCode'])) {
            $code = is_scalar($decoded['errorCode']) ? (string) $decoded['errorCode'] : 'unknown';
            $message = isset($decoded['errorMsg']) && is_scalar($decoded['errorMsg']) ? (string) $decoded['errorMsg'] : 'Unknown API error';
            throw new WebfleetApiException($action, $code, $message, $status);
        }

        if (!is_array($decoded) || !array_is_list($decoded)) {
            throw new UnexpectedResponseException($action, 'expected a JSON array');
        }

        $rows = [];
        foreach ($decoded as $row) {
            if (!is_array($row) || array_is_list($row)) {
                throw new UnexpectedResponseException($action, 'expected each JSON result to be an object');
            }
            /** @var array<string, mixed> $row */
            $rows[] = $row;
        }

        return new RawResponse($action, $status, $this->normalizeHeaders($response->getHeaders()), $rows);
    }

    /**
     * @param array<string, null|bool|float|int|string|Stringable|list<bool|float|int|string|Stringable>> $parameters
     */
    private function encodeQuery(array $parameters): string
    {
        $parts = [];
        foreach ($parameters as $name => $value) {
            if (null === $value) {
                continue;
            }

            $values = is_array($value) ? $value : [$value];
            foreach ($values as $item) {
                $parts[] = rawurlencode($name) . '=' . rawurlencode($this->stringify($item));
            }
        }

        return implode('&', $parts);
    }

    private function stringify(bool|float|int|string|Stringable $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    }

    /** @return null|array{string, string} */
    private function plainTextError(string $body): ?array
    {
        if (1 !== preg_match('/^\s*(\d+)\s*,\s*(.+)\s*$/sD', $body, $matches)) {
            return null;
        }

        return [$matches[1], $matches[2]];
    }

    /**
     * @param array<array<string>> $headers
     * @return array<string, list<string>>
     */
    private function normalizeHeaders(array $headers): array
    {
        $normalized = [];
        foreach ($headers as $name => $values) {
            $normalized[(string) $name] = array_values($values);
        }

        return $normalized;
    }
}

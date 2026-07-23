<?php

declare(strict_types=1);

namespace Webfleet\Connect\Tests\Support;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

final class QueueHttpClient implements ClientInterface
{
    /** @var list<ResponseInterface> */
    private array $responses;

    /** @var list<RequestInterface> */
    public array $requests = [];

    public function __construct(ResponseInterface ...$responses)
    {
        $this->responses = array_values($responses);
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;
        $response = array_shift($this->responses);

        if (null === $response) {
            throw new RuntimeException('No queued response.');
        }

        return $response;
    }
}

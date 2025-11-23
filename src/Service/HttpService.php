<?php

declare(strict_types=1);

namespace Bilo\Service;

use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use JsonSerializable;

class HttpService
{
    public function __construct(
        private readonly Client $httpClient,
    ) {
    }

    public function post(string $url, array $payload): bool
    {
        try {
            $response = $this->httpClient->post($url, [
                RequestOptions::JSON => $payload,
                RequestOptions::VERIFY => false,
                RequestOptions::HEADERS => [
                    'Content-Type' => 'application/json',
                ]
            ]);

            if ($response->getStatusCode() === StatusCodeInterface::STATUS_OK) {
                return true;
            }

        } catch (GuzzleException $e) {
            //log
        }

        return false;
    }

}

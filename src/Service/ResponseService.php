<?php

declare(strict_types=1);

namespace Bilo\Service;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;

class ResponseService
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    public function errorResponse(Response $response, int $code, string $message): Response {
        // Log the error
        $this->logger->error('Error response generated', [
            'code' => $code,
            'message' => $message,
        ]);

        /** @noinspection JsonEncodingApiUsageInspection */
        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => $message,
        ]));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($code);

    }

    public function success(Response $response, int $code, array|string $message): Response {
        /** @noinspection JsonEncodingApiUsageInspection */
        $response->getBody()->write(json_encode(is_array($message) ? $message : [
            'success' => true,
            'message' => $message,
        ]));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($code);

    }


}
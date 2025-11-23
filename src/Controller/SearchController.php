<?php

declare(strict_types=1);

namespace Bilo\Controller;

use Bilo\Database\Exception\MySqlQueryException;
use Bilo\Enum\Queue;
use Bilo\Enum\SearchRequestStatus;
use Bilo\Enum\SearchType;
use Bilo\Service\Exception\NotFoundException;
use Bilo\Service\Exception\ServiceException;
use Bilo\Service\QueueService;
use Bilo\Service\ResponseService;
use Bilo\Service\SearchService;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class SearchController
{
    public function __construct(
        private readonly QueueService $queueService,
        private readonly ResponseService $responseService,
        private readonly SearchService $searchService,
    )
    {
    }

    public function searchRecords(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody();
        return $this->listRecords($body, $response, SearchType::LIST, Queue::search_request);
    }

    public function aggregateRecords(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody();
        return $this->listRecords($body, $response, SearchType::AGGREGATE, Queue::search_aggregate);
    }

    private function listRecords(array $body, Response $response, SearchType $searchType, Queue $queue): Response
    {

        $startDate = $body['startDate'] ?? null;
        $endDate = $body['endDate'] ?? null;
        $recordType = $body['recordType'] ?? null;

        $positive = null;
        if ($recordType === 'positive') {
            $positive = true;
        }elseif ($recordType === 'negative') {
            $positive = false;
        }

        try {
            $searchId = $this->searchService->insert($searchType);
            $this->queueService->publishMessage($queue, 0, $searchId, $startDate, $endDate, $positive);
        } catch (ServiceException $e) {
            return $this->responseService->errorResponse($response,StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR, $e->getMessage());
        }

        return $this->responseService->success($response,StatusCodeInterface::STATUS_ACCEPTED, [
            'id' => $searchId,
            'status' => SearchRequestStatus::QUEUED->name,
        ]);

    }

    public function searchStatus(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'];

        try {
            [$searchType, $status] = $this->searchService->getStatus($id);
        } catch (MySqlQueryException $e) {
            return $this->responseService->errorResponse($response,StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR, $e->getMessage());
        } catch (NotFoundException $e) {
            return $this->responseService->errorResponse($response,StatusCodeInterface::STATUS_NOT_FOUND, $e->getMessage());
        }

        $data = [
            'id' => $id,
            'status' => $status->name,
        ];

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'];
        $url = $scheme . '://' . $host;

        if ($status === SearchRequestStatus::DONE) {
            if ($searchType === SearchType::LIST) {
                $data['url'] = $url.'/reports/'.$id.'.csv';
            }
            if ($searchType === SearchType::AGGREGATE) {
                $data['url'] = $url.'/reports/'.$id.'.json';
            }
        }

        return $this->responseService->success($response,StatusCodeInterface::STATUS_OK, $data);
    }
}
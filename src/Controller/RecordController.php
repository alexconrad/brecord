<?php

declare(strict_types=1);

namespace Bilo\Controller;

use Bilo\Enum\EnvVar;
use Bilo\Enum\Queue;
use Bilo\Service\ConfigService;
use Bilo\Service\Exception\EntryAlreadyExists;
use Bilo\Service\Exception\ServiceException;
use Bilo\Service\EntityBuilder;
use Bilo\Service\QueueService;
use Bilo\Service\RecordService;
use Bilo\Service\ResponseService;
use Fig\Http\Message\StatusCodeInterface;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class RecordController
{
    public function __construct(
        private readonly ConfigService $configService,
        private readonly RecordService $recordService,
        private readonly ResponseService $responseService,
        private readonly QueueService $queueService,
        private readonly EntityBuilder $queueMessageBuilder,
    ) {
    }

    public function createRecord(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody();

        try {
            $recordEntity = $this->queueMessageBuilder->buildRecord($body);
            $this->recordService->createRecord($recordEntity);

            $positiveThreshold = $this->configService->getRequired(EnvVar::POSITIVE_THRESHOLD);
            $negativeThreshold = $this->configService->getRequired(EnvVar::NEGATIVE_THRESHOLD);

            if ($recordEntity->positive && bccomp((string)$recordEntity->value, $positiveThreshold, ConfigService::DECIMAL_PLACES) >= 0) {
                $this->queueService->publishMessage(Queue::alert,0, $positiveThreshold, $recordEntity);
            } elseif (!$recordEntity->positive && bccomp((string)$recordEntity->value, $positiveThreshold, ConfigService::DECIMAL_PLACES) >= 0) {
                $this->queueService->publishMessage(Queue::alert,0, $negativeThreshold, $recordEntity);
            }

            $this->queueService->publishMessage(Queue::notification,0, $recordEntity);


        } catch (InvalidArgumentException) {
            return $this->responseService->errorResponse(
                $response,
                StatusCodeInterface::STATUS_BAD_REQUEST,
                'Invalid date format. Use ISO 8601 format.'
            );
        } catch (EntryAlreadyExists) {
            return $this->responseService->errorResponse(
                $response,
                StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
                'Record already exists'
            );
        } catch (ServiceException) {
            return $this->responseService->errorResponse(
                $response,
                StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR,
                'Server error.'
            );
        }


        return $this->responseService->success(
            $response,
            StatusCodeInterface::STATUS_CREATED,
            'Record created successfully'
        );
    }


}

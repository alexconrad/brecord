<?php

declare(strict_types=1);

namespace Bilo\Service;

use Bilo\DAO\SearchRequestsDao;
use Bilo\Database\Exception\MySqlQueryException;
use Bilo\Enum\SearchRequestStatus;
use Bilo\Enum\SearchType;
use Bilo\Service\Exception\NotFoundException;
use Bilo\Service\Exception\ServiceException;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

class SearchService
{
    public function __construct(
        private readonly SearchRequestsDao $searchRequestsDao,
        private readonly LoggerInterface $logger,
    )
    {
    }

    public function insert(SearchType $searchType): string
    {
        try {
            $uuid = Uuid::uuid6()->toString();
            $this->searchRequestsDao->insertRequest($uuid, $searchType->value, SearchRequestStatus::QUEUED->value);
            return $uuid;
        } catch (MySqlQueryException $e) {
            $this->logger->error($e->getMessage());
            throw new ServiceException('Error inserting search request', 0, $e);
        }
    }

    public function changeStatus(string $uuid, SearchRequestStatus $status)
    {
        $this->searchRequestsDao->updateStatus($uuid, $status);
    }

    /**
     * @param  string  $uuid
     * @return array{SearchType, SearchRequestStatus}
     * @throws MySqlQueryException
     * @throws NotFoundException
     */
    public function getStatus(string $uuid): array
    {
        if (([$searchType, $status] = $this->searchRequestsDao->getStatus($uuid)) === null) {
            throw new NotFoundException('Search request not found');
        }

        return [
            SearchType::from((int)$searchType),
            SearchRequestStatus::from((int)$status)
        ];
    }



}
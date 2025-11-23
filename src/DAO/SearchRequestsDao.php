<?php

declare(strict_types=1);

namespace Bilo\DAO;

use Bilo\Database\Connection;
use Bilo\Database\Exception\MySqlQueryException;
use Bilo\Enum\SearchRequestStatus;

class SearchRequestsDao
{
    public function __construct(
        private readonly Connection $connection
    )
    {
    }

    /**
     * @throws MySqlQueryException
     */
    public function insertRequest(string $uuid, int $searchType, int $status)
    {
        $sql = "INSERT INTO search_requests (id_search_request, search_type, status) VALUES (?, ?, ?)";
        $this->connection->execute($sql, [$uuid, $searchType, $status]);
    }

    public function updateStatus(string $uuid, SearchRequestStatus $status)
    {
        $sql = "UPDATE search_requests SET status = ? WHERE id_search_request = ?";
        $this->connection->execute($sql, [$status->value, $uuid]);
    }

    /**
     * @throws MySqlQueryException
     */
    public function getStatus(string $uuid): ?array
    {
        $sql = "SELECT search_type, status FROM search_requests WHERE id_search_request = ?";
        return array_values($this->connection->getRow($sql, [$uuid]));
    }


}
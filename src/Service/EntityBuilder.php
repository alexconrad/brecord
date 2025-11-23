<?php

declare(strict_types=1);

namespace Bilo\Service;

use Bilo\Entity\Record;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

class EntityBuilder
{
    public const string MYSQL_DATE_FORMAT = '!Y-m-d H:i:s';
    public const string NOW = 'now';

    public function buildRecord(array $data): Record
    {
        return new Record(
            $data['recordId'],
            $this->buildDateTime($data['time']),
            $data['sourceId'],
            $data['destinationId'],
            ($data['type'] === 'positive'),
            $data['value'],
            $data['unit'],
            $data['reference'],
        );
    }

    public function buildDateTime(string $dateTime, ?string $format = null): DateTimeImmutable
    {
        if ($format === null) {
            $format = '!'.DateTimeInterface::ATOM;
        }
        if ($dateTime === self::NOW) {
            return new DateTimeImmutable();
        }

        $dt = DateTimeImmutable::createFromFormat($format, $dateTime);

        $errors = DateTimeImmutable::getLastErrors();
        if ($errors !== false) {
            throw new InvalidArgumentException("Invalid date :[".$dateTime.']['.$format.']'.print_r($errors, true));
        }
        return $dt;
    }

}
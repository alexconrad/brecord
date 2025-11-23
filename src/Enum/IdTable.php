<?php

declare(strict_types=1);

namespace Bilo\Enum;

enum IdTable: string
{
    case destinations = 'id_destination';
    case records = 'id_record';
    case refs = 'id_reference';
    case sources = 'id_source';
    case units = 'id_unit';

    public function getField(): string
    {
        if ($this === self::destinations) {
            return 'destinationId';
        }
        if ($this === self::records) {
            return 'recordId';
        }
        if ($this === self::refs) {
            return 'ref';
        }
        if ($this === self::sources) {
            return 'sourceId';
        }
        if ($this === self::units) {
            return 'unit';
        }

        throw new \RuntimeException('Enum IdTable not configured correctly');
    }

}
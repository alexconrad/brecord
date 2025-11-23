<?php

namespace Bilo\Service;

use Bilo\DAO\SettingsDao;
use Bilo\Database\Exception\NotFoundException;
use Bilo\Enum\Setting;

class SettingsService
{
    public function __construct(private readonly SettingsDao $dao)
    {
    }

    public function getLastPilotLogRead(): ?string
    {
        try {
            return $this->dao->getSetting(Setting::LAST_PILOT_LOG_LINE_READ->name);
        } catch (NotFoundException) {
            return null;
        }
    }

}
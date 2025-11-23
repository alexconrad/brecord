<?php

declare(strict_types=1);

namespace Bilo\Service;

use Bilo\Enum\EnvVar;
use Bilo\Service\Exception\ConfigurationNotFound;

class ConfigService
{
    public const int DECIMAL_PLACES = 2;

    private function get(EnvVar $var, ?string $default = null): ?string
    {
        return $_ENV[$var->value] ?? $default;
    }

    /**
     * @throws ConfigurationNotFound
     */
    public function getRequired(EnvVar $var): string
    {
        $value = $this->get($var);
        
        if ($value === null) {
            throw new ConfigurationNotFound("Required environment variable {$var->value} is not set");
        }
        
        return $value;
    }
}

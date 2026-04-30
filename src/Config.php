<?php

namespace Appsignal;

use Throwable;

class Config
{
    public const CONFIG_PATH = '/config/appsignal.php';

    /**
     * @param string[]|null $disablePatches
     */
    public function __construct(
        public ?bool $active = false,
        public ?string $name = null,
        public ?string $environment = null,
        public ?string $pushApiKey = null,
        public ?string $collectorEndpoint = null,
        public ?array $disablePatches = [],
    ) {}

    public function isValid(): bool
    {
        return !empty($this->pushApiKey)
            && !empty($this->collectorEndpoint)
            && !empty($this->name)
            && !empty($this->environment);
    }

    /**
     * @return string[]
     */
    public function getMissingFields(): array
    {
        $missing = [];

        if (empty($this->pushApiKey)) {
            $missing[] = 'push_api_key';
        }
        if (empty($this->collectorEndpoint)) {
            $missing[] = 'collector_endpoint';
        }
        if (empty($this->name)) {
            $missing[] = 'name';
        }
        if (empty($this->environment)) {
            $missing[] = 'environment';
        }

        return $missing;
    }

    public function applySystemEnvVariables(): self
    {
        if (isset($_ENV['APPSIGNAL_ACTIVE'])) {
            $this->active = filter_var($_ENV['APPSIGNAL_ACTIVE'], FILTER_VALIDATE_BOOL);
        }
        if (isset($_ENV['APPSIGNAL_APP_NAME'])) {
            $this->name = $_ENV['APPSIGNAL_APP_NAME'];
        } elseif (isset($_ENV['APP_NAME'])) {
            $this->name = $_ENV['APP_NAME'];
        }
        if (isset($_ENV['APPSIGNAL_APP_ENV'])) {
            $this->environment = $_ENV['APPSIGNAL_APP_ENV'];
        } elseif (isset($_ENV['APP_ENV'])) {
            $this->environment = $_ENV['APP_ENV'];
        }
        if (isset($_ENV['APPSIGNAL_PUSH_API_KEY'])) {
            $this->pushApiKey = $_ENV['APPSIGNAL_PUSH_API_KEY'];
        }
        if (isset($_ENV['APPSIGNAL_COLLECTOR_ENDPOINT'])) {
            $this->collectorEndpoint = $_ENV['APPSIGNAL_COLLECTOR_ENDPOINT'];
        }
        if (isset($_ENV['APPSIGNAL_DISABLE_PATCHES'])) {
            $this->disablePatches = explode(",", $_ENV['APPSIGNAL_DISABLE_PATCHES']);
        }
        return $this;
    }

    /**
     * Load config from a file that returns an array
     */
    public static function tryFromFile(string $path): self
    {
        if (!file_exists($path)) {
            return new self();
        }

        try {
            $values = require $path;

            if (!is_array($values)) {
                return new self();
            }
            $disabledPatches = $values['disable_patches'] ?? null;

            return new self(
                active: $values['active'] ?? null,
                name: $values['name'] ?? null,
                environment: $values['environment'] ?? null,
                pushApiKey: $values['push_api_key'] ?? null,
                collectorEndpoint: $values['collector_endpoint'] ?? null,
                disablePatches: is_array($disabledPatches) ? $disabledPatches : [],
            );
        } catch (Throwable $e) {
            return new self();
        }
    }
}

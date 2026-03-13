<?php

namespace AppSignal;

use Throwable;

class Config
{
    public const CONFIG_PATH = '/config/appsignal.php';

    /**
     * @param string[]|null $disablePatches
     */
    public function __construct(
        public ?string $name = null,
        public ?string $environment = null,
        public ?string $pushApiKey = null,
        public ?string $collectorUrl = null,
        public ?array $disablePatches = null,
    ) {
        $this->name ??= $_ENV['APPSIGNAL_APP_NAME'] ?? $_ENV['APP_NAME'] ?? null;
        $this->environment ??= $_ENV['APP_ENV'] ?? null;
        $this->pushApiKey ??= $_ENV['APPSIGNAL_PUSH_API_KEY'] ?? null;
        $this->collectorUrl ??= $_ENV['APPSIGNAL_COLLECTOR_URL'] ?? null;
        $disablePatchesFromEnv = $_ENV['APPSIGNAL_DISABLE_PATCHES'] ?? null;
        $this->disablePatches ??= $disablePatchesFromEnv ? explode(",", $disablePatchesFromEnv) : [];
    }

    public function isValid(): bool
    {
        return !empty($this->pushApiKey)
            && !empty($this->collectorUrl)
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
        if (empty($this->collectorUrl)) {
            $missing[] = 'collector_url';
        }
        if (empty($this->name)) {
            $missing[] = 'name';
        }
        if (empty($this->environment)) {
            $missing[] = 'environment';
        }

        return $missing;
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
                name: $values['name'] ?? null,
                environment: $values['environment'] ?? null,
                pushApiKey: $values['push_api_key'] ?? null,
                collectorUrl: $values['collector_url'] ?? null,
                disablePatches: is_array($disabledPatches) ? $disabledPatches : null,
            );
        } catch (Throwable $e) {
            return new self();
        }
    }
}

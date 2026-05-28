<?php

namespace Appsignal;

use Throwable;

class Config
{
    public const CONFIG_PATH = '/config/appsignal.php';

    /**
     * @param string[] $disablePatches
     * @param string[] $filterAttributes
     * @param string[] $filterFunctionParameters
     * @param string[] $filterRequestQueryParameters
     * @param string[] $filterRequestPayload
     * @param string[] $filterRequestSessionData
     * @param string[] $ignoreActions
     * @param string[] $ignoreErrors
     * @param string[] $ignoreNamespaces
     * @param string[] $ignoreLogs
     * @param string[]|null $requestHeaders
     * @param string[]|null $responseHeaders
     */
    public function __construct(
        public ?bool $active = false,
        public ?string $name = null,
        public ?string $environment = null,
        public ?string $pushApiKey = null,
        public ?string $collectorEndpoint = null,
        public ?string $appPath = null,
        public ?array $disablePatches = [],
        public ?array $filterAttributes = [],
        public ?array $filterFunctionParameters = [],
        public ?array $filterRequestQueryParameters = [],
        public ?array $filterRequestPayload = [],
        public ?array $filterRequestSessionData = [],
        public ?array $ignoreActions = [],
        public ?array $ignoreErrors = [],
        public ?array $ignoreNamespaces = [],
        public ?array $ignoreLogs = [],
        public ?array $requestHeaders = null,
        public ?array $responseHeaders = null,
        public ?bool $sendFunctionParameters = null,
        public ?bool $sendRequestQueryParameters = null,
        public ?bool $sendRequestPayload = null,
        public ?bool $sendRequestSessionData = null,
        public ?string $revision = null,
        public ?string $hostname = null,
        public ?string $serviceName = null,
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

    /**
     * @param array<string, mixed> $overrides
     */
    public function withOverrides(array $overrides): self
    {
        $clone = clone $this;
        foreach ($overrides as $key => $value) {
            if ($value !== null && property_exists($clone, $key)) {
                $clone->$key = $value;
            }
        }
        return $clone;
    }

    public static function load(string $configPath): self
    {
        return self::loadFromEnv()->applyConfigFile($configPath);
    }

    public static function loadFromEnv(): self
    {
        return new self(
            active: filter_var($_ENV['APPSIGNAL_ACTIVE'] ?? false, FILTER_VALIDATE_BOOL),
            name: $_ENV['APPSIGNAL_APP_NAME'] ?? null,
            environment: $_ENV['APPSIGNAL_APP_ENV'] ?? null,
            pushApiKey: $_ENV['APPSIGNAL_PUSH_API_KEY'] ?? null,
            collectorEndpoint: $_ENV['APPSIGNAL_COLLECTOR_ENDPOINT'] ?? null,
            appPath: $_ENV['APPSIGNAL_APP_PATH'] ?? null,
            disablePatches: self::splitCsv($_ENV['APPSIGNAL_DISABLE_PATCHES'] ?? ''),
            filterAttributes: self::splitCsv($_ENV['APPSIGNAL_FILTER_ATTRIBUTES'] ?? ''),
            filterFunctionParameters: self::splitCsv($_ENV['APPSIGNAL_FILTER_FUNCTION_PARAMETERS'] ?? ''),
            filterRequestQueryParameters: self::splitCsv($_ENV['APPSIGNAL_FILTER_REQUEST_QUERY_PARAMETERS'] ?? ''),
            filterRequestPayload: self::splitCsv($_ENV['APPSIGNAL_FILTER_REQUEST_PAYLOAD'] ?? ''),
            filterRequestSessionData: self::splitCsv($_ENV['APPSIGNAL_FILTER_REQUEST_SESSION_DATA'] ?? ''),
            ignoreActions: self::splitCsv($_ENV['APPSIGNAL_IGNORE_ACTIONS'] ?? ''),
            ignoreErrors: self::splitCsv($_ENV['APPSIGNAL_IGNORE_ERRORS'] ?? ''),
            ignoreNamespaces: self::splitCsv($_ENV['APPSIGNAL_IGNORE_NAMESPACES'] ?? ''),
            ignoreLogs: self::splitCsv($_ENV['APPSIGNAL_IGNORE_LOGS'] ?? ''),
            requestHeaders: self::splitCsv($_ENV['APPSIGNAL_REQUEST_HEADERS'] ?? null, fallback: null),
            responseHeaders: self::splitCsv($_ENV['APPSIGNAL_RESPONSE_HEADERS'] ?? null, fallback: null),
            sendFunctionParameters: self::ensureBoolOrNull($_ENV['APPSIGNAL_SEND_FUNCTION_PARAMETERS'] ?? null),
            sendRequestQueryParameters: self::ensureBoolOrNull($_ENV['APPSIGNAL_SEND_REQUEST_QUERY_PARAMETERS'] ?? null),
            sendRequestPayload: self::ensureBoolOrNull($_ENV['APPSIGNAL_SEND_REQUEST_PAYLOAD'] ?? null),
            sendRequestSessionData: self::ensureBoolOrNull($_ENV['APPSIGNAL_SEND_REQUEST_SESSION_DATA'] ?? null),
            revision: $_ENV['APPSIGNAL_REVISION'] ?? null,
            hostname: $_ENV['APPSIGNAL_HOSTNAME'] ?? null,
            serviceName: $_ENV['APPSIGNAL_SERVICE_NAME'] ?? null,
        );
    }

    /**
     * Load config from a file that returns an array
     * and apply to the current instance
     */
    public function applyConfigFile(string $path): self
    {
        if (!file_exists($path)) {
            return $this;
        }

        try {
            $values = require $path;

            if (!is_array($values)) {
                return $this;
            }

            $this->active = $values['active'] ?? $this->active;
            $this->name = $values['name'] ?? $this->name;
            $this->environment = $values['environment'] ?? $this->environment;
            $this->pushApiKey = $values['push_api_key'] ?? $this->pushApiKey;
            $this->collectorEndpoint = $values['collector_endpoint'] ?? $this->collectorEndpoint;
            $this->appPath = $values['app_path'] ?? $this->appPath;
            $this->disablePatches = self::ensureStringArray($values['disable_patches'] ?? null, null) ?? $this->disablePatches;
            $this->filterAttributes = self::ensureStringArray($values['filter_attributes'] ?? null, null) ?? $this->filterAttributes;
            $this->filterFunctionParameters = self::ensureStringArray($values['filter_function_parameters'] ?? null, null) ?? $this->filterFunctionParameters;
            $this->filterRequestQueryParameters = self::ensureStringArray($values['filter_request_query_parameters'] ?? null, null) ?? $this->filterRequestQueryParameters;
            $this->filterRequestPayload = self::ensureStringArray($values['filter_request_payload'] ?? null, null) ?? $this->filterRequestPayload;
            $this->filterRequestSessionData = self::ensureStringArray($values['filter_request_session_data'] ?? null, null) ?? $this->filterRequestSessionData;
            $this->ignoreActions = self::ensureStringArray($values['ignore_actions'] ?? null, null) ?? $this->ignoreActions;
            $this->ignoreErrors = self::ensureStringArray($values['ignore_errors'] ?? null, null) ?? $this->ignoreErrors;
            $this->ignoreNamespaces = self::ensureStringArray($values['ignore_namespaces'] ?? null, null) ?? $this->ignoreNamespaces;
            $this->ignoreLogs = self::ensureStringArray($values['ignore_logs'] ?? null, null) ?? $this->ignoreLogs;
            $this->requestHeaders = self::ensureStringArray($values['request_headers'] ?? null, null) ?? $this->requestHeaders;
            $this->responseHeaders = self::ensureStringArray($values['response_headers'] ?? null, null) ?? $this->responseHeaders;
            $this->sendFunctionParameters = self::ensureBoolOrNull($values['send_function_parameters'] ?? null) ?? $this->sendFunctionParameters;
            $this->sendRequestQueryParameters = self::ensureBoolOrNull($values['send_request_query_parameters'] ?? null) ?? $this->sendRequestQueryParameters;
            $this->sendRequestPayload = self::ensureBoolOrNull($values['send_request_payload'] ?? null) ?? $this->sendRequestPayload;
            $this->sendRequestSessionData = self::ensureBoolOrNull($values['send_request_session_data'] ?? null) ?? $this->sendRequestSessionData;
            $this->revision = $values['revision'] ?? $this->revision;
            $this->hostname = $values['hostname'] ?? $this->hostname;
            $this->serviceName = $values['service_name'] ?? $this->serviceName;

            return $this;
        } catch (Throwable $e) {
            return $this;
        }
    }

    /**
     * @return string[]
     */
    private static function splitCsv(?string $value, mixed $fallback = null): ?array
    {
        if (is_null($value)) {
            return $fallback;
        }

        if ($value === '') {
            return [];
        }
        return array_map('trim', explode(',', $value));
    }

    protected static function ensureBoolOrNull(mixed $value): ?bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
    }

    /**
     * @param mixed $items
     * @param string[]|null $fallback
     *
     * @return string[]|null
     */
    protected static function ensureStringArray($items, $fallback): ?array
    {
        if (!is_array($items)) {
            return $fallback;
        }

        $allStrings = true;
        foreach ($items as $value) {
            if (!is_string($value)) {
                $allStrings = false;
            }
        }

        if ($allStrings) {
            return $items;
        }

        return $fallback;
    }
}

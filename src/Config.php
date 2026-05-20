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
        public ?array $requestHeaders = null,
        public ?array $responseHeaders = null,
        public ?bool $sendFunctionParameters = null,
        public ?bool $sendRequestQueryParameters = null,
        public ?bool $sendRequestPayload = null,
        public ?bool $sendRequestSessionData = null,
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

    public static function load(string $configPath): self
    {
        return self::tryFromFile($configPath)->applyEnvVariables();
    }

    public function applyEnvVariables(): self
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
        if (isset($_ENV['APPSIGNAL_APP_PATH'])) {
            $this->appPath = $_ENV['APPSIGNAL_APP_PATH'];
        }
        if (isset($_ENV['APPSIGNAL_DISABLE_PATCHES'])) {
            $this->disablePatches = self::splitCsv($_ENV['APPSIGNAL_DISABLE_PATCHES']);
        }
        if (isset($_ENV['APPSIGNAL_FILTER_ATTRIBUTES'])) {
            $this->filterAttributes = self::splitCsv($_ENV['APPSIGNAL_FILTER_ATTRIBUTES']);
        }
        if (isset($_ENV['APPSIGNAL_FILTER_FUNCTION_PARAMETERS'])) {
            $this->filterFunctionParameters = self::splitCsv($_ENV['APPSIGNAL_FILTER_FUNCTION_PARAMETERS']);
        }
        if (isset($_ENV['APPSIGNAL_FILTER_REQUEST_QUERY_PARAMETERS'])) {
            $this->filterRequestQueryParameters = self::splitCsv($_ENV['APPSIGNAL_FILTER_REQUEST_QUERY_PARAMETERS']);
        }
        if (isset($_ENV['APPSIGNAL_FILTER_REQUEST_PAYLOAD'])) {
            $this->filterRequestPayload = self::splitCsv($_ENV['APPSIGNAL_FILTER_REQUEST_PAYLOAD']);
        }
        if (isset($_ENV['APPSIGNAL_FILTER_REQUEST_SESSION_DATA'])) {
            $this->filterRequestSessionData = self::splitCsv($_ENV['APPSIGNAL_FILTER_REQUEST_SESSION_DATA']);
        }
        if (isset($_ENV['APPSIGNAL_IGNORE_ACTIONS'])) {
            $this->ignoreActions = self::splitCsv($_ENV['APPSIGNAL_IGNORE_ACTIONS']);
        }
        if (isset($_ENV['APPSIGNAL_IGNORE_ERRORS'])) {
            $this->ignoreErrors = self::splitCsv($_ENV['APPSIGNAL_IGNORE_ERRORS']);
        }
        if (isset($_ENV['APPSIGNAL_IGNORE_NAMESPACES'])) {
            $this->ignoreNamespaces = self::splitCsv($_ENV['APPSIGNAL_IGNORE_NAMESPACES']);
        }
        if (isset($_ENV['APPSIGNAL_REQUEST_HEADERS'])) {
            $this->requestHeaders = self::splitCsv($_ENV['APPSIGNAL_REQUEST_HEADERS']);
        }
        if (isset($_ENV['APPSIGNAL_RESPONSE_HEADERS'])) {
            $this->responseHeaders = self::splitCsv($_ENV['APPSIGNAL_RESPONSE_HEADERS']);
        }
        if (isset($_ENV['APPSIGNAL_SEND_FUNCTION_PARAMETERS'])) {
            $this->sendFunctionParameters = filter_var($_ENV['APPSIGNAL_SEND_FUNCTION_PARAMETERS'], FILTER_VALIDATE_BOOL);
        }
        if (isset($_ENV['APPSIGNAL_SEND_REQUEST_QUERY_PARAMETERS'])) {
            $this->sendRequestQueryParameters = filter_var($_ENV['APPSIGNAL_SEND_REQUEST_QUERY_PARAMETERS'], FILTER_VALIDATE_BOOL);
        }
        if (isset($_ENV['APPSIGNAL_SEND_REQUEST_PAYLOAD'])) {
            $this->sendRequestPayload = filter_var($_ENV['APPSIGNAL_SEND_REQUEST_PAYLOAD'], FILTER_VALIDATE_BOOL);
        }
        if (isset($_ENV['APPSIGNAL_SEND_REQUEST_SESSION_DATA'])) {
            $this->sendRequestSessionData = filter_var($_ENV['APPSIGNAL_SEND_REQUEST_SESSION_DATA'], FILTER_VALIDATE_BOOL);
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
                appPath: $values['app_path'] ?? null,
                disablePatches: is_array($disabledPatches) ? $disabledPatches : [],
                filterAttributes: self::ensureStringArray($values['filter_attributes'] ?? [], []),
                filterFunctionParameters: self::ensureStringArray($values['filter_function_parameters'] ?? [], []),
                filterRequestQueryParameters: self::ensureStringArray($values['filter_request_query_parameters'] ?? [], []),
                filterRequestPayload: self::ensureStringArray($values['filter_request_payload'] ?? [], []),
                filterRequestSessionData: self::ensureStringArray($values['filter_request_session_data'] ?? [], []),
                ignoreActions: self::ensureStringArray($values['ignore_actions'] ?? [], []),
                ignoreErrors: self::ensureStringArray($values['ignore_errors'] ?? [], []),
                ignoreNamespaces: self::ensureStringArray($values['ignore_namespaces'] ?? [], []),
                requestHeaders: array_key_exists('request_headers', $values)
                    ? self::ensureStringArray($values['request_headers'], null)
                    : null,
                responseHeaders: array_key_exists('response_headers', $values)
                    ? self::ensureStringArray($values['response_headers'], null)
                    : null,
                sendFunctionParameters: array_key_exists('send_function_parameters', $values)
                    ? (bool) $values['send_function_parameters']
                    : null,
                sendRequestQueryParameters: array_key_exists('send_request_query_parameters', $values)
                    ? (bool) $values['send_request_query_parameters']
                    : null,
                sendRequestPayload: array_key_exists('send_request_payload', $values)
                    ? (bool) $values['send_request_payload']
                    : null,
                sendRequestSessionData: array_key_exists('send_request_session_data', $values)
                    ? (bool) $values['send_request_session_data']
                    : null,
            );
        } catch (Throwable $e) {
            return new self();
        }
    }

    /**
     * @return array<string, string[]|false>
     */
    public function getOtelResourceAttributes(): array
    {
        $config = [];

        if (count($this->filterAttributes) > 0) {
            $config['appsignal.config.filter_attributes'] = $this->filterAttributes;
        }
        if (count($this->filterFunctionParameters) > 0) {
            $config['appsignal.config.filter_function_parameters'] = $this->filterFunctionParameters;
        }
        if (count($this->filterRequestQueryParameters) > 0) {
            $config['appsignal.config.filter_request_query_parameters'] = $this->filterRequestQueryParameters;
        }
        if (count($this->filterRequestPayload) > 0) {
            $config['appsignal.config.filter_request_payload'] = $this->filterRequestPayload;
        }
        if (count($this->filterRequestSessionData) > 0) {
            $config['appsignal.config.filter_request_session_data'] = $this->filterRequestSessionData;
        }
        if (count($this->ignoreActions) > 0) {
            $config['appsignal.config.ignore_actions'] = $this->ignoreActions;
        }
        if (count($this->ignoreErrors) > 0) {
            $config['appsignal.config.ignore_errors'] = $this->ignoreErrors;
        }
        if (count($this->ignoreNamespaces) > 0) {
            $config['appsignal.config.ignore_namespaces'] = $this->ignoreNamespaces;
        }
        if ($this->requestHeaders !== null) {
            $config['appsignal.config.request_headers'] = $this->requestHeaders;
        }
        if ($this->responseHeaders !== null) {
            $config['appsignal.config.response_headers'] = $this->responseHeaders;
        }
        if ($this->sendFunctionParameters === false) {
            $config['appsignal.config.send_function_parameters'] = false;
        }
        if ($this->sendRequestQueryParameters === false) {
            $config['appsignal.config.send_request_query_parameters'] = false;
        }
        if ($this->sendRequestPayload === false) {
            $config['appsignal.config.send_request_payload'] = false;
        }
        if ($this->sendRequestSessionData === false) {
            $config['appsignal.config.send_request_session_data'] = false;
        }

        return $config;
    }

    /**
     * @return string[]
     */
    private static function splitCsv(string $value): array
    {
        if ($value === '') {
            return [];
        }
        return array_map('trim', explode(',', $value));
    }

    /**
     * @param mixed $items
     * @param string[]|null $fallback
     *
     * @return string[]|null
     */
    protected static function ensureStringArray($items, $fallback): ?array
    {
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

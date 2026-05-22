<?php

namespace Appsignal;

class OpenTelemetryResourceAttributes
{
    public function __construct(protected Config $config) {}

    /**
     * @return array<string, string|string[]|false|null>
     */
    public function toArray(): array
    {
        $attributes = [
            'appsignal.config.name'         => $this->config->name,
            'appsignal.config.environment'  => $this->config->environment,
            'appsignal.config.push_api_key' => $this->config->pushApiKey,
        ];

        if (count($this->config->filterAttributes) > 0) {
            $attributes['appsignal.config.filter_attributes'] = $this->config->filterAttributes;
        }
        if (count($this->config->filterFunctionParameters) > 0) {
            $attributes['appsignal.config.filter_function_parameters'] = $this->config->filterFunctionParameters;
        }
        if (count($this->config->filterRequestQueryParameters) > 0) {
            $attributes['appsignal.config.filter_request_query_parameters'] = $this->config->filterRequestQueryParameters;
        }
        if (count($this->config->filterRequestPayload) > 0) {
            $attributes['appsignal.config.filter_request_payload'] = $this->config->filterRequestPayload;
        }
        if (count($this->config->filterRequestSessionData) > 0) {
            $attributes['appsignal.config.filter_request_session_data'] = $this->config->filterRequestSessionData;
        }
        if (count($this->config->ignoreActions) > 0) {
            $attributes['appsignal.config.ignore_actions'] = $this->config->ignoreActions;
        }
        if (count($this->config->ignoreErrors) > 0) {
            $attributes['appsignal.config.ignore_errors'] = $this->config->ignoreErrors;
        }
        if (count($this->config->ignoreNamespaces) > 0) {
            $attributes['appsignal.config.ignore_namespaces'] = $this->config->ignoreNamespaces;
        }
        if (count($this->config->ignoreLogs) > 0) {
            $attributes['appsignal.config.ignore_logs'] = $this->config->ignoreLogs;
        }
        if ($this->config->requestHeaders !== null) {
            $attributes['appsignal.config.request_headers'] = $this->config->requestHeaders;
        }
        if ($this->config->responseHeaders !== null) {
            $attributes['appsignal.config.response_headers'] = $this->config->responseHeaders;
        }
        if ($this->config->sendFunctionParameters === false) {
            $attributes['appsignal.config.send_function_parameters'] = false;
        }
        if ($this->config->sendRequestQueryParameters === false) {
            $attributes['appsignal.config.send_request_query_parameters'] = false;
        }
        if ($this->config->sendRequestPayload === false) {
            $attributes['appsignal.config.send_request_payload'] = false;
        }
        if ($this->config->sendRequestSessionData === false) {
            $attributes['appsignal.config.send_request_session_data'] = false;
        }
        if ($this->config->revision !== null) {
            $attributes['appsignal.config.revision'] = $this->config->revision;
        }
        if ($this->config->hostname !== null) {
            $attributes['host.name'] = $this->config->hostname;
        }
        if ($this->config->serviceName !== null) {
            $attributes['service.name'] = $this->config->serviceName;
        }

        return $attributes;
    }
}

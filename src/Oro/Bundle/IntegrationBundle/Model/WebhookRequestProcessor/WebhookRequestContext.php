<?php

namespace Oro\Bundle\IntegrationBundle\Model\WebhookRequestProcessor;

/**
 * Represents the context of a webhook request, encapsulating the payload,
 * HTTP method, headers, request options, and associated metadata.
 */
class WebhookRequestContext
{
    public function __construct(
        private array $payload,
        private string $httpMethod,
        private array $headers,
        private array $requestOptions,
        private array $metadata
    ) {
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function setPayload(array $payload): void
    {
        $this->payload = $payload;
    }

    public function getHttpMethod(): string
    {
        return $this->httpMethod;
    }

    public function setHttpMethod(string $httpMethod): void
    {
        $this->httpMethod = $httpMethod;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }

    public function getRequestOptions(): array
    {
        return $this->requestOptions;
    }

    public function setRequestOptions(array $requestOptions): void
    {
        $this->requestOptions = $requestOptions;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
    }
}

<?php

namespace Oro\Bundle\ApiBundle\Request\Rest;

/**
 * The configuration options of CORS requests.
 */
final class CorsSettings
{
    private int $preflightMaxAge;
    /** @var string[] */
    private array $allowedOrigins;
    private bool $isCredentialsAllowed;
    /** @var string[] */
    private array $allowedHeaders;
    /** @var string[] */
    private array $exposableHeaders;

    /**
     * @param int      $preflightMaxAge
     * @param string[] $allowedOrigins
     * @param bool     $isCredentialsAllowed
     * @param string[] $allowedHeaders
     * @param string[] $exposableHeaders
     */
    public function __construct(
        int $preflightMaxAge,
        array $allowedOrigins,
        bool $isCredentialsAllowed,
        array $allowedHeaders,
        array $exposableHeaders
    ) {
        $this->preflightMaxAge = $preflightMaxAge;
        $this->allowedOrigins = $allowedOrigins;
        $this->isCredentialsAllowed = $isCredentialsAllowed;
        $this->allowedHeaders = $allowedHeaders;
        $this->exposableHeaders = $exposableHeaders;
    }

    /**
     * Gets the amount of seconds the user agent is allowed to cache CORS preflight requests.
     */
    public function getPreflightMaxAge(): int
    {
        return $this->preflightMaxAge;
    }

    /**
     * Gets the list of origins that are allowed to send CORS requests.
     *
     * @return string[]
     */
    public function getAllowedOrigins(): array
    {
        return $this->allowedOrigins;
    }

    /**
     * Indicates whether CORS request can include user credentials.
     */
    public function isCredentialsAllowed(): bool
    {
        return $this->isCredentialsAllowed;
    }

    /**
     * Gets the list of headers that are allowed to send by CORS requests.
     *
     * @return string[]
     */
    public function getAllowedHeaders(): array
    {
        return $this->allowedHeaders;
    }

    /**
     * Gets the list of headers that can be exposed by CORS responses.
     *
     * @return string[]
     */
    public function getExposableHeaders(): array
    {
        return $this->exposableHeaders;
    }
}

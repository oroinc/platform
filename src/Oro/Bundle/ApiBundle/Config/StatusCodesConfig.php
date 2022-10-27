<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * Represents a configuration of all possible response status codes for API resource or sub-resource.
 */
class StatusCodesConfig
{
    /** @var StatusCodeConfig[] */
    private array $codes = [];

    /**
     * Gets a native PHP array representation of the configuration.
     */
    public function toArray(): array
    {
        return ConfigUtil::convertObjectsToArray($this->codes, true);
    }

    /**
     * Indicates whether there is at least one status code.
     */
    public function isEmpty(): bool
    {
        return empty($this->codes);
    }

    /**
     * Makes a deep copy of the object.
     */
    public function __clone()
    {
        $this->codes = ConfigUtil::cloneObjects($this->codes);
    }

    /**
     * Indicates whether the configuration of at least one status code exists.
     */
    public function hasCodes(): bool
    {
        return !empty($this->codes);
    }

    /**
     * Gets the configuration for all status codes.
     *
     * @return StatusCodeConfig[] [code => config, ...]
     */
    public function getCodes(): array
    {
        return $this->codes;
    }

    /**
     * Indicates whether the configuration of the given status code exists.
     */
    public function hasCode(string $code): bool
    {
        return isset($this->codes[$code]);
    }

    /**
     * Gets the configuration of the status code.
     */
    public function getCode(string $code): ?StatusCodeConfig
    {
        return $this->codes[$code] ?? null;
    }

    /**
     * Adds the configuration of the status code.
     */
    public function addCode(string $code, StatusCodeConfig $config = null): StatusCodeConfig
    {
        if (null === $config) {
            $config = new StatusCodeConfig();
        }

        $this->codes[$code] = $config;

        return $config;
    }

    /**
     * Removes the configuration of the status code.
     */
    public function removeCode(string $code): void
    {
        unset($this->codes[$code]);
    }
}

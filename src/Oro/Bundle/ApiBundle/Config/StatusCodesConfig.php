<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * Represents a configuration of all possible response status codes.
 */
class StatusCodesConfig
{
    /** @var StatusCodeConfig[] */
    protected $codes = [];

    /**
     * Gets a native PHP array representation of the configuration.
     *
     * @return array
     */
    public function toArray()
    {
        return ConfigUtil::convertObjectsToArray($this->codes, true);
    }

    /**
     * Indicates whether there is at least one status code.
     *
     * @return bool
     */
    public function isEmpty()
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
     * Checks whether the configuration of at least one status code exists.
     *
     * @return bool
     */
    public function hasCodes()
    {
        return !empty($this->codes);
    }

    /**
     * Gets the configuration for all status codes.
     *
     * @return StatusCodeConfig[] [code => config, ...]
     */
    public function getCodes()
    {
        return $this->codes;
    }

    /**
     * Checks whether the configuration of the status code exists.
     *
     * @param string $code
     *
     * @return bool
     */
    public function hasCode($code)
    {
        return isset($this->codes[$code]);
    }

    /**
     * Gets the configuration of the status code.
     *
     * @param string $code
     *
     * @return StatusCodeConfig|null
     */
    public function getCode($code)
    {
        return isset($this->codes[$code])
            ? $this->codes[$code]
            : null;
    }

    /**
     * Adds the configuration of the status code.
     *
     * @param string                $code
     * @param StatusCodeConfig|null $config
     *
     * @return StatusCodeConfig
     */
    public function addCode($code, $config = null)
    {
        if (null === $config) {
            $config = new StatusCodeConfig();
        }

        $this->codes[$code] = $config;

        return $config;
    }

    /**
     * Removes the configuration of the status code.
     *
     * @param string $code
     */
    public function removeCode($code)
    {
        unset($this->codes[$code]);
    }
}

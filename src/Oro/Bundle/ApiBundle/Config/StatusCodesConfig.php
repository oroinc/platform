<?php

namespace Oro\Bundle\ApiBundle\Config;

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
        $result = [];
        if (!empty($this->codes)) {
            foreach ($this->codes as $code => $config) {
                $codeConfig = $config->toArray();
                $result[$code] = !empty($codeConfig) ? $codeConfig : null;
            }
        }

        return $result;
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
     * Make a deep copy of object.
     */
    public function __clone()
    {
        $this->codes = array_map(
            function ($config) {
                return clone $config;
            },
            $this->codes
        );
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

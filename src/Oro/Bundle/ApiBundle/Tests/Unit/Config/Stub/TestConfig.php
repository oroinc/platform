<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config\Stub;

use Oro\Bundle\ApiBundle\Model\Label;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class TestConfig
{
    /** @var string|null */
    private $exclusionPolicy;

    /** @var array */
    private $items = [];

    /**
     * Gets a native PHP array representation of the configuration.
     *
     * @return array
     */
    public function toArray()
    {
        $result = $this->items;
        if (null !== $this->exclusionPolicy && ConfigUtil::EXCLUSION_POLICY_NONE !== $this->exclusionPolicy) {
            $result[ConfigUtil::EXCLUSION_POLICY] = $this->exclusionPolicy;
        }

        return $result;
    }

    /**
     * Indicates whether the entity does not have a configuration.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return
            null === $this->exclusionPolicy
            && empty($this->items);
    }

    /**
     * Indicates whether the exclusion policy is set explicitly.
     *
     * @return bool
     */
    public function hasExclusionPolicy()
    {
        return null !== $this->exclusionPolicy;
    }

    /**
     * Gets the exclusion strategy that should be used for the entity.
     *
     * @return string An exclusion strategy, e.g. "none" or "all"
     */
    public function getExclusionPolicy()
    {
        if (null === $this->exclusionPolicy) {
            return 'none';
        }

        return $this->exclusionPolicy;
    }

    /**
     * Sets the exclusion strategy that should be used for the entity.
     *
     * @param string|null $exclusionPolicy An exclusion strategy, e.g. "none" or "all",
     *                                     or NULL to remove this option
     */
    public function setExclusionPolicy($exclusionPolicy)
    {
        $this->exclusionPolicy = $exclusionPolicy;
    }

    /**
     * Indicates whether all fields are not configured explicitly should be excluded.
     *
     * @return bool
     */
    public function isExcludeAll()
    {
        return 'all' === $this->exclusionPolicy;
    }

    /**
     * Sets the exclusion strategy to exclude all fields are not configured explicitly.
     */
    public function setExcludeAll()
    {
        $this->exclusionPolicy = 'all';
    }

    /**
     * Sets the exclusion strategy to exclude only fields are marked as excluded.
     */
    public function setExcludeNone()
    {
        $this->exclusionPolicy = 'none';
    }

    /**
     * Indicates whether the description attribute exists.
     *
     * @return bool
     */
    public function hasDescription()
    {
        return \array_key_exists('description', $this->items);
    }

    /**
     * Gets the value of the description attribute.
     *
     * @return string|Label|null
     */
    public function getDescription()
    {
        if (!\array_key_exists('description', $this->items)) {
            return null;
        }

        return $this->items['description'];
    }

    /**
     * Sets the value of the description attribute.
     *
     * @param string|Label|null $description
     */
    public function setDescription($description)
    {
        if ($description) {
            $this->items['description'] = $description;
        } else {
            unset($this->items['description']);
        }
    }

    /**
     * Checks whether the configuration attribute exists.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has($key)
    {
        return \array_key_exists($key, $this->items);
    }

    /**
     * Gets the configuration value.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get($key)
    {
        if (!\array_key_exists($key, $this->items)) {
            return null;
        }

        return $this->items[$key];
    }

    /**
     * Sets the configuration value.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function set($key, $value)
    {
        if (null !== $value) {
            $this->items[$key] = $value;
        } else {
            unset($this->items[$key]);
        }
    }

    /**
     * Removes the configuration value.
     *
     * @param string $key
     */
    public function remove($key)
    {
        unset($this->items[$key]);
    }

    /**
     * Gets names of all configuration attributes.
     *
     * @return string[]
     */
    public function keys()
    {
        return \array_keys($this->items);
    }
}

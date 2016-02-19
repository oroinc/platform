<?php

namespace Oro\Bundle\ApiBundle\Config\Traits;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * @property array $items
 */
trait ExclusionPolicyTrait
{
    /**
     * Indicates whether the exclusion policy is set explicitly.
     *
     * @return bool
     */
    public function hasExclusionPolicy()
    {
        return array_key_exists(ConfigUtil::EXCLUSION_POLICY, $this->items);
    }

    /**
     * Gets the exclusion strategy that should be used for the entity.
     *
     * @return string One of ConfigUtil::EXCLUSION_POLICY_* constant
     */
    public function getExclusionPolicy()
    {
        return array_key_exists(ConfigUtil::EXCLUSION_POLICY, $this->items)
            ? $this->items[ConfigUtil::EXCLUSION_POLICY]
            : ConfigUtil::EXCLUSION_POLICY_NONE;
    }

    /**
     * Sets the exclusion strategy that should be used for the entity.
     *
     * @param string $exclusionPolicy One of ConfigUtil::EXCLUSION_POLICY_* constant
     */
    public function setExclusionPolicy($exclusionPolicy)
    {
        $this->items[ConfigUtil::EXCLUSION_POLICY] = $exclusionPolicy;
    }

    /**
     * Indicates whether all fields are not configured explicitly should be excluded.
     *
     * @return bool
     */
    public function isExcludeAll()
    {
        return ConfigUtil::EXCLUSION_POLICY_ALL === $this->getExclusionPolicy();
    }

    /**
     * Sets the exclusion strategy to exclude all fields are not configured explicitly.
     */
    public function setExcludeAll()
    {
        $this->items[ConfigUtil::EXCLUSION_POLICY] = ConfigUtil::EXCLUSION_POLICY_ALL;
    }

    /**
     * Sets the exclusion strategy to exclude only fields are marked as excluded.
     */
    public function setExcludeNone()
    {
        unset($this->items[ConfigUtil::EXCLUSION_POLICY]);
    }
}

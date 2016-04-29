<?php

namespace Oro\Bundle\ApiBundle\Config\Traits;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * @property array $items
 */
trait ExcludeTrait
{
    /**
     * Indicates whether the exclusion flag is set explicitly.
     *
     * @return bool
     */
    public function hasExcluded()
    {
        return array_key_exists(ConfigUtil::EXCLUDE, $this->items);
    }

    /**
     * Indicates whether the exclusion flag.
     *
     * @return bool
     */
    public function isExcluded()
    {
        return array_key_exists(ConfigUtil::EXCLUDE, $this->items)
            ? $this->items[ConfigUtil::EXCLUDE]
            : false;
    }

    /**
     * Sets the exclusion flag.
     *
     * @param bool $exclude
     */
    public function setExcluded($exclude = true)
    {
        $this->items[ConfigUtil::EXCLUDE] = $exclude;
    }
}

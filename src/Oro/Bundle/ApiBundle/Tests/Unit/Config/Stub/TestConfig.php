<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config\Stub;

use Oro\Bundle\ApiBundle\Config\Traits;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class TestConfig
{
    use Traits\ConfigTrait;
    use Traits\ExclusionPolicyTrait;

    /** @var array */
    protected $items = [];

    /**
     * Gets a native PHP array representation of the configuration.
     *
     * @return array
     */
    public function toArray()
    {
        $result = $this->items;
        $this->removeItemWithDefaultValue($result, ConfigUtil::EXCLUSION_POLICY, ConfigUtil::EXCLUSION_POLICY_NONE);

        return $result;
    }

    /**
     * Indicates whether the entity does not have a configuration.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->items);
    }

    /**
     * @return string|null
     */
    public function getLabel()
    {
        return array_key_exists(ConfigUtil::LABEL, $this->items)
            ? $this->items[ConfigUtil::LABEL]
            : null;
    }

    /**
     * @param string|null $label
     */
    public function setLabel($label)
    {
        if ($label) {
            $this->items[ConfigUtil::LABEL] = $label;
        } else {
            unset($this->items[ConfigUtil::LABEL]);
        }
    }
}

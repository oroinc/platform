<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config\Stub;

use Oro\Bundle\ApiBundle\Config\Traits;

class TestConfig
{
    use Traits\ConfigTrait;
    use Traits\ExclusionPolicyTrait;

    const EXCLUSION_POLICY      = 'exclusion_policy';
    const EXCLUSION_POLICY_ALL  = 'all';
    const EXCLUSION_POLICY_NONE = 'none';
    const LABEL                 = 'label';

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
        $this->removeItemWithDefaultValue($result, self::EXCLUSION_POLICY, self::EXCLUSION_POLICY_NONE);

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
        return array_key_exists(self::LABEL, $this->items)
            ? $this->items[self::LABEL]
            : null;
    }

    /**
     * @param string|null $label
     */
    public function setLabel($label)
    {
        if ($label) {
            $this->items[self::LABEL] = $label;
        } else {
            unset($this->items[self::LABEL]);
        }
    }
}

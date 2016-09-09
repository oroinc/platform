<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config\Stub;

use Oro\Bundle\ApiBundle\Config\Traits;

class TestConfig
{
    use Traits\ConfigTrait;
    use Traits\ExclusionPolicyTrait;
    use Traits\DescriptionTrait;

    const EXCLUSION_POLICY      = 'exclusion_policy';
    const EXCLUSION_POLICY_ALL  = 'all';
    const EXCLUSION_POLICY_NONE = 'none';
    const DESCRIPTION           = 'description';

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
}

<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface;

class TestFilterValueAccessor implements FilterValueAccessorInterface
{
    /** @var FilterValue[] */
    private $values = [];

    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        return isset($this->values[$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        return $this->values[$key] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function getGroup($group)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultGroupName()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultGroupName($group)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getAll()
    {
        return $this->values;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, FilterValue $value = null)
    {
        if (null === $value) {
            unset($this->values[$key]);
        } else {
            $this->values[$key] = $value;
            $value->setSourceKey($key);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key)
    {
        unset($this->values[$key]);
    }
}

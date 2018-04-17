<?php

namespace Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Filter;

use Oro\Component\EntitySerializer\Filter\EntityAwareFilterInterface;

class TestFilter implements EntityAwareFilterInterface
{
    /** @var array */
    private $checkRules;

    public function setCheckRules(array $checkRules)
    {
        $this->checkRules = $checkRules;
    }

    /**
     * {@inheritdoc}
     */
    public function checkField($entity, $entityClass, $field)
    {
        if (array_key_exists($field, $this->checkRules)) {
            return $this->checkRules[$field];
        }

        return self::FILTER_NOTHING;
    }
}

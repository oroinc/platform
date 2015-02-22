<?php

namespace Oro\Component\ConfigExpression\Condition;

use Oro\Component\ConfigExpression\Exception;

/**
 * Implements logical FALSE constant.
 */
class False extends AbstractCondition
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'false';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return $this->convertToArray(null);
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (!empty($options)) {
            throw new Exception\InvalidArgumentException('Options are prohibited.');
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function isConditionAllowed($context)
    {
        return false;
    }
}

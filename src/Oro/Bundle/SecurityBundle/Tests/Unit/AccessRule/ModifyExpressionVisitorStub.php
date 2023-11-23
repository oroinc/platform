<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\AccessRule;

use Oro\Bundle\SecurityBundle\AccessRule\Expr\Comparison;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Value;
use Oro\Bundle\SecurityBundle\AccessRule\ModifyExpressionVisitor;

class ModifyExpressionVisitorStub extends ModifyExpressionVisitor
{
    /** @var callable|null */
    private $walkComparisonCallback;
    /** @var callable|null */
    private $walkValueCallback;

    /**
     * @param callable|null $callback function (Comparison $comparison): mixed
     */
    public function setWalkComparisonCallback(?callable $callback): void
    {
        $this->walkComparisonCallback = $callback;
    }

    /**
     * @param callable|null $callback function (Value $value): mixed
     */
    public function setWalkValueCallback(?callable $callback): void
    {
        $this->walkValueCallback = $callback;
    }

    /**
     * {@inheritDoc}
     */
    public function walkComparison(Comparison $comparison): mixed
    {
        if (null !== $this->walkComparisonCallback) {
            return \call_user_func($this->walkComparisonCallback, $comparison);
        }

        return parent::walkComparison($comparison);
    }

    /**
     * {@inheritDoc}
     */
    public function walkValue(Value $value): mixed
    {
        if (null !== $this->walkValueCallback) {
            return \call_user_func($this->walkValueCallback, $value);
        }

        return parent::walkValue($value);
    }
}

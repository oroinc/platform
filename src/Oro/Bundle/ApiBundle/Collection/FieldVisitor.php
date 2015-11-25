<?php

namespace Oro\Bundle\ApiBundle\Collection;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Value;

class FieldVisitor extends ExpressionVisitor
{
    /** @var array */
    protected $fields = [];

    /**
     * Gets all fields are used in a visited expression.
     *
     * @return string[]
     */
    public function getFields()
    {
        return array_keys($this->fields);
    }

    /**
     * {@inheritdoc}
     */
    public function walkComparison(Comparison $comparison)
    {
        $field = $comparison->getField();
        if (!isset($this->fields[$field])) {
            $this->fields[$field] = true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkValue(Value $value)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function walkCompositeExpression(CompositeExpression $expr)
    {
        foreach ($expr->getExpressionList() as $child) {
            $this->dispatch($child);
        }
    }
}

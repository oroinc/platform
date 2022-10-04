<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Expression;

/**
 * This filter supports an association field using composite identifier.
 * Also see CompositeIdentifierFilter
 */
class AssociationCompositeIdFilter extends CompositeIdentifierFilter implements FieldAwareFilterInterface
{
    private string $field;

    /**
     * Gets a field by which the data is filtered.
     */
    public function getField(): ?string
    {
        return $this->field;
    }

    /**
     * {@inheritdoc}
     */
    public function setField(string $field): void
    {
        $this->field = $field;
    }

    protected function buildEqualExpression(array $value): Expression
    {
        $expressions = [];
        foreach ($value as $fieldName => $fieldValue) {
            $expressions[] = Criteria::expr()?->eq(
                sprintf("%s.%s", $this->getField(), $this->metadata->getProperty($fieldName)?->getPropertyPath()),
                $fieldValue
            );
        }

        return new CompositeExpression(CompositeExpression::TYPE_AND, $expressions);
    }

    protected function buildNotEqualExpression(array $value): Expression
    {
        $expressions = [];
        foreach ($value as $fieldName => $fieldValue) {
            $expressions[] = Criteria::expr()?->neq(
                sprintf("%s.%s", $this->getField(), $this->metadata->getProperty($fieldName)?->getPropertyPath()),
                $fieldValue
            );
        }

        return new CompositeExpression(CompositeExpression::TYPE_OR, $expressions);
    }
}

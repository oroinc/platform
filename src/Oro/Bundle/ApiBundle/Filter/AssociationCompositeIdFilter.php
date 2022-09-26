<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Expression;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;
use Oro\Component\PhpUtils\ArrayUtil;

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

    /**
     * {@inheritdoc}
     */
    public function apply(Criteria $criteria, FilterValue $value = null): void
    {
        if (null !== $value) {
            $criteria->andWhere(
                $this->buildExpression($value->getOperator(), $value->getValue())
            );
        }
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function buildExpression(?string $operator, mixed $value): Expression
    {
        if (null === $value) {
            throw new \InvalidArgumentException('The composite identifier value must not be NULL.');
        }

        if (null === $operator) {
            $operator = FilterOperator::EQ;
        }
        if (!\in_array($operator, $this->getSupportedOperators(), true)) {
            throw new \InvalidArgumentException(\sprintf(
                'The operator "%s" is not supported for composite identifier.',
                $operator
            ));
        }

        $entityIdTransformer = $this->getEntityIdTransformer();
        if (\is_array($value) && !ArrayUtil::isAssoc($value)) {
            $expressions = [];
            if (FilterOperator::NEQ === $operator) {
                foreach ($value as $val) {
                    $expressions[] = $this->buildNotEqualExpression(
                        $entityIdTransformer->reverseTransform($val, $this->getMetadata())
                    );
                }
                $expr = new CompositeExpression(CompositeExpression::TYPE_AND, $expressions);
            } else {
                foreach ($value as $val) {
                    $expressions[] = $this->buildEqualExpression(
                        $entityIdTransformer->reverseTransform($val, $this->getMetadata())
                    );
                }
                $expr = new CompositeExpression(CompositeExpression::TYPE_OR, $expressions);
            }
        } else {
            $value = $entityIdTransformer->reverseTransform($value, $this->getMetadata());
            if (FilterOperator::NEQ === $operator) {
                $expr = $this->buildNotEqualExpression($value);
            } else {
                $expr = $this->buildEqualExpression($value);
            }
        }

        return $expr;
    }

    private function buildEqualExpression(array $value): Expression
    {
        $expressions = [];
        foreach ($value as $fieldName => $fieldValue) {
            $expressions[] = Criteria::expr()?->eq(
                sprintf("%s.%s", $this->getField(), $this->getMetadata()->getProperty($fieldName)?->getPropertyPath()),
                $fieldValue
            );
        }

        return new CompositeExpression(CompositeExpression::TYPE_AND, $expressions);
    }

    private function buildNotEqualExpression(array $value): Expression
    {
        $expressions = [];
        foreach ($value as $fieldName => $fieldValue) {
            $expressions[] = Criteria::expr()?->neq(
                sprintf("%s.%s", $this->getField(), $this->getMetadata()->getProperty($fieldName)?->getPropertyPath()),
                $fieldValue
            );
        }

        return new CompositeExpression(CompositeExpression::TYPE_OR, $expressions);
    }

    private function getEntityIdTransformer(): EntityIdTransformerInterface
    {
        return $this->getEntityIdTransformerRegistry()->getEntityIdTransformer($this->getRequestType());
    }
}

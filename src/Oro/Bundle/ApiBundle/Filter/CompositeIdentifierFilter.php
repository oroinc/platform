<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Expression;
use Oro\Bundle\ApiBundle\Exception\InvalidFilterOperatorException;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Component\PhpUtils\ArrayUtil;

/**
 * A filter that can be used to filter data by composite identifier.
 * This filter supports only "equal" and "not equal" comparisons.
 * Also filtering by several identifiers is supported.
 */
class CompositeIdentifierFilter extends StandaloneFilter implements
    FieldFilterInterface,
    RequestAwareFilterInterface,
    MetadataAwareFilterInterface
{
    private RequestType $requestType;
    private EntityMetadata $metadata;
    private EntityIdTransformerRegistry $entityIdTransformerRegistry;

    /**
     * {@inheritDoc}
     */
    public function setRequestType(RequestType $requestType): void
    {
        $this->requestType = $requestType;
    }

    /**
     * {@inheritDoc}
     */
    public function setMetadata(EntityMetadata $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function setEntityIdTransformerRegistry(EntityIdTransformerRegistry $registry): void
    {
        $this->entityIdTransformerRegistry = $registry;
    }

    /**
     * {@inheritDoc}
     */
    public function apply(Criteria $criteria, FilterValue $value = null): void
    {
        if (null !== $value) {
            $criteria->andWhere(
                $this->buildExpression($value->getOperator(), $value->getValue())
            );
        }
    }

    protected function getFieldPath(string $fieldName): string
    {
        return $this->metadata->getProperty($fieldName)->getPropertyPath();
    }

    protected function buildExpressionForSingleIdentifier(
        EntityIdTransformerInterface $entityIdTransformer,
        ?string $operator,
        mixed $value
    ): Expression {
        if (FilterOperator::EQ === $operator) {
            // expression: field1 = value1 AND field2 = value2 AND ...
            return $this->buildEqualExpression($entityIdTransformer->reverseTransform($value, $this->metadata));
        }
        if (FilterOperator::NEQ === $operator) {
            // expression: field1 != value1 OR field2 != value2 OR ...
            // this expression equals to NOT (field1 = value1 AND field2 = value2 AND ...),
            // but Criteria object does not support NOT expression
            return $this->buildNotEqualExpression($entityIdTransformer->reverseTransform($value, $this->metadata));
        }
        throw new InvalidFilterOperatorException($operator);
    }

    protected function buildExpressionForListOfIdentifiers(
        EntityIdTransformerInterface $entityIdTransformer,
        ?string $operator,
        array $value
    ): Expression {
        $expressions = [];
        foreach ($value as $val) {
            $expressions[] = $this->buildExpressionForSingleIdentifier($entityIdTransformer, $operator, $val);
        }

        if (FilterOperator::EQ === $operator) {
            // expression: (field1 = value1 AND field2 = value2 AND ...) OR (...)
            return new CompositeExpression(CompositeExpression::TYPE_OR, $expressions);
        }
        if (FilterOperator::NEQ === $operator) {
            // expression: (field1 != value1 OR field2 != value2 OR ...) AND (...)
            // this expression equals to NOT ((field1 = value1 AND field2 = value2 AND ...) OR (...)),
            // but Criteria object does not support NOT expression
            return new CompositeExpression(CompositeExpression::TYPE_AND, $expressions);
        }
        throw new InvalidFilterOperatorException($operator);
    }

    private function buildExpression(?string $operator, mixed $value): Expression
    {
        if (null === $value) {
            throw new \InvalidArgumentException('The composite identifier value must not be NULL.');
        }

        if (null === $operator) {
            $operator = FilterOperator::EQ;
        }
        if (!\in_array($operator, $this->getSupportedOperators(), true)) {
            throw new \InvalidArgumentException(sprintf(
                'The operator "%s" is not supported for composite identifier.',
                $operator
            ));
        }

        $entityIdTransformer = $this->entityIdTransformerRegistry->getEntityIdTransformer($this->requestType);
        if (\is_array($value) && !ArrayUtil::isAssoc($value)) {
            return $this->buildExpressionForListOfIdentifiers($entityIdTransformer, $operator, $value);
        }

        return $this->buildExpressionForSingleIdentifier($entityIdTransformer, $operator, $value);
    }

    private function buildEqualExpression(array $value): Expression
    {
        $expressions = [];
        foreach ($value as $fieldName => $fieldValue) {
            $expressions[] = Criteria::expr()->eq($this->getFieldPath($fieldName), $fieldValue);
        }

        return new CompositeExpression(CompositeExpression::TYPE_AND, $expressions);
    }

    private function buildNotEqualExpression(array $value): Expression
    {
        $expressions = [];
        foreach ($value as $fieldName => $fieldValue) {
            $expressions[] = Criteria::expr()->neq($this->getFieldPath($fieldName), $fieldValue);
        }

        return new CompositeExpression(CompositeExpression::TYPE_OR, $expressions);
    }
}

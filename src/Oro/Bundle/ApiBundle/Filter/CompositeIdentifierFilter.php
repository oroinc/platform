<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Expression;
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
    /** @var RequestType */
    private $requestType;

    /** @var EntityMetadata */
    private $metadata;

    /** @var EntityIdTransformerRegistry */
    private $entityIdTransformerRegistry;

    /**
     * {@inheritdoc}
     */
    public function setRequestType(RequestType $requestType): void
    {
        $this->requestType = $requestType;
    }

    /**
     * {@inheritdoc}
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
     * @param string|null $operator
     * @param mixed       $value
     *
     * @return Expression
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function buildExpression(?string $operator, $value): Expression
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
            // a list of identifiers
            if (FilterOperator::NEQ === $operator) {
                // expression: (field1 != value1 OR field2 != value2 OR ...) AND (...)
                // this expression equals to NOT ((field1 = value1 AND field2 = value2 AND ...) OR (...)),
                // but Criteria object does not support NOT expression
                $expressions = [];
                foreach ($value as $val) {
                    $expressions[] = $this->buildNotEqualExpression(
                        $entityIdTransformer->reverseTransform($val, $this->metadata)
                    );
                }
                $expr = new CompositeExpression(CompositeExpression::TYPE_AND, $expressions);
            } else {
                // expression: (field1 = value1 AND field2 = value2 AND ...) OR (...)
                $expressions = [];
                foreach ($value as $val) {
                    $expressions[] = $this->buildEqualExpression(
                        $entityIdTransformer->reverseTransform($val, $this->metadata)
                    );
                }
                $expr = new CompositeExpression(CompositeExpression::TYPE_OR, $expressions);
            }
        } else {
            // single identifier
            $value = $entityIdTransformer->reverseTransform($value, $this->metadata);
            if (FilterOperator::NEQ === $operator) {
                // expression: field1 != value1 OR field2 != value2 OR ...
                // this expression equals to NOT (field1 = value1 AND field2 = value2 AND ...),
                // but Criteria object does not support NOT expression
                $expr = $this->buildNotEqualExpression($value);
            } else {
                // expression: field1 = value1 AND field2 = value2 AND ...
                $expr = $this->buildEqualExpression($value);
            }
        }

        return $expr;
    }

    private function buildEqualExpression(array $value): Expression
    {
        $expressions = [];
        foreach ($value as $fieldName => $fieldValue) {
            $expressions[] = Criteria::expr()->eq(
                $this->metadata->getProperty($fieldName)->getPropertyPath(),
                $fieldValue
            );
        }

        return new CompositeExpression(CompositeExpression::TYPE_AND, $expressions);
    }

    private function buildNotEqualExpression(array $value): Expression
    {
        $expressions = [];
        foreach ($value as $fieldName => $fieldValue) {
            $expressions[] = Criteria::expr()->neq(
                $this->metadata->getProperty($fieldName)->getPropertyPath(),
                $fieldValue
            );
        }

        return new CompositeExpression(CompositeExpression::TYPE_OR, $expressions);
    }

    private function getEntityIdTransformer(): EntityIdTransformerInterface
    {
        return $this->entityIdTransformerRegistry->getEntityIdTransformer($this->requestType);
    }
}

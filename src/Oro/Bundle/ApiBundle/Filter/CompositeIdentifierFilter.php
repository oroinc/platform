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
 * The filter that can be used to filter data by a composite identifier.
 * This filter supports only "equal" and "not equal" comparisons.
 * Also filtering by several identifiers is supported.
 */
class CompositeIdentifierFilter extends AbstractCompositeIdentifierFilter implements
    RequestAwareFilterInterface,
    MetadataAwareFilterInterface
{
    private RequestType $requestType;
    private EntityMetadata $metadata;
    private EntityIdTransformerRegistry $entityIdTransformerRegistry;
    private ?EntityIdTransformerInterface $entityIdTransformer = null;

    #[\Override]
    public function setRequestType(RequestType $requestType): void
    {
        $this->requestType = $requestType;
    }

    #[\Override]
    public function setMetadata(EntityMetadata $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function setEntityIdTransformerRegistry(EntityIdTransformerRegistry $registry): void
    {
        $this->entityIdTransformerRegistry = $registry;
    }

    protected function getFieldPath(string $fieldName): string
    {
        return $this->metadata->getProperty($fieldName)->getPropertyPath();
    }

    #[\Override]
    public function apply(Criteria $criteria, FilterValue $value = null): void
    {
        if (null !== $value) {
            $this->entityIdTransformer = $this->entityIdTransformerRegistry->getEntityIdTransformer($this->requestType);
            try {
                parent::apply($criteria, $value);
            } finally {
                $this->entityIdTransformer = null;
            }
        }
    }

    #[\Override]
    protected function isListOfIdentifiers(mixed $value): bool
    {
        return parent::isListOfIdentifiers($value) && !ArrayUtil::isAssoc($value);
    }

    #[\Override]
    protected function buildEqualExpression(array $value): Expression
    {
        $expressions = [];
        foreach ($value as $fieldName => $fieldValue) {
            $expressions[] = Criteria::expr()->eq($this->getFieldPath($fieldName), $fieldValue);
        }

        return new CompositeExpression(CompositeExpression::TYPE_AND, $expressions);
    }

    #[\Override]
    protected function buildNotEqualExpression(array $value): Expression
    {
        $expressions = [];
        foreach ($value as $fieldName => $fieldValue) {
            $expressions[] = Criteria::expr()->neq($this->getFieldPath($fieldName), $fieldValue);
        }

        return new CompositeExpression(CompositeExpression::TYPE_OR, $expressions);
    }

    #[\Override]
    protected function parseIdentifier(mixed $value): mixed
    {
        return $this->entityIdTransformer->reverseTransform($value, $this->metadata);
    }
}

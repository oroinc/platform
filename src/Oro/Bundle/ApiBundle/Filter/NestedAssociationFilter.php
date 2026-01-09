<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Expression;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Exception\InvalidFilterOperatorException;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * A filter that can be used to filter data by a nested association.
 */
class NestedAssociationFilter extends AssociationFilter implements
    ConfigAwareFilterInterface,
    MetadataAwareFilterInterface
{
    private EntityDefinitionConfig $config;
    private EntityMetadata $metadata;

    #[\Override]
    public function setConfig(EntityDefinitionConfig $config): void
    {
        $this->config = $config;
    }

    #[\Override]
    public function setMetadata(EntityMetadata $metadata): void
    {
        $this->metadata = $metadata;
    }

    #[\Override]
    protected function doBuildExpression(string $field, string $path, string $operator, mixed $value): ?Expression
    {
        $this->assertFilterValuePath($field, $path);

        $entityClass = $this->getEntityClass(substr($path, \strlen($field) + 1));

        $targetConfig = $this->config->getField($field)->getTargetEntity();
        $entityClassFieldName = $targetConfig->getField(ConfigUtil::CLASS_NAME)->getPropertyPath();
        $entityIdFieldName = $targetConfig->getField('id')->getPropertyPath();

        if (FilterOperator::EXISTS === $operator) {
            $expr = $this->buildEqualToExpression($entityClassFieldName, $entityClass);
            if (!$value) {
                $expr = Criteria::expr()->orX(
                    $this->buildNotExpression($expr),
                    $this->buildEqualToExpression($entityIdFieldName, null)
                );
            }

            return $expr;
        }

        if (
            FilterOperator::EQ === $operator
            || FilterOperator::NEQ === $operator
            || FilterOperator::NEQ_OR_NULL === $operator
        ) {
            $expr = Criteria::expr()->andX(
                $this->buildEqualToExpression($entityClassFieldName, $entityClass),
                $this->buildComparisonExpression($entityIdFieldName, 'ENTITY', [
                    $entityClass,
                    $this->buildEqualToExpression($this->getTargetIdFieldName($entityClass, $field), $value)
                ])
            );
            if (FilterOperator::NEQ === $operator) {
                $expr = $this->buildNotExpression($expr);
            } elseif (FilterOperator::NEQ_OR_NULL === $operator) {
                $expr = Criteria::expr()->orX(
                    $this->buildNotExpression($expr),
                    $this->buildEqualToExpression($entityIdFieldName, null)
                );
            }

            return $expr;
        }

        throw new InvalidFilterOperatorException($operator);
    }

    private function getTargetIdFieldName(string $entityClass, string $field): string
    {
        $associationMetadata = $this->metadata->getAssociation($field);
        if (null === $associationMetadata) {
            $associationMetadata = $this->metadata->getEntityMetadata($this->metadata->getClassName())
                ->getAssociation($field);
        }
        $targetMetadata = $associationMetadata->getTargetMetadata($entityClass);
        $targetIdFieldNames = $targetMetadata->getIdentifierFieldNames();

        return $targetMetadata->getProperty(reset($targetIdFieldNames))->getPropertyPath();
    }
}

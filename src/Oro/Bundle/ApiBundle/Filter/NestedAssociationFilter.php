<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Expression;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Provider\EntityAliasResolverRegistry;

/**
 * A filter that can be used to filter data by a nested association.
 */
class NestedAssociationFilter extends AssociationFilter implements ConfigAwareFilterInterface
{
    private EntityAliasResolverRegistry $entityAliasResolverRegistry;

    private string $fieldName;
    private EntityDefinitionConfig $config;

    public function setEntityAliasResolverRegistry(EntityAliasResolverRegistry $entityAliasResolverRegistry): void
    {
        $this->entityAliasResolverRegistry = $entityAliasResolverRegistry;
    }

    public function setFieldName(string $fieldName): void
    {
        $this->fieldName = $fieldName;
    }

    #[\Override]
    public function setConfig(EntityDefinitionConfig $config): void
    {
        $this->config = $config;
    }

    #[\Override]
    public function getField(): ?string
    {
        return $this->fieldName;
    }

    #[\Override]
    protected function doBuildExpression(string $field, string $path, string $operator, mixed $value): ?Expression
    {
        $this->assertFilterValuePath($field, $path);

        $entityAliasResolver = $this->entityAliasResolverRegistry
            ->getEntityAliasResolver($this->getRequestType());
        $className = $entityAliasResolver->getClassByPluralAlias(substr($path, \strlen($field) + 1));

        $targetEntity = $this->config->getField($field)->getTargetEntity();
        $expr = Criteria::expr()->andX(
            $this->buildEqualToExpression($targetEntity->getField('__class__')->getPropertyPath(), $className),
            parent::doBuildExpression($targetEntity->getField('id')->getPropertyPath(), $path, $operator, $value)
        );

        if (FilterOperator::NEQ === $operator) {
            $expr = $this->buildNotExpression($expr);
        }

        return $expr;
    }
}

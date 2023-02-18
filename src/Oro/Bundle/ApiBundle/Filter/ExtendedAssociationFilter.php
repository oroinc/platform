<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\Expression;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderRegistry;
use Oro\Bundle\ApiBundle\Provider\ExtendedAssociationProvider;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;

/**
 * A filter that can be used to filter data by a multi-target association.
 */
class ExtendedAssociationFilter extends AssociationFilter implements ConfigAwareFilterInterface
{
    private ExtendedAssociationProvider $extendedAssociationProvider;
    private EntityOverrideProviderRegistry $entityOverrideProviderRegistry;
    private string $associationOwnerClass;
    private string $associationType;
    private ?string $associationKind = null;
    private EntityDefinitionConfig $config;

    public function setExtendedAssociationProvider(ExtendedAssociationProvider $extendedAssociationProvider): void
    {
        $this->extendedAssociationProvider = $extendedAssociationProvider;
    }

    public function setEntityOverrideProviderRegistry(EntityOverrideProviderRegistry $registry): void
    {
        $this->entityOverrideProviderRegistry = $registry;
    }

    public function setAssociationOwnerClass(string $associationOwnerClass): void
    {
        $this->associationOwnerClass = $associationOwnerClass;
    }

    public function setAssociationType(string $associationType): void
    {
        $this->associationType = $associationType;
    }

    public function setAssociationKind(?string $associationKind): void
    {
        $this->associationKind = $associationKind;
    }

    /**
     * {@inheritDoc}
     */
    public function setConfig(EntityDefinitionConfig $config): void
    {
        $this->config = $config;
    }

    /**
     * {@inheritDoc}
     */
    protected function doBuildExpression(string $field, string $path, string $operator, mixed $value): ?Expression
    {
        $this->assertFilterValuePath($field, $path);

        $fieldName = $this->getFieldName(substr($path, \strlen($field) + 1));
        if (RelationType::MANY_TO_MANY === $this->associationType) {
            $expr = $this->buildComparisonExpression($fieldName, Comparison::MEMBER_OF, $value);
            if (FilterOperator::NEQ === $operator) {
                $expr = $this->buildNotExpression($expr);
            }

            return $expr;
        }

        return parent::doBuildExpression($fieldName, $path, $operator, $value);
    }

    protected function getFieldName(string $filterValueName): string
    {
        $fieldName = null;
        $targetFieldNames = $this->config->getField($this->getField())?->getDependsOn();
        if ($targetFieldNames) {
            $targetEntityClass = $this->getEntityClass($filterValueName);
            $associationTargets = $this->extendedAssociationProvider->filterExtendedAssociationTargets(
                $this->associationOwnerClass,
                $this->associationType,
                $this->associationKind,
                $targetFieldNames
            );

            $entityOverrideProvider = $this->entityOverrideProviderRegistry->getEntityOverrideProvider(
                $this->getRequestType()
            );
            foreach ($associationTargets as $targetClass => $targetField) {
                $substituteTargetClass = $entityOverrideProvider->getSubstituteEntityClass($targetClass);
                if ($substituteTargetClass) {
                    $targetClass = $substituteTargetClass;
                }
                if ($targetClass === $targetEntityClass) {
                    $fieldName = $targetField;
                    break;
                }
            }
        }
        if (!$fieldName) {
            throw new RuntimeException(sprintf('An association with "%s" is not supported.', $filterValueName));
        }

        return $fieldName;
    }
}

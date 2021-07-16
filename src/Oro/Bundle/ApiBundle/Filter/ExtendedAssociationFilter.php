<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\Expression;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderRegistry;
use Oro\Bundle\EntityExtendBundle\Entity\Manager\AssociationManager;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;

/**
 * A filter that can be used to filter data by a multi-target association.
 */
class ExtendedAssociationFilter extends AssociationFilter
{
    /** @var AssociationManager */
    private $associationManager;

    /** @var EntityOverrideProviderRegistry */
    private $entityOverrideProviderRegistry;

    /** @var string */
    private $associationOwnerClass;

    /** @var string */
    private $associationType;

    /** @var string|null */
    private $associationKind;

    public function setAssociationManager(AssociationManager $associationManager): void
    {
        $this->associationManager = $associationManager;
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
     * {@inheritdoc}
     */
    protected function doBuildExpression(string $field, string $path, string $operator, $value): ?Expression
    {
        $this->assertFilterValuePath($field, $path);

        $fieldName = $this->getFieldName(\substr($path, \strlen($field) + 1));
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
        $targetEntityClass = $this->getEntityClass($filterValueName);
        $associationTargets = $this->associationManager->getAssociationTargets(
            $this->associationOwnerClass,
            null,
            $this->associationType,
            $this->associationKind
        );

        $fieldName = null;
        $entityOverrideProvider = $this->entityOverrideProviderRegistry
            ->getEntityOverrideProvider($this->getRequestType());
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
        if (!$fieldName) {
            throw new RuntimeException(\sprintf(
                'An association with "%s" is not supported.',
                $filterValueName
            ));
        }

        return $fieldName;
    }
}

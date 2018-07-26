<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Doctrine\Common\Collections\Expr\Comparison;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderRegistry;
use Oro\Bundle\EntityExtendBundle\Entity\Manager\AssociationManager;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;

/**
 * A filter that can be used to filter data by an extended association.
 */
class ExtendedAssociationFilter extends AssociationFilter
{
    /** @var AssociationManager */
    protected $associationManager;

    /** @var EntityOverrideProviderRegistry */
    protected $entityOverrideProviderRegistry;

    /** @var string */
    protected $associationOwnerClass;

    /** @var string */
    protected $associationType;

    /** @var string|null */
    protected $associationKind;

    /**
     * @param AssociationManager $associationManager
     */
    public function setAssociationManager(AssociationManager $associationManager)
    {
        $this->associationManager = $associationManager;
    }

    /**
     * @param EntityOverrideProviderRegistry $entityOverrideProviderRegistry
     */
    public function setEntityOverrideProviderRegistry(EntityOverrideProviderRegistry $entityOverrideProviderRegistry)
    {
        $this->entityOverrideProviderRegistry = $entityOverrideProviderRegistry;
    }

    /**
     * @param string $associationOwnerClass
     */
    public function setAssociationOwnerClass($associationOwnerClass)
    {
        $this->associationOwnerClass = $associationOwnerClass;
    }

    /**
     * @param string $associationType
     */
    public function setAssociationType($associationType)
    {
        $this->associationType = $associationType;
    }

    /**
     * @param string $associationKind
     */
    public function setAssociationKind($associationKind)
    {
        $this->associationKind = $associationKind;
    }

    /**
     * {@inheritdoc}
     */
    protected function doBuildExpression($field, $path, $operator, $value)
    {
        $this->assertFilterValuePath($field, $path);

        $fieldName = $this->getFieldName(\substr($path, \strlen($field) + 1));
        if (RelationType::MANY_TO_MANY === $this->associationType) {
            $expr = $this->buildComparisonExpression($fieldName, Comparison::MEMBER_OF, $value);
            if (self::NEQ === $operator) {
                $expr = $this->buildNotExpression($expr);
            }

            return $expr;
        }

        return parent::doBuildExpression($fieldName, $path, $operator, $value);
    }

    /**
     * @param string $filterValueName
     *
     * @return string
     */
    protected function getFieldName($filterValueName)
    {
        $targetEntityClass = $this->getEntityClass($filterValueName);
        $associationTargets = $this->associationManager->getAssociationTargets(
            $this->associationOwnerClass,
            null,
            $this->associationType,
            $this->associationKind
        );

        $fieldName = null;
        $entityOverrideProvider = $this->entityOverrideProviderRegistry->getEntityOverrideProvider($this->requestType);
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
            throw new RuntimeException(
                \sprintf('An association with "%s" is not supported.', $filterValueName)
            );
        }

        return $fieldName;
    }
}

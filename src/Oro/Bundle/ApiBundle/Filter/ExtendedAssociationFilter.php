<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\EntityExtendBundle\Entity\Manager\AssociationManager;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;

/**
 * A filter that can be used to filter data by an extended association.
 */
class ExtendedAssociationFilter extends AssociationFilter
{
    /** @var AssociationManager */
    protected $associationManager;

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

        $fieldName = $this->getFieldName(substr($path, strlen($field) + 1));
        if (RelationType::MANY_TO_MANY === $this->associationType) {
            $expr = new Comparison($fieldName, 'MEMBER OF', $value);
            if (self::NEQ === $operator) {
                $expr = new CompositeExpression('NOT', [$expr]);
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
        if (!isset($associationTargets[$targetEntityClass])) {
            throw new RuntimeException(
                sprintf('An association with "%s" is not supported.', $filterValueName)
            );
        }

        return $associationTargets[$targetEntityClass];
    }
}

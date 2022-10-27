<?php

namespace Oro\Bundle\DraftBundle\Acl\AccessRule;

use Oro\Bundle\DraftBundle\Entity\DraftableInterface;
use Oro\Bundle\DraftBundle\Helper\DraftPermissionHelper;
use Oro\Bundle\SecurityBundle\AccessRule\AccessRuleInterface;
use Oro\Bundle\SecurityBundle\AccessRule\Criteria;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Comparison;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\CompositeExpression;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Path;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclConditionDataBuilderInterface;

/**
 * The access rule that allows access to the drafts that owned by the current user
 * and based on the value of VIEW_ALL_DRAFTS permission
 */
class DraftAccessRule implements AccessRuleInterface
{
    /** @var AclConditionDataBuilderInterface */
    private $builder;

    /** @var DraftPermissionHelper */
    private $draftPermissionHelper;

    /** @var TokenAccessorInterface */
    private $tokenAccessor;

    /** @var bool */
    private $enabled = false;

    public function __construct(
        AclConditionDataBuilderInterface $builder,
        DraftPermissionHelper $draftPermissionHelper,
        TokenAccessorInterface $tokenAccessor
    ) {
        $this->builder = $builder;
        $this->draftPermissionHelper = $draftPermissionHelper;
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * @param boolean $enabled
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function isApplicable(Criteria $criteria): bool
    {
        $entityClass = $criteria->getEntityClass();
        $permission = $criteria->getPermission();

        return $this->enabled
            && is_a($entityClass, DraftableInterface::class, true)
            && BasicPermission::VIEW === $permission;
    }

    public function process(Criteria $criteria): void
    {
        $entityClass = $criteria->getEntityClass();
        $permission = $criteria->getPermission();
        $globalPermissionName = $this->draftPermissionHelper->generateGlobalPermission($permission);

        $conditionData = $this->builder->getAclConditionData($entityClass, $globalPermissionName);
        if (empty($conditionData)) {
            return;
        }

        [$ownerField, $ownerValue, $organizationField, $organizationValue, $ignoreOwner] = $conditionData;

        $alias = $criteria->getAlias();

        // No access
        if (null === $ownerField
            && null === $ownerValue
            && null === $organizationField
            && null === $organizationValue
        ) {
            // records that owned by current user should be always visible
            $criteria->setExpression($this->getConditionByCurrentUser($alias));

            return;
        }

        // Access to the organization
        if (null !== $organizationField && null !== $organizationValue) {
            $expression = new CompositeExpression(
                CompositeExpression::TYPE_OR,
                [
                    $this->getConditionByOrganization($alias),
                    $this->getConditionByCurrentUser($alias)
                ]
            );
            $criteria->orExpression($expression);
        }
    }

    private function getConditionByCurrentUser(string $alias): Comparison
    {
        return new Comparison(
            new Path('draftOwner', $alias),
            Comparison::EQ,
            $this->tokenAccessor->getUserId()
        );
    }

    private function getConditionByOrganization(string $alias): Comparison
    {
        return new Comparison(
            new Path('organization', $alias),
            Comparison::EQ,
            $this->tokenAccessor->getOrganizationId()
        );
    }
}

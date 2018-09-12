<?php

namespace Oro\Bundle\SecurityBundle\AccessRule;

use Oro\Bundle\SecurityBundle\AccessRule\Expr\AccessDenied;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Comparison;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Path;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclConditionDataBuilderInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;

/**
 * Limits entities that can be used as an owner by CREATE or ASSIGN permission.
 */
class AvailableOwnerAccessRule implements AccessRuleInterface
{
    /** The option that allows to enable the rule. Default value is false. */
    const ENABLE_RULE = 'availableOwnerEnable';

    /** The option that contains the target class name whose access level should be used for the check. */
    const TARGET_ENTITY_CLASS = 'availableOwnerTargetEntityClass';

    /** The option that contains the ID of owner that should be available even if ACL check denies access. */
    const CURRENT_OWNER = 'availableOwnerCurrentOwner';

    /** @var AclConditionDataBuilderInterface */
    private $builder;

    /** @var OwnershipMetadataProviderInterface */
    private $ownershipMetadataProvider;

    /**
     * @param AclConditionDataBuilderInterface $builder
     * @param OwnershipMetadataProviderInterface $ownershipMetadataProvider
     */
    public function __construct(
        AclConditionDataBuilderInterface $builder,
        OwnershipMetadataProviderInterface $ownershipMetadataProvider
    ) {
        $this->builder = $builder;
        $this->ownershipMetadataProvider = $ownershipMetadataProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(Criteria $criteria): bool
    {
        return
            $criteria->getOption(self::ENABLE_RULE, false)
            && ($criteria->getPermission() === 'ASSIGN' || $criteria->getPermission() === 'CREATE')
            && $criteria->isRoot()
            && $criteria->hasOption(self::TARGET_ENTITY_CLASS);
    }

    /**
     * {@inheritdoc}
     */
    public function process(Criteria $criteria): void
    {
        $alias = $criteria->getAlias();

        $conditionData = $this->builder->getAclConditionData(
            $criteria->getOption(self::TARGET_ENTITY_CLASS),
            $criteria->getPermission()
        );
        if (!empty($conditionData)) {
            list(, $ownerValue, , $organizationValue, $ignoreOwner) = $conditionData;
            if (!$ignoreOwner) {
                if (empty($ownerValue)) {
                    $criteria->andExpression(new AccessDenied());
                } else {
                    $criteria->andExpression(
                        new Comparison(
                            new Path('id', $alias),
                            is_array($ownerValue) ? Comparison::IN : Comparison::EQ,
                            $ownerValue
                        )
                    );
                }
            }

            if (null !== $organizationValue) {
                $metadata = $this->ownershipMetadataProvider->getMetadata($criteria->getEntityClass());
                $criteria->andExpression(
                    new Comparison(
                        new Path($metadata->getOrganizationFieldName(), $alias),
                        Comparison::EQ,
                        $organizationValue
                    )
                );
            }
        }

        if ($criteria->hasOption(self::CURRENT_OWNER)) {
            $criteria->orExpression(
                new Comparison(new Path('id', $alias), Comparison::EQ, $criteria->getOption(self::CURRENT_OWNER))
            );
        }
    }
}

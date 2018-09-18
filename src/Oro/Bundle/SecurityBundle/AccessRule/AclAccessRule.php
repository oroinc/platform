<?php

namespace Oro\Bundle\SecurityBundle\AccessRule;

use Oro\Bundle\SecurityBundle\AccessRule\Expr\AccessDenied;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Comparison;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Path;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclConditionDataBuilderInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;

/**
 * The access rule that adds ACL related access expressions.
 */
class AclAccessRule implements AccessRuleInterface
{
    /** The option that allows to disable the rule. Default value is false. */
    public const DISABLE_RULE = 'aclDisable';

    /** The option that enable checking of the current joined entity if it is the owner of a parent entity. */
    public const CHECK_OWNER = 'aclCheckOwner';

    /**
     * The option that contains the parent class name of the current joined entity.
     * This option is used together with the check owner option.
     */
    public const PARENT_CLASS = 'aclParentClass';

    /**
     * The option that contains the field name by which the current entity is joined.
     * This option is used together with the check owner option.
     */
    public const PARENT_FIELD = 'aclParentField';

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
        if ($criteria->getOption(self::DISABLE_RULE, false)) {
            return false;
        }

        // do not apply this rule for related entities that are owner for the root entity
        if (!$criteria->isRoot()
            && !$criteria->getOption(self::CHECK_OWNER, false)
            && $criteria->hasOption(self::PARENT_CLASS)
            && $criteria->hasOption(self::PARENT_FIELD)
        ) {
            $metadata = $this->ownershipMetadataProvider->getMetadata($criteria->getOption(self::PARENT_CLASS));
            if ($metadata->hasOwner() && $metadata->getOwnerFieldName() === $criteria->getOption(self::PARENT_FIELD)) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Criteria $criteria): void
    {
        $entityClass = $criteria->getEntityClass();

        $conditionData = $this->builder->getAclConditionData($entityClass, $criteria->getPermission());
        if (empty($conditionData)) {
            return;
        }

        list($ownerField, $ownerValue, $organizationField, $organizationValue, $ignoreOwner) = $conditionData;
        $alias = $criteria->getAlias();

        if (!$ignoreOwner && empty($ownerValue)) {
            $criteria->andExpression(new AccessDenied());

            return;
        }

        if (!$ignoreOwner) {
            $criteria->andExpression(
                new Comparison(
                    new Path($ownerField, $alias),
                    is_array($ownerValue) ? Comparison::IN : Comparison::EQ,
                    $ownerValue
                )
            );
        }

        if (null !== $organizationField && null !== $organizationValue) {
            $criteria->andExpression(
                new Comparison(new Path($organizationField, $alias), Comparison::EQ, $organizationValue)
            );
        }
    }
}

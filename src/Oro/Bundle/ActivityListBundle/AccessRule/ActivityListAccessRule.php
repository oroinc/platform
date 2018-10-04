<?php

namespace Oro\Bundle\ActivityListBundle\AccessRule;

use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Model\ActivityListProviderInterface;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\SecurityBundle\AccessRule\AccessRuleInterface;
use Oro\Bundle\SecurityBundle\AccessRule\Criteria;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Comparison;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\CompositeExpression;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\NullComparison;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Path;
use Oro\Bundle\SecurityBundle\ORM\Walker\AccessRuleWalker;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclConditionDataBuilderInterface;

/**
 * Access rule that protects ActivityList entity through joined ActivityOwner entity.
 * In other words activity list items are filtered by accessible activities.
 */
class ActivityListAccessRule implements AccessRuleInterface
{
    /**
     * The option that contains the alias of joined Oro\Bundle\ActivityListBundle\Entity\ActivityOwner.
     * All security checks for ActivityList entity will be applied to this entity.
     */
    public const ACTIVITY_OWNER_TABLE_ALIAS = 'activityListActivityOwnerTableAlias';

    /** @var AclConditionDataBuilderInterface */
    private $builder;

    /** @var ActivityListChainProvider */
    private $activityListProvider;

    /**
     * @param AclConditionDataBuilderInterface $builder
     * @param ActivityListChainProvider $activityListProvider
     */
    public function __construct(
        AclConditionDataBuilderInterface $builder,
        ActivityListChainProvider $activityListProvider
    ) {
        $this->builder = $builder;
        $this->activityListProvider = $activityListProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(Criteria $criteria): bool
    {
        return
            $criteria->getType() === AccessRuleWalker::ORM_RULES_TYPE
            && $criteria->getEntityClass() === ActivityList::class
            // This check should be deleted in BAP-17679
            && $criteria->hasOption(self::ACTIVITY_OWNER_TABLE_ALIAS);
    }

    /**
     * {@inheritdoc}
     */
    public function process(Criteria $criteria): void
    {
        if (!$criteria->hasOption(self::ACTIVITY_OWNER_TABLE_ALIAS)) {
            throw new \LogicException(sprintf(
                'The "%s" option was not set to ActivityListAccessRule access rule.',
                self::ACTIVITY_OWNER_TABLE_ALIAS
            ));
        }

        $activityOwnerAlias = $criteria->getOption(self::ACTIVITY_OWNER_TABLE_ALIAS);
        $expressions = [];

        $providers = $this->activityListProvider->getProviders();
        /** @var ActivityListProviderInterface $provider */
        foreach ($providers as $provider) {
            $providerExpressions = [];
            $activityClass = $provider->getActivityClass();
            $aclClass = $provider->getAclClass();
            $conditionData = $this->builder->getAclConditionData($aclClass, $criteria->getPermission());

            if (!empty($conditionData)) {
                list(, $ownerValue, , $organizationValue, $ignoreOwner) = $conditionData;
                if (!$ignoreOwner) {
                    if (null === $ownerValue) {
                        continue;
                    }
                    $providerExpressions[] = new Comparison(
                        new Path('user', $activityOwnerAlias),
                        is_array($ownerValue) ? Comparison::IN : Comparison::EQ,
                        $ownerValue
                    );
                }

                if (null !== $organizationValue) {
                    $providerExpressions[] = new Comparison(
                        new Path('organization', $activityOwnerAlias),
                        Comparison::EQ,
                        $organizationValue
                    );
                }
            }
            $providerExpressions[] = new Comparison(new Path('relatedActivityClass'), Comparison::EQ, $activityClass);
            $expressions[] = new CompositeExpression(CompositeExpression::TYPE_AND, $providerExpressions);
        }

        $expressions[] = new CompositeExpression(
            CompositeExpression::TYPE_AND,
            [
                new NullComparison(new Path('user', $activityOwnerAlias)),
                new NullComparison(new Path('organization', $activityOwnerAlias)),
            ]
        );

        $expression = new CompositeExpression(CompositeExpression::TYPE_OR, $expressions);
        $criteria->andExpression($expression);
    }
}

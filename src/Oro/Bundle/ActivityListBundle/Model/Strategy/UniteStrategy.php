<?php

namespace Oro\Bundle\ActivityListBundle\Model\Strategy;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Helper\ActivityListAclCriteriaHelper;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityMergeBundle\Model\Strategy\StrategyInterface;
use Oro\Bundle\EntityMergeBundle\Data\FieldData;
use Oro\Bundle\EntityMergeBundle\Model\MergeModes;

/**
 * Class UniteStrategy
 * @package Oro\Bundle\ActivityListBundle\Model\Strategy
 */
class UniteStrategy implements StrategyInterface
{
    /** @var ActivityManager  */
    protected $activityManager;

    /** @var DoctrineHelper  */
    protected $doctrineHelper;

    /** @var ActivityListAclCriteriaHelper */
    protected $activityListAclHelper;

    /** @var ActivityListChainProvider */
    protected $activityListProvider;

    /**
     * @param ActivityManager               $activityManager
     * @param DoctrineHelper                $doctrineHelper
     * @param ActivityListAclCriteriaHelper $activityListAclCriteriaHelper
     * @param ActivityListChainProvider     $activityListChainProvider
     */
    public function __construct(
        ActivityManager $activityManager,
        DoctrineHelper $doctrineHelper,
        ActivityListAclCriteriaHelper $activityListAclCriteriaHelper,
        ActivityListChainProvider $activityListChainProvider
    ) {
        $this->activityManager = $activityManager;
        $this->doctrineHelper = $doctrineHelper;
        $this->activityListAclHelper = $activityListAclCriteriaHelper;
        $this->activityListProvider = $activityListChainProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function merge(FieldData $fieldData)
    {
        $entityData    = $fieldData->getEntityData();
        $masterEntity  = $entityData->getMasterEntity();
        $sourceEntity  = $fieldData->getSourceEntity();
        $fieldMetadata = $fieldData->getMetadata();

        $entityClass = get_class($sourceEntity);
        $activityClass = $fieldMetadata->get('type');
        $queryBuilder = $this->doctrineHelper
            ->getEntityRepository(ActivityList::ENTITY_NAME)
            ->getBaseActivityListQueryBuilder($entityClass, $sourceEntity->getId())
            ->andWhere('activity.relatedActivityClass = :activityClass')
            ->setParameter('activityClass', $activityClass);

        $this->activityListAclHelper->applyAclCriteria($queryBuilder, $this->activityListProvider->getProviders());
        /** @var ActivityList[] $activities */
        $activities = $queryBuilder->getQuery()->getResult();

        foreach ($activities as $activityListItem) {
            $activity = $this->doctrineHelper->getEntityRepository($activityListItem->getRelatedActivityClass())
                ->find($activityListItem->getRelatedActivityId());
            $this->activityManager->replaceActivityTarget($activity, $sourceEntity, $masterEntity);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supports(FieldData $fieldData)
    {
        return $fieldData->getMode() === MergeModes::UNITE;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'unite';
    }
}

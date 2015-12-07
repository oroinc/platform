<?php

namespace Oro\Bundle\ActivityListBundle\Model\Strategy;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityMergeBundle\Data\FieldData;
use Oro\Bundle\EntityMergeBundle\Model\MergeModes;
use Oro\Bundle\EntityMergeBundle\Model\Strategy\StrategyInterface;

/**
 * Class ReplaceStrategy
 * @package Oro\Bundle\ActivityListBundle\Model\Strategy
 */
class ReplaceStrategy implements StrategyInterface
{
    /** @var ActivityManager  */
    protected $activityManager;

    /** @var DoctrineHelper  */
    protected $doctrineHelper;

    /**
     * @param ActivityManager $activityManager
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(ActivityManager $activityManager, DoctrineHelper $doctrineHelper)
    {
        $this->activityManager = $activityManager;
        $this->doctrineHelper = $doctrineHelper;
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

        $activityClass = $fieldMetadata->get('type');

        $activityListItems = $this->getActivityListByEntity($masterEntity, $activityClass);
        foreach ($activityListItems as $activityListItem) {
            $activity = $this->doctrineHelper->getEntityRepository($activityListItem->getRelatedActivityClass())
                ->find($activityListItem->getRelatedActivityId());
            $this->activityManager->removeActivityTarget($activity, $masterEntity);
        }

        $activityListItems = $this->getActivityListByEntity($sourceEntity, $activityClass);
        foreach ($activityListItems as $activityListItem) {
            $activity = $this->doctrineHelper->getEntityRepository($activityListItem->getRelatedActivityClass())
                ->find($activityListItem->getRelatedActivityId());
            $this->activityManager->replaceActivityTarget($activity, $sourceEntity, $masterEntity);
        }
    }

    /**
     * @param $entity
     * @param $activityClass
     * @return mixed
     */
    protected function getActivityListByEntity($entity, $activityClass)
    {
        $entityClass = get_class($entity);
        $queryBuilder = $this->doctrineHelper
            ->getEntityRepository(ActivityList::ENTITY_NAME)
            ->getBaseActivityListQueryBuilder($entityClass, $entity->getId())
            ->andWhere('activity.relatedActivityClass = :activityClass')
            ->setParameter('activityClass', $activityClass);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function supports(FieldData $fieldData)
    {
        return $fieldData->getMode() === MergeModes::REPLACE;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'replace';
    }
}

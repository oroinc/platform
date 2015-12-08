<?php

namespace Oro\Bundle\ActivityListBundle\Model\Strategy;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Model\MergeModes;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityMergeBundle\Data\FieldData;
use Oro\Bundle\EntityMergeBundle\Model\Strategy\StrategyInterface;
use Symfony\Component\Security\Core\Util\ClassUtils;

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

        $activities = $this->getActivitiesByEntity($masterEntity, $activityClass);
        foreach ($activities as $activity) {
            $this->activityManager->removeActivityTarget($activity, $masterEntity);
        }

        $activities = $this->getActivitiesByEntity($sourceEntity, $activityClass);
        foreach ($activities as $activity) {
            $this->activityManager->replaceActivityTarget($activity, $sourceEntity, $masterEntity);
        }
    }

    /**
     * @param $entity
     * @param $activityClass
     * @return mixed
     */
    protected function getActivitiesByEntity($entity, $activityClass)
    {
        $entityClass = ClassUtils::getRealClass($entity);
        $queryBuilder = $this->doctrineHelper
            ->getEntityRepository(ActivityList::ENTITY_NAME)
            ->getBaseActivityListQueryBuilder($entityClass, $entity->getId())
            ->andWhere('activity.relatedActivityClass = :activityClass')
            ->setParameter('activityClass', $activityClass);

        $activityListItems = $queryBuilder->getQuery()->getResult();
        $activityIds = [];
        foreach ($activityListItems as $activityListItem) {
            $activityIds[] = $activityListItem->getRelatedActivityId();
        }

        return $this->doctrineHelper->getEntityRepository($activityClass)->findBy(['id' => $activityIds]);
    }

    /**
     * {@inheritdoc}
     */
    public function supports(FieldData $fieldData)
    {
        return $fieldData->getMode() === MergeModes::ACTIVITY_REPLACE;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'activity_replace';
    }
}

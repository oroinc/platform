<?php

namespace Oro\Bundle\ActivityListBundle\Model\Strategy;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Model\MergeModes;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityMergeBundle\Model\Strategy\StrategyInterface;
use Oro\Bundle\EntityMergeBundle\Data\FieldData;

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
        $fieldMetadata = $fieldData->getMetadata();

        $entities = $fieldData->getEntityData()->getEntities();
        foreach ($entities as $sourceEntity) {
            if ($sourceEntity->getId() !== $masterEntity->getId()) {
                $entityClass = get_class($sourceEntity);
                $activityClass = $fieldMetadata->get('type');
                $queryBuilder = $this->doctrineHelper
                    ->getEntityRepository(ActivityList::ENTITY_NAME)
                    ->getBaseActivityListQueryBuilder($entityClass, $sourceEntity->getId())
                    ->andWhere('activity.relatedActivityClass = :activityClass')
                    ->setParameter('activityClass', $activityClass);

                /** @var ActivityList[] $activities */
                $activities = $queryBuilder->getQuery()->getResult();

                foreach ($activities as $activityListItem) {
                    $activity = $this->doctrineHelper->getEntityRepository($activityListItem->getRelatedActivityClass())
                        ->find($activityListItem->getRelatedActivityId());
                    $this->activityManager->replaceActivityTarget($activity, $sourceEntity, $masterEntity);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supports(FieldData $fieldData)
    {
        return $fieldData->getMode() === MergeModes::ACTIVITY_UNITE;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'activity_unite';
    }
}

<?php

namespace Oro\Bundle\ActivityListBundle\Model\Strategy;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Entity\Manager\ActivityListManager;
use Oro\Bundle\ActivityListBundle\Model\MergeModes;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityMergeBundle\Data\FieldData;
use Oro\Bundle\EntityMergeBundle\Model\Strategy\StrategyInterface;
use Symfony\Component\Security\Acl\Util\ClassUtils;

/**
 * Realization of EntityMergeBundle FieldData merge StrategyInterface
 * Does db update plain SQL query, using activityListManager
 * updates targetField of association (has FieldData.sourceEntity.id) with FieldData.masterEntity.id
 */
class ReplaceStrategy implements StrategyInterface
{
    /** @var ActivityListManager  */
    protected $activityListManager;

    /** @var DoctrineHelper  */
    protected $doctrineHelper;

    /** @var ActivityManager  */
    protected $activityManager;

    /**
     * @param ActivityListManager $activityListManager
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        ActivityListManager $activityListManager,
        DoctrineHelper $doctrineHelper,
        ActivityManager $activityManager
    ) {
        $this->activityListManager = $activityListManager;
        $this->doctrineHelper = $doctrineHelper;
        $this->activityManager = $activityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function merge(FieldData $fieldData)
    {
        $entityData    = $fieldData->getEntityData();
        $masterEntity  = $entityData->getMasterEntity();
        $sourceEntity  = $fieldData->getSourceEntity();

        if ($masterEntity->getId() !== $sourceEntity->getId()) {
            $fieldMetadata = $fieldData->getMetadata();

            $activityClass = $fieldMetadata->get('type');

            $activityListItems = $this->getActivitiesByEntity($masterEntity, $activityClass);
            $activityIds = \array_column($activityListItems, 'relatedActivityId');

            $activities = $this->doctrineHelper->getEntityRepository($activityClass)->findBy(['id' => $activityIds]);
            foreach ($activities as $activity) {
                $this->activityManager->removeActivityTarget($activity, $masterEntity);
            }

            $activityListItems = $this->getActivitiesByEntity($sourceEntity, $activityClass);

            $activityIds = \array_column($activityListItems, 'id');
            $entityClass = ClassUtils::getRealClass($masterEntity);
            $this->activityListManager
                ->replaceActivityTargetWithPlainQuery(
                    $activityIds,
                    $entityClass,
                    $sourceEntity->getId(),
                    $masterEntity->getId()
                );

            $activityIds = \array_column($activityListItems, 'relatedActivityId');
            $this->activityListManager
                ->replaceActivityTargetWithPlainQuery(
                    $activityIds,
                    $entityClass,
                    $sourceEntity->getId(),
                    $masterEntity->getId(),
                    $activityClass
                );
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
            ->getActivityListQueryBuilderByActivityClass($entityClass, $entity->getId(), $activityClass);

        return $queryBuilder->getQuery()->getResult();
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

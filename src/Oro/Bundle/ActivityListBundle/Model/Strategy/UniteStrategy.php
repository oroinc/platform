<?php

namespace Oro\Bundle\ActivityListBundle\Model\Strategy;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Model\MergeModes;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityMergeBundle\Model\Strategy\StrategyInterface;
use Oro\Bundle\EntityMergeBundle\Data\FieldData;
use Symfony\Component\Security\Core\Util\ClassUtils;

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
                $entityClass = ClassUtils::getRealClass($masterEntity);
                $activityClass = $fieldMetadata->get('type');
                $queryBuilder = $this->doctrineHelper
                    ->getEntityRepository(ActivityList::ENTITY_NAME)
                    ->getBaseActivityListQueryBuilder($entityClass, $sourceEntity->getId());
                $queryBuilder->select('activity.relatedActivityId')
                    ->andWhere('activity.relatedActivityClass = :activityClass')
                    ->setParameter('activityClass', $activityClass);

                $activityListItems = $queryBuilder->getQuery()->getResult();
                $activityIds = array_column($activityListItems, 'relatedActivityId');

                $activities = $this->doctrineHelper
                    ->getEntityRepository($activityClass)
                    ->findBy(['id' => $activityIds]);

                foreach ($activities as $activity) {
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

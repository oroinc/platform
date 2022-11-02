<?php

namespace Oro\Bundle\TestFrameworkBundle\Provider;

use Oro\Bundle\ActivityBundle\Tools\ActivityAssociationHelper;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Entity\ActivityOwner;
use Oro\Bundle\ActivityListBundle\Model\ActivityListProviderInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;

/**
 * Provides a way to use TestActivity entity in an activity list.
 */
class TestActivityActivityListProvider implements ActivityListProviderInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ActivityAssociationHelper */
    protected $activityAssociationHelper;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        ActivityAssociationHelper $activityAssociationHelper
    ) {
        $this->doctrineHelper            = $doctrineHelper;
        $this->activityAssociationHelper = $activityAssociationHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicableTarget($entityClass, $accessible = true)
    {
        return $this->activityAssociationHelper->isActivityAssociationEnabled(
            $entityClass,
            TestActivity::class,
            $accessible
        );
    }

    /**
     * {@inheritdoc}
     * @param TestActivity $entity
     */
    public function getSubject($entity)
    {
        return $entity->getMessage();
    }

    /**
     * {@inheritdoc}
     * @param TestActivity $entity
     */
    public function getDescription($entity)
    {
        return $entity->getDescription();
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ActivityList $activityList)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getOwner($entity)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     * @param TestActivity $entity
     */
    public function getOrganization($entity)
    {
        return $entity->getOrganization();
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return '@OroActivityList/ActivityList/js/activityItemTemplate.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutes($entity)
    {
        return [
            'itemView'   => '',
            'itemEdit'   => '',
            'itemDelete' => ''
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getActivityId($entity)
    {
        return $this->doctrineHelper->getSingleEntityIdentifier($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable($entity)
    {
        if (\is_object($entity)) {
            return $entity instanceof TestActivity;
        }

        return $entity === TestActivity::class;
    }

    /**
     * {@inheritdoc}
     * @param TestActivity $entity
     */
    public function getTargetEntities($entity)
    {
        return $entity->getActivityTargets();
    }

    /**
     * {@inheritdoc}
     * @param TestActivity $entity
     */
    public function getActivityOwners($entity, ActivityList $activityList)
    {
        $organization = $this->getOrganization($entity);
        $owner = $entity->getOwner();

        if (!$organization || !$owner) {
            return [];
        }

        $activityOwner = new ActivityOwner();
        $activityOwner->setActivity($activityList);
        $activityOwner->setOrganization($organization);
        $activityOwner->setUser($owner);

        return [$activityOwner];
    }

    /**
     * {@inheritDoc}
     */
    public function isActivityListApplicable(ActivityList $activityList): bool
    {
        return true;
    }
}

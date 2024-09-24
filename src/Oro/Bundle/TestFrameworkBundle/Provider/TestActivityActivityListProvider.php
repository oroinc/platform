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

    #[\Override]
    public function isApplicableTarget($entityClass, $accessible = true)
    {
        return $this->activityAssociationHelper->isActivityAssociationEnabled(
            $entityClass,
            TestActivity::class,
            $accessible
        );
    }

    /**
     * @param TestActivity $entity
     */
    #[\Override]
    public function getSubject($entity)
    {
        return $entity->getMessage();
    }

    /**
     * @param TestActivity $entity
     */
    #[\Override]
    public function getDescription($entity)
    {
        return $entity->getDescription();
    }

    #[\Override]
    public function getData(ActivityList $activityList)
    {
        return [];
    }

    #[\Override]
    public function getOwner($entity)
    {
        return null;
    }

    /**
     * @param TestActivity $entity
     */
    #[\Override]
    public function getOrganization($entity)
    {
        return $entity->getOrganization();
    }

    #[\Override]
    public function getTemplate()
    {
        return '@OroActivityList/ActivityList/js/activityItemTemplate.html.twig';
    }

    #[\Override]
    public function getRoutes($entity)
    {
        return [
            'itemView'   => '',
            'itemEdit'   => '',
            'itemDelete' => ''
        ];
    }

    #[\Override]
    public function getActivityId($entity)
    {
        return $this->doctrineHelper->getSingleEntityIdentifier($entity);
    }

    #[\Override]
    public function isApplicable($entity)
    {
        if (\is_object($entity)) {
            return $entity instanceof TestActivity;
        }

        return $entity === TestActivity::class;
    }

    /**
     * @param TestActivity $entity
     */
    #[\Override]
    public function getTargetEntities($entity)
    {
        return $entity->getActivityTargets();
    }

    /**
     * @param TestActivity $entity
     */
    #[\Override]
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

    #[\Override]
    public function isActivityListApplicable(ActivityList $activityList): bool
    {
        return true;
    }
}

<?php

namespace Oro\Bundle\TestFrameworkBundle\Provider;

use Oro\Bundle\ActivityBundle\Tools\ActivityAssociationHelper;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Entity\ActivityOwner;
use Oro\Bundle\ActivityListBundle\Model\ActivityListProviderInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;

class TestActivityActivityListProvider implements ActivityListProviderInterface
{
    const ACTIVITY_CLASS = 'Oro\Bundle\TestFrameworkBundle\Entity\TestActivity';
    const ACL_CLASS = 'Oro\Bundle\TestFrameworkBundle\Entity\TestActivity';

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ActivityAssociationHelper */
    protected $activityAssociationHelper;

    /**
     * @param DoctrineHelper            $doctrineHelper
     * @param ActivityAssociationHelper $activityAssociationHelper
     */
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
            self::ACTIVITY_CLASS,
            $accessible
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getSubject($entity)
    {
        /** @var $entity TestActivity */
        return $entity->getMessage();
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription($entity)
    {
        /** @var $entity TestActivity */
        return $entity->getDescription();
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ActivityList $activityListEntity)
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
     */
    public function getOrganization($activityEntity)
    {
        /** @var $activityEntity TestActivity */
        return $activityEntity->getOrganization();
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return 'OroActivityListBundle:ActivityList/js:activityItemTemplate.js.twig';
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutes()
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
    public function getActivityClass()
    {
        return self::ACTIVITY_CLASS;
    }

    /**
     * {@inheritdoc}
     */
    public function getAclClass()
    {
        return self::ACL_CLASS;
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
        if (is_object($entity)) {
            $entity = $this->doctrineHelper->getEntityClass($entity);
        }

        return $entity === self::ACTIVITY_CLASS;
    }

    /**
     * {@inheritdoc}
     */
    public function getTargetEntities($entity)
    {
        return $entity->getActivityTargetEntities();
    }

    /**
     * {@inheritdoc}
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
}

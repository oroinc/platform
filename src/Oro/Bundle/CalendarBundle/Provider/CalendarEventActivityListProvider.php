<?php

namespace Oro\Bundle\CalendarBundle\Provider;

use Oro\Bundle\ActivityBundle\Tools\ActivityAssociationHelper;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Model\ActivityListProviderInterface;
use Oro\Bundle\ActivityListBundle\Entity\ActivityOwner;
use Oro\Bundle\ActivityListBundle\Model\ActivityListDateProviderInterface;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CommentBundle\Model\CommentProviderInterface;
use Oro\Bundle\CommentBundle\Tools\CommentAssociationHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UserBundle\Entity\User;

class CalendarEventActivityListProvider implements
    ActivityListProviderInterface,
    CommentProviderInterface,
    ActivityListDateProviderInterface
{
    const ACTIVITY_CLASS = 'Oro\Bundle\CalendarBundle\Entity\CalendarEvent';
    const ACL_CLASS = 'Oro\Bundle\CalendarBundle\Entity\CalendarEvent';

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ActivityAssociationHelper */
    protected $activityAssociationHelper;

    /** @var CommentAssociationHelper */
    protected $commentAssociationHelper;

    /**
     * @param DoctrineHelper            $doctrineHelper
     * @param ActivityAssociationHelper $activityAssociationHelper
     * @param CommentAssociationHelper  $commentAssociationHelper
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ActivityAssociationHelper $activityAssociationHelper,
        CommentAssociationHelper $commentAssociationHelper
    ) {
        $this->doctrineHelper            = $doctrineHelper;
        $this->activityAssociationHelper = $activityAssociationHelper;
        $this->commentAssociationHelper  = $commentAssociationHelper;
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
    public function getRoutes()
    {
        return [
            'itemView'   => 'oro_calendar_event_widget_info',
            'itemEdit'   => 'oro_calendar_event_update',
            'itemDelete' => 'oro_calendar_event_delete'
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
    public function getSubject($entity)
    {
        /** @var $entity CalendarEvent */
        return $entity->getTitle();
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription($entity)
    {
        /** @var $entity CalendarEvent */
        return $entity->getDescription();
    }

    /**
     * {@inheritdoc}
     */
    public function getCreatedAt($entity)
    {
        /** @var $entity CalendarEvent */
        return $entity->getCreatedAt();
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdatedAt($entity)
    {
        /** @var $entity CalendarEvent */
        return $entity->getUpdatedAt();
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
    public function getOrganization($activityEntity)
    {
        /** @var $activityEntity CalendarEvent */
        if ($activityEntity->getCalendar()) {
            return $activityEntity->getCalendar()->getOrganization();
        } elseif ($activityEntity->getSystemCalendar()) {
            return $activityEntity->getSystemCalendar()->getOrganization();
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return 'OroCalendarBundle:CalendarEvent:js/activityItemTemplate.js.twig';
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

        return $entity == self::ACTIVITY_CLASS;
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
    public function isCommentsEnabled($entityClass)
    {
        return $this->commentAssociationHelper->isCommentAssociationEnabled($entityClass);
    }

    /**
     * {@inheritdoc}
     */
    public function getActivityOwners($entity, ActivityList $activityList)
    {
        $organization = $this->getOrganization($entity);
        $owner = $this->getOwner($entity);

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
     * Get calendar owner
     *
     * @param CalendarEvent $activityEntity
     * @return null|User
     */
    public function getOwner($activityEntity)
    {
        /** @var $activityEntity CalendarEvent */
        if ($activityEntity->getCalendar()) {
            return $activityEntity->getCalendar()->getOwner();
        }
        return null;
    }
}

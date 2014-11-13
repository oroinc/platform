<?php

namespace Oro\Bundle\CalendarBundle\Provider;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ActivityListBundle\Model\ActivityListProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;

class CalendarEventActivityListProvider implements ActivityListProviderInterface
{
    const ACTIVITY_CLASS = 'Oro\Bundle\CalendarBundle\Entity\CalendarEvent';

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicableTarget(ConfigIdInterface $configId, ConfigManager $configManager)
    {
        $provider = $configManager->getProvider('activity');
        return $provider->hasConfigById($configId) && $provider->getConfigById($configId)->has('activities');
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutes()
    {
        return [
            'itemEdit'   => 'oro_calendar_event_update',
            'itemDelete' => 'oro_api_delete_calendarevent'
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
    public function getSubject($entity)
    {
        /** @var $entity CalendarEvent */
        return $entity->getTitle();
    }

    /**
     * {@inheritdoc}
     */
    public function getData($entity)
    {
        /** @var CalendarEvent $entity */
        return [];
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
}

<?php

namespace Oro\Bundle\CalendarBundle\Manager;

use Oro\Bundle\CalendarBundle\Entity\CalendarProperty;
use Oro\Bundle\CalendarBundle\Provider\CalendarProviderInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\UIBundle\Tools\ArrayUtils;

class CalendarManager
{
    const CALENDAR_PROPERTY_CLASS = 'Oro\Bundle\CalendarBundle\Entity\CalendarProperty';

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ConfigManager */
    protected $configManager;

    /** @var CalendarProviderInterface[] */
    protected $providers = [];

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ConfigManager  $configManager
     */
    public function __construct(DoctrineHelper $doctrineHelper, ConfigManager $configManager)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->configManager  = $configManager;
    }

    /**
     * Registers the given provider in the chain
     *
     * @param string                    $alias
     * @param CalendarProviderInterface $provider
     */
    public function addProvider($alias, CalendarProviderInterface $provider)
    {
        $this->providers[$alias] = $provider;
    }

    /**
     * @param int $userId
     * @param int $calendarId
     *
     * @return array
     */
    public function getCalendars($userId, $calendarId)
    {
        // make sure input parameters have proper type
        $userId     = (int)$userId;
        $calendarId = (int)$calendarId;

        $result = $this->getCalendarProperties($calendarId);

        $existing = [];
        foreach ($result as $key => $item) {
            $existing[$item['calendarAlias']][$item['calendar']] = $key;
        }

        foreach ($this->providers as $alias => $provider) {
            $calendarIds           = isset($existing[$alias]) ? array_keys($existing[$alias]) : [];
            $calendarDefaultValues = $provider->getCalendarDefaultValues($userId, $calendarId, $calendarIds);
            foreach ($calendarDefaultValues as $calendarId => $values) {
                if (isset($existing[$alias][$calendarId])) {
                    $key      = $existing[$alias][$calendarId];
                    $calendar = $result[$key];
                    $this->applyCalendarDefaultValues($calendar, $values);
                    $result[$key] = $calendar;
                } else {
                    $values['calendarAlias'] = $alias;
                    $values['calendar']      = $calendarId;
                    $result[]                = $values;
                }
            }
        }

        $this->normalizeCalendarData($result);

        return $result;
    }

    /**
     * @param CalendarProperty $connection
     *
     * @return array
     */
    public function getCalendarInfo(CalendarProperty $connection)
    {
        $provider = $this->providers[$connection->getCalendarAlias()];

        return [
            'calendarName' => $provider->getCalendarName($connection)
        ];
    }

    /**
     * @param int       $calendarId
     * @param \DateTime $start
     * @param \DateTime $end
     * @param bool      $subordinate
     *
     * @return array
     */
    public function getCalendarEvents($calendarId, $start, $end, $subordinate)
    {
        // make sure input parameters have proper type
        $calendarId = (int)$calendarId;

        $result = [];

        foreach ($this->providers as $alias => $provider) {
            $events = $provider->getCalendarEvents($calendarId, $start, $end, $subordinate);
            if (!empty($events)) {
                foreach ($events as &$event) {
                    $event['calendarAlias'] = $alias;
                }
                $result = array_merge($result, $events);
            }
        }

        return $result;
    }

    /**
     * @param int $calendarId
     *
     * @return array
     */
    protected function getCalendarProperties($calendarId)
    {
        $repo = $this->doctrineHelper->getEntityRepository(self::CALENDAR_PROPERTY_CLASS);
        $qb   = $repo->createQueryBuilder('o')
            ->where('o.targetCalendar = :calendar_id')
            ->setParameter('calendar_id', $calendarId)
            ->orderBy('o.id');

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @param array $calendars
     */
    protected function normalizeCalendarData(array &$calendars)
    {
        // apply default values and remove redundant properties
        $defaultValues = $this->getCalendarDefaultValues();
        foreach ($calendars as &$calendar) {
            $this->applyCalendarDefaultValues($calendar, $defaultValues);
            $this->removeCalendarRedundantProperties($calendar, $defaultValues);
        }

        ArrayUtils::sortBy($calendars, false, 'position');
    }

    /**
     * @param array $calendar
     * @param array $defaultValues
     */
    protected function applyCalendarDefaultValues(array &$calendar, array $defaultValues)
    {
        foreach ($defaultValues as $key => $val) {
            if (!isset($calendar[$key]) && !array_key_exists($key, $calendar)) {
                $calendar[$key] = $val;
            }
        }
    }

    /**
     * @param array $calendar
     * @param array $defaultValues
     */
    protected function removeCalendarRedundantProperties(array &$calendar, array $defaultValues)
    {
        foreach (array_keys($calendar) as $key) {
            if (!isset($defaultValues[$key]) && !array_key_exists($key, $defaultValues)) {
                unset($calendar[$key]);
            }
        }
    }

    /**
     * @return array
     */
    protected function getCalendarDefaultValues()
    {
        $metadata = $this->doctrineHelper->getEntityMetadata(self::CALENDAR_PROPERTY_CLASS);
        /** @var FieldConfigId[] $fieldIds */
        $fieldIds = $this->configManager->getIds('extend', self::CALENDAR_PROPERTY_CLASS);

        $result = [];
        foreach ($fieldIds as $fieldId) {
            $fieldName    = $fieldId->getFieldName();
            $defaultValue = null;
            if ($metadata->hasField($fieldName)) {
                $mapping      = $metadata->getFieldMapping($fieldName);
                $defaultValue = isset($mapping['options']['default']) ? $mapping['options']['default'] : null;
            }
            $result[$fieldName] = $defaultValue;
        }

        $result['calendarName'] = null;
        $result['removable']    = true;

        return $result;
    }
}

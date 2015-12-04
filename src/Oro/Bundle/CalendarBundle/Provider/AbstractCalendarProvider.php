<?php

namespace Oro\Bundle\CalendarBundle\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

abstract class AbstractCalendarProvider implements CalendarProviderInterface
{

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @param DoctrineHelper $doctrineHelper */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    abstract public function getCalendarDefaultValues($organizationId, $userId, $calendarId, array $calendarIds);

    /**
     * {@inheritdoc}
     */
    abstract public function getCalendarEvents($organizationId, $userId, $calendarId, $start, $end, $connections, $extraField);

    /**
     * @param string $className
     *
     * @return array
     */
    protected function getSupportedFields($className)
    {
        $classMetadata = $this->doctrineHelper->getEntityMetadata($className);

        return $classMetadata->fieldNames;
    }
}

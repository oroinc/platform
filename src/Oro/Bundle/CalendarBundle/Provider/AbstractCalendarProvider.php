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
     * @param string $className
     *
     * @return array
     */
    protected function getSupportedFields($className)
    {
        $classMetadata = $this->doctrineHelper->getEntityMetadata($className);

        return $classMetadata->fieldNames;
    }

    /**
     * @param        $extraFields
     *
     * @param string $class
     *
     * @return array
     */
    protected function filterSupportedFields($extraFields, $class)
    {
        $extraFields = !empty($extraFields)
            ? array_intersect($extraFields, $this->getSupportedFields($class))
            : [];

        return $extraFields;
    }
}

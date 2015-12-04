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
}

<?php

namespace Oro\Bundle\EntityBundle\ImportExport\EventListeners;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\ImportExport\Serializer\EntityFieldFallbackValueNormalizer;
use Oro\Bundle\ImportExportBundle\Event\LoadEntityRulesAndBackendHeadersEvent;

/**
 * This class adds headers for EntityFieldFallbackValue`s into exported file
 */
class EntityFieldFallbackValueHeadersListener
{
    /**
     * @param LoadEntityRulesAndBackendHeadersEvent $event
     */
    public function afterLoadEntityRulesAndBackendHeaders(LoadEntityRulesAndBackendHeadersEvent $event)
    {
        $header = ['value' => EntityFieldFallbackValueNormalizer::VIRTUAL_FIELD_NAME];
        if ($event->isFullData() &&
            $event->getEntityName() === EntityFieldFallbackValue::class &&
            !in_array($header, $event->getHeaders(), true)
        ) {
            $event->addHeader($header);
            $event->setRule(EntityFieldFallbackValueNormalizer::VIRTUAL_FIELD_NAME, $header);
        }
    }
}

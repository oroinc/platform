<?php

namespace Oro\Bundle\EntityBundle\ImportExport\EventListeners;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\ImportExport\Serializer\EntityFieldFallbackValueNormalizer;
use Oro\Bundle\ImportExportBundle\Converter\ConfigurableTableDataConverter;
use Oro\Bundle\ImportExportBundle\Event\LoadEntityRulesAndBackendHeadersEvent;
use Oro\Bundle\ImportExportBundle\EventListener\ImportExportHeaderModifier;

/**
 * This class adds headers for EntityFieldFallbackValue`s into exported file
 */
class EntityFieldFallbackValueHeadersListener
{
    public function afterLoadEntityRulesAndBackendHeaders(LoadEntityRulesAndBackendHeadersEvent $event)
    {
        // arrayValue for 10001, fallback for 10002, id for 10003, scalarValue for 10004
        if (!$event->isFullData() ||
            $event->getEntityName() !== EntityFieldFallbackValue::class
        ) {
            return;
        }

        ImportExportHeaderModifier::addHeader(
            $event,
            EntityFieldFallbackValueNormalizer::VIRTUAL_FIELD_NAME,
            EntityFieldFallbackValueNormalizer::VIRTUAL_FIELD_NAME,
            ConfigurableTableDataConverter::DEFAULT_ORDER + 5
        );
    }
}

<?php

namespace Oro\Bundle\AttachmentBundle\ImportExport\EventListener;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\ImportExportBundle\Event\LoadEntityRulesAndBackendHeadersEvent;
use Oro\Bundle\ImportExportBundle\EventListener\ImportExportHeaderModifier;

/**
 * Adds "uri" rule and header for File after normalization.
 *
 * @see \Oro\Bundle\AttachmentBundle\ImportExport\FileNormalizer
 */
class FileHeadersListener
{
    public function afterLoadEntityRulesAndBackendHeaders(LoadEntityRulesAndBackendHeadersEvent $event): void
    {
        if ($event->getEntityName() !== File::class) {
            return;
        }

        ImportExportHeaderModifier::addHeader($event, 'uri', 'URI');
        $event->setRule('UUID', ['value' => 'uuid', 'order' => 30]);
    }
}

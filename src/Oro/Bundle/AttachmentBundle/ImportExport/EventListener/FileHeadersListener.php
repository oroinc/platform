<?php

namespace Oro\Bundle\AttachmentBundle\ImportExport\EventListener;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\ImportExportBundle\Event\LoadEntityRulesAndBackendHeadersEvent;

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

        $headers = array_column($event->getHeaders(), 'value');
        if (!in_array('uri', $headers, false)) {
            $event->addHeader(['value' => 'uri', 'order' => 20]);
            $event->setRule('URI', ['value' => 'uri', 'order' => 20]);
        }

        $event->setRule('UUID', ['value' => 'uuid', 'order' => 30]);
    }
}

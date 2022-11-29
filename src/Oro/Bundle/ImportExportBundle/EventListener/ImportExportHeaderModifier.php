<?php

namespace Oro\Bundle\ImportExportBundle\EventListener;

use Oro\Bundle\ImportExportBundle\Event\LoadEntityRulesAndBackendHeadersEvent;

/**
 * Class used to manipulate import-export headers.
 */
class ImportExportHeaderModifier
{
    protected const DEFAULT_ORDER = 20;

    public static function addHeader(
        LoadEntityRulesAndBackendHeadersEvent $event,
        string $value,
        string $label,
        int $order = self::DEFAULT_ORDER
    ): void {
        foreach ($event->getHeaders() as $header) {
            if ($header['value'] === $value) {
                return;
            }
        }

        $event->addHeader([
            'value' => $value,
            'order' => $order,
        ]);
        $event->setRule(
            $label,
            [
                'value' => $value,
                'order' => $order,
            ]
        );
    }
}

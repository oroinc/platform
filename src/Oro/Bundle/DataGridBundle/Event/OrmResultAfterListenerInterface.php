<?php

declare(strict_types=1);

namespace Oro\Bundle\DataGridBundle\Event;

/**
 * General interface for listeners to {@see OrmResultAfter} event.
 * Can be used to make lazy services for final listener classes that cannot be proxied due to missing interface.
 */
interface OrmResultAfterListenerInterface
{
    public function onResultAfter(OrmResultAfter $event): void;
}

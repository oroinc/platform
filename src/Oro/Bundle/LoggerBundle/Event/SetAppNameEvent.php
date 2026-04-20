<?php

namespace Oro\Bundle\LoggerBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched when trace ID changes
 * to notify subscribers to update PostgreSQL application_name
 */
class SetAppNameEvent extends Event
{
}

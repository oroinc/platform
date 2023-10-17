<?php

namespace Oro\Bundle\ConfigBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * The event that is fired when scope identifier are changed.
 */
class ConfigManagerScopeIdUpdateEvent extends Event
{
    public const EVENT_NAME = 'oro_config.config_manager_scope_id_change';
}

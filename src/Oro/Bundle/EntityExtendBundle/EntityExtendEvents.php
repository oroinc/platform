<?php

namespace Oro\Bundle\EntityExtendBundle;

/**
 * Defines event names for entity extend operations.
 */
final class EntityExtendEvents
{
    /**
     * The BEFORE_VALUE_RENDER event fire before value is rendered to template.
     * Instance of Oro\Bundle\EntityExtendBundle\Event\ValueRenderEvent is passed.
     */
    const BEFORE_VALUE_RENDER = 'oro.entity_extend_event.before_value_render';
}

<?php

namespace Oro\Bundle\EntityConfigBundle\Event;

/**
 * Provides the list of all events related to entity configuration.
 */
final class Events
{
    /**
     * This event occurs when a new configurable entity is found and its configuration attributes are loaded,
     * but before they are stored in a database.
     */
    const CREATE_ENTITY = 'oro.entity_config.entity.create';

    /**
     * This event occurs when default values of configuration attributes of existing entity are merged
     * with existing configuration data, but before they are stored in a database.
     */
    const UPDATE_ENTITY = 'oro.entity_config.entity.update';

    /**
     * This event occurs when a new configurable field is found and its configuration attributes are loaded,
     * but before they are stored in a database.
     */
    const CREATE_FIELD = 'oro.entity_config.field.create';

    /**
     * This event occurs when default values of configuration attributes of existing field are merged
     * with existing configuration data, but before they are stored in a database.
     */
    const UPDATE_FIELD = 'oro.entity_config.field.update';

    /**
     * This event occurs when the name of existing field is being changed.
     */
    const RENAME_FIELD = 'oro.entity_config.field.rename';

    /**
     * This event occurs before changes of configuration data is flushed into a database.
     */
    const PRE_FLUSH = 'oro.entity_config.pre_flush';

    /**
     * This event occurs after all changes of configuration data is flushed into a database.
     */
    const POST_FLUSH = 'oro.entity_config.post_flush';

    /**
     * Occurs before setting config state to Require update
     */
    const PRE_SET_REQUIRE_UPDATE = 'oro.entity_config.pre_set_require_update';
}

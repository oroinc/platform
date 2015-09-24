<?php

namespace Oro\Bundle\EntityConfigBundle\Event;

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

    /** @deprecated since 1.9. Use CREATE_ENTITY instead */
    const NEW_ENTITY_CONFIG = 'entity_config.new.entity.config';
    /** @deprecated since 1.9. Use UPDATE_ENTITY instead */
    const UPDATE_ENTITY_CONFIG = 'entity_config.update.entity.config';
    /** @deprecated since 1.9. Use CREATE_FIELD instead */
    const NEW_FIELD_CONFIG = 'entity_config.new.field.config';
    /** @deprecated since 1.9. Use UPDATE_FIELD instead */
    const UPDATE_FIELD_CONFIG = 'entity_config.update.field.config';
    /** @deprecated since 1.9. Use RENAME_FIELD instead */
    const RENAME_FIELD_OLD = 'entity_config.rename.field';
    /** @deprecated since 1.9. Use PRE_FLUSH instead */
    const PRE_PERSIST_CONFIG = 'entity_config.persist.config';
    /** @deprecated since 1.9. Use POST_FLUSH instead */
    const POST_FLUSH_CONFIG = 'entity_config.flush.config';
}

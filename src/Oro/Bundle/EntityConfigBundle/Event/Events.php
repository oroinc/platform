<?php

namespace Oro\Bundle\EntityConfigBundle\Event;

final class Events
{
    /**
     * Config Event Names
     */
    const NEW_ENTITY_CONFIG    = 'entity_config.new.entity.config';
    const UPDATE_ENTITY_CONFIG = 'entity_config.update.entity.config';
    const NEW_FIELD_CONFIG     = 'entity_config.new.field.config';
    const UPDATE_FIELD_CONFIG  = 'entity_config.update.field.config';
    const PRE_PERSIST_CONFIG   = 'entity_config.persist.config';
}

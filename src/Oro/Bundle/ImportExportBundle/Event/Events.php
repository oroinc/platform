<?php

namespace Oro\Bundle\ImportExportBundle\Event;

final class Events
{
    /**
     * This event occurs after the entity page is loaded in iterator.
     *
     * Can be used to modify rows.
     */
    const AFTER_ENTITY_PAGE_LOADED = 'oro.import_export.after_entity_page_loaded';

    /**
     * This event occurs before entity is normalized.
     *
     * Can be used to change entity data or prefill normalized data before normalization.
     */
    const BEFORE_NORMALIZE_ENTITY = 'oro.import_export.before_normalize_entity';

    /**
     * This event occurs after entity is normalized.
     *
     * Can be used to change normalized data.
     */
    const AFTER_NORMALIZE_ENTITY = 'oro.import_export.after_normalize_entity';

    /**
     * This event occurs after entity is denormalized.
     *
     * Can be used to change denormalized data.
     */
    const AFTER_DENORMALIZE_ENTITY = 'oro.import_export.after_denormalize_entity';

    /**
     * This event occurs after rules and backend headers are loaded.
     *
     * Can be used to modify, add new headers and modify rules.
     */
    const AFTER_LOAD_ENTITY_RULES_AND_BACKEND_HEADERS = 'oro.import_export.after_load_entity_rules_and_backend_headers';

    /**
     * This event occurs after template fixtures are loaded.
     *
     * Can be used to modify fixtures.
     */
    const AFTER_LOAD_TEMPLATE_FIXTURES = 'oro.import_export.after_load_template_fixtures';
}

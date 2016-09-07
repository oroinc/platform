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
     * This event occurs before entity is denormalized.
     *
     * Can be used to prefill denormalized data.
     */
    const BEFORE_DENORMALIZE_ENTITY = 'oro.import_export.before_denormalize_entity';

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

    /**
     * This event occurs before data is converted into export format.
     *
     * Can be used to modify record before conversion begins.
     */
    const BEFORE_EXPORT_FORMAT_CONVERSION = 'oro.import_export.before_export_format_conversion';

    /**
     * This event occurs after data is converted into export format.
     *
     * Can be used to modify result after conversion ends.
     */
    const AFTER_EXPORT_FORMAT_CONVERSION = 'oro.import_export.after_export_format_conversion';

    /**
     * This event occurs before data is converted into import format.
     *
     * Can be used to modify record before conversion begins.
     */
    const BEFORE_IMPORT_FORMAT_CONVERSION = 'oro.import_export.before_import_format_conversion';

    /**
     * This event occurs after data is converted into export format.
     *
     * Can be used to modify result after conversion ends.
     */
    const AFTER_IMPORT_FORMAT_CONVERSION = 'oro.import_export.after_import_format_conversion';

    /**
     * This event occurs after processing some job.
     *
     * Can be used to do some staff after job. For example, clean cache.
     */
    const AFTER_JOB_EXECUTION = 'oro.import_export.after_job_execution';
}

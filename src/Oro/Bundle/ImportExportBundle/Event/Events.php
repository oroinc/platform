<?php

namespace Oro\Bundle\ImportExportBundle\Event;

/**
 * Import/Export events
 */
final class Events
{
    /**
     * This event occurs after the entity page is loaded in iterator.
     *
     * Can be used to modify rows.
     */
    public const AFTER_ENTITY_PAGE_LOADED = 'oro.import_export.after_entity_page_loaded';

    /**
     * This event occurs before entity is normalized.
     *
     * Can be used to change entity data or prefill normalized data before normalization.
     */
    public const BEFORE_NORMALIZE_ENTITY = 'oro.import_export.before_normalize_entity';

    /**
     * This event occurs after entity is normalized.
     *
     * Can be used to change normalized data.
     */
    public const AFTER_NORMALIZE_ENTITY = 'oro.import_export.after_normalize_entity';

    /**
     * This event occurs before entity is denormalized.
     *
     * Can be used to prefill denormalized data.
     */
    public const BEFORE_DENORMALIZE_ENTITY = 'oro.import_export.before_denormalize_entity';

    /**
     * This event occurs after entity is denormalized.
     *
     * Can be used to change denormalized data.
     */
    public const AFTER_DENORMALIZE_ENTITY = 'oro.import_export.after_denormalize_entity';

    /**
     * This event occurs after rules and backend headers are loaded.
     *
     * Can be used to modify, add new headers and modify rules.
     */
    public const AFTER_LOAD_ENTITY_RULES_AND_BACKEND_HEADERS =
        'oro.import_export.after_load_entity_rules_and_backend_headers';

    /**
     * This event occurs after template fixtures are loaded.
     *
     * Can be used to modify fixtures.
     */
    public const AFTER_LOAD_TEMPLATE_FIXTURES = 'oro.import_export.after_load_template_fixtures';

    /**
     * This event occurs before data is converted into export format.
     *
     * Can be used to modify record before conversion begins.
     */
    public const BEFORE_EXPORT_FORMAT_CONVERSION = 'oro.import_export.before_export_format_conversion';

    /**
     * This event occurs after data is converted into export format.
     *
     * Can be used to modify result after conversion ends.
     */
    public const AFTER_EXPORT_FORMAT_CONVERSION = 'oro.import_export.after_export_format_conversion';

    /**
     * This event occurs before data is converted into import format.
     *
     * Can be used to modify record before conversion begins.
     */
    public const BEFORE_IMPORT_FORMAT_CONVERSION = 'oro.import_export.before_import_format_conversion';

    /**
     * This event occurs after data is converted into export format.
     *
     * Can be used to modify result after conversion ends.
     */
    public const AFTER_IMPORT_FORMAT_CONVERSION = 'oro.import_export.after_import_format_conversion';

    /**
     * This event occurs after processing some job.
     *
     * Can be used to do some stuff after the job. For example, clean cache.
     */
    public const AFTER_JOB_EXECUTION = 'oro.import_export.after_job_execution';

    /**
     * This event occurs before qetting ids for the products export
     *
     * It can be used to modify the query builder to make additional filtering
     */
    public const BEFORE_EXPORT_GET_IDS = 'oro.import_export.before_get_ids';

    /**
     * This event occurs before chunk jobs are created in import processor.
     *
     * Can be used to send extra messages before processing chunks.
     */
    public const BEFORE_CREATING_IMPORT_CHUNK_JOBS = 'oro.import_export.before_import_chunks';

    /**
     * This event occurs after the import completes.
     */
    public const FINISH_IMPORT = 'oro.import_export.finish_import';
}

## Events

# Table of Contents

 - [Where to Find](#where-to-find)
 - [AFTER_ENTITY_PAGE_LOADED](#after-entity-page-loaded)
 - [BEFORE_NORMALIZE_ENTITY](#before-normalize-entity)
 - [AFTER_NORMALIZE_ENTITY](#after-normalize-entity)
 - [BEFORE_DENORMALIZE_ENTITY](#before-denormalize-entity)
 - [AFTER_DENORMALIZE_ENTITY](#after-denormalize-entity)
 - [AFTER_LOAD_ENTITY_RULES_AND_BACKEND_HEADERS](#after-load-entity-rules-and-backend-headers)
 - [AFTER_LOAD_TEMPLATE_FIXTURES](#after-load-template-fixtures)
 - [BEFORE_EXPORT_FORMAT_CONVERSION](#before-export-format-conversion)
 - [AFTER_EXPORT_FORMAT_CONVERSION](#after-export-format-conversion)
 - [BEFORE_IMPORT_FORMAT_CONVERSION](#before-import-format-conversion)
 - [AFTER_IMPORT_FORMAT_CONVERSION](#after-import-format-conversion)
 - [AFTER_JOB_EXECUTION](#after-job-execution)

# Where to Find

All events are available in the Oro\Bundle\ImportExportBundle\Event\Events class.

# AFTER_ENTITY_PAGE_LOADED

This event occurs after the entity page is loaded in the iterator. It is used to modify rows.

# BEFORE_NORMALIZE_ENTITY

This event occurs before the entity is normalized. It is used to change the entity data or prefill the normalized data before normalization.

# AFTER_NORMALIZE_ENTITY

This event occurs after the entity is normalized. It is used to change the normalized data.

# BEFORE_DENORMALIZE_ENTITY

This event occurs before the entity is denormalized.
It is used to prefill the denormalized data.

# AFTER_DENORMALIZE_ENTITY

This event occurs after the entity is denormalized.
It is used to change the denormalized data.

# AFTER_LOAD_ENTITY_RULES_AND_BACKEND_HEADERS

This event occurs after the rules and backend headers are loaded.
It is used to modify the rules and headers, and add new ones.

# AFTER_LOAD_TEMPLATE_FIXTURES

This event occurs after the template fixtures are loaded.
It is used to modify the fixtures.

# BEFORE_EXPORT_FORMAT_CONVERSION

This event occurs before the data is converted into the export format.
It is used to modify the record before the conversion begins.

# AFTER_EXPORT_FORMAT_CONVERSION

This event occurs after the data is converted into the export format.
It is used to modify the result after the conversion ends.

# BEFORE_IMPORT_FORMAT_CONVERSION

This event occurs before the data is converted into the import format.
It is used to modify the record before the conversion begins.

# AFTER_IMPORT_FORMAT_CONVERSION

This event occurs after the data is converted into the export format.
It is used to modify the result after the conversion ends.

# AFTER_JOB_EXECUTION

This event occurs after a job is processed.
It is used to perform some actions after the job is processed. For example, clean cache.

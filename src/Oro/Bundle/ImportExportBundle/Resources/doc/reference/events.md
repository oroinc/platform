Events
======

Table of Contents
-----------------
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

AFTER_ENTITY_PAGE_LOADED
------------------------
This event occurs after the entity page is loaded in iterator.
Can be used to modify rows.

BEFORE_NORMALIZE_ENTITY
-----------------------
This event occurs before entity is normalized.
Can be used to change entity data or prefill normalized data before normalization.

AFTER_NORMALIZE_ENTITY
----------------------
This event occurs after entity is normalized.
Can be used to change normalized data.

BEFORE_DENORMALIZE_ENTITY
-------------------------
This event occurs before entity is denormalized.
Can be used to prefill denormalized data.

AFTER_DENORMALIZE_ENTITY
------------------------
This event occurs after entity is denormalized.
Can be used to change denormalized data.

AFTER_LOAD_ENTITY_RULES_AND_BACKEND_HEADERS
-------------------------------------------
This event occurs after rules and backend headers are loaded.
Can be used to modify, add new headers and modify rules.

AFTER_LOAD_TEMPLATE_FIXTURES
----------------------------
This event occurs after template fixtures are loaded.
Can be used to modify fixtures.

BEFORE_EXPORT_FORMAT_CONVERSION
-------------------------------
This event occurs before data is converted into export format.
Can be used to modify record before conversion begins.

AFTER_EXPORT_FORMAT_CONVERSION
------------------------------
This event occurs after data is converted into export format.
Can be used to modify result after conversion ends.

BEFORE_IMPORT_FORMAT_CONVERSION
-------------------------------
This event occurs before data is converted into import format.
Can be used to modify record before conversion begins.

AFTER_IMPORT_FORMAT_CONVERSION
------------------------------
This event occurs after data is converted into export format.
Can be used to modify result after conversion ends.

AFTER_JOB_EXECUTION
-------------------
This event occurs after processing some job.
Can be used to do some staff after job. For example, clean cache.

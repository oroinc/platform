UPGRADE FROM 2.3 to 2.4
=======================

**IMPORTANT**
-------------

Some inline underscore templates from next bundles, were moved to separate .html file for each template:
 - DataGridBundle
 - FilterBundle
 - UIBundle

MessageQueue component
----------------------
- Class `Oro\Component\MessageQueue\Job\JobStorage`
    - removed unused method `updateJobProgress`
- Class `Oro\Component\MessageQueue\Consumption\QueueConsumer`
    - changed the constructor signature: parameter `ExtensionInterface $extension = null` was replaces with `ExtensionInterface $extension`
- Added interface `Oro\Component\MessageQueue\Job\ExtensionInterface` that can be used to do some additional work before and after job processing
- Class `Oro\Component\MessageQueue\Job\JobRunner`
    - changed the constructor signature: added parameter `ExtensionInterface $jobExtension`
- Class `Oro\Component\MessageQueue\Util\VarExport` was removed


BatchBundle
-----------
- Class `Oro\Bundle\BatchBundle\Job\DoctrineJobRepository`
    - changed the constructor signature: parameter `EntityManager $entityManager` was replaced with `ManagerRegistry $doctrine`

DashboardBundle
--------
- Class `Oro\Bundle\DashboardBundle\Helper\DateHelper`
    - In method `addDatePartsSelect` removed the last one argument `$useCurrentTimeZone`
    - In method `getEnforcedTimezoneFunction` removed the last one argument `$useCurrentTimeZone`

DataGridBundle
--------------
- Class `Oro\Bundle\DataGridBundle\Extension\Sorter\PreciseOrderByExtension` was renamed to `Oro\Bundle\DataGridBundle\Extension\Sorter\HintExtension`.
 Hint name and priority now passed as 2nd and 3rd constructor arguments
- `HINT_DISABLE_ORDER_BY_MODIFICATION_NULLS` was enabled by default for all data grids. To enable order by nulls behavior same to MySQL for PostgreSQL 
 next hint should be added to data grid config
```yaml
datagrids:
    grid-name:
       ...
       source:
           ...
           hints:
               - { name: HINT_DISABLE_ORDER_BY_MODIFICATION_NULLS, value: false }
```

FormBundle
----------
- Removed usage of `'tinymce/jquery.tinymce'` extension. Use `'tinymce/tinymce'` directly instead

MessageQueueBundle
------------------
- The entity manager `message_queue_job` was removed. The default entity manager is used instead
- Service `oro_message_queue.client.consume_messages_command` was removed
- Service `oro_message_queue.command.consume_messages` was removed

SyncBundle
----------
- Class `Oro\Bundle\SyncBundle\Content\DoctrineTagGenerator`
    - removed property `generatedTags`
    - removed method `getCacheIdentifier`

UIBundle
--------
- `'oroui/js/tools'` JS-module does not contain utils methods from `Caplin.utils` any more. Require `'chaplin'` directly to get access to them.

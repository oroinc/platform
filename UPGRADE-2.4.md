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

SegmentBundle
-------------
- Services `oro_segment.query_converter.segment` and `oro_segment.query_converter.segment.link` were removed.
- Factory Oro\Bundle\SegmentBundle\Query\SegmentQueryConverterFactory was created. it was registered as the service `oro_segment.query.segment_query_converter_factory`.
    services.yml
    ```yml
    oro_segment.query.segment_query_converter_factory:
        class: 'Oro\Bundle\SegmentBundle\Query\SegmentQueryConverterFactory'
        arguments:
            - '@oro_query_designer.query_designer.manager'
            - '@oro_entity.virtual_field_provider.chain'
            - '@doctrine'
            - '@oro_query_designer.query_designer.restriction_builder'
            - '@oro_entity.virtual_relation_provider.chain'
        public: false
    ```
- Service `oro_segment.query.segment_query_converter_factory.link` was created to initialize the service `oro_segment.query.segment_query_converter_factory` in `Oro\Bundle\SegmentBundle\Query\DynamicSegmentQueryBuilder`.
    services.yml
    ```yml
    oro_segment.query.segment_query_converter_factory.link:
        tags:
            - { name: oro_service_link,  service: oro_segment.query.segment_query_converter_factory }
     ```
- Class `Oro\Bundle\SegmentBundle\Query\DynamicSegmentQueryBuilder` was changed to use `oro_segment.query.segment_query_converter_factory.link` instead of `oro_segment.query_converter.segment.link`.
    - public method `setSegmentQueryConverterFactoryLink(ServiceLink $segmentQueryConverterFactoryLink)` was removed.

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

ActionBundle
------------
- Class `Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass\ConfigurationPass` was removed.
- Changed constructor arguments in `Oro\Bundle\ActionBundle\Configuration\ConfigurationProvider`. Added `Oro\Bundle\CacheBundle\Loader\ConfigurationLoader $configurationLoader` before previous arguments.

ApiBundle
---------
- The `data_transformer` option for fields was removed from `Resources/config/oro/api.yml`. This option is required very very rarely and it is quite confusing for developers because its name is crossed with data transformers used in Symfony Forms, but the purpose of this option was very different and it was used to transform a field value from one data type to another during loading data. If you used this option for some of your API resources, please replace it with a processor for [customize_loaded_data](./src/Oro/Bundle/ApiBundle/Resources/doc/actions.md#customize_loaded_data-action) action.
- Class `Oro\Bundle\ApiBundle\Request\ApiActions`
    - removed methods `isInputAction`, `isOutputAction` and `getActionOutputFormatActionType`. They were moved to `Oro\Bundle\ApiBundle\ApiDoc\RestDocHandler`
    - removed method `isIdentifierNeededForAction`. This code was moved to `Oro\Bundle\ApiBundle\ApiDoc\Parser\ApiDocMetadataParser`
- Class `Oro\Bundle\ApiBundle\ApiDoc\HtmlFormatter` was renamed to `Oro\Bundle\ApiBundle\ApiDoc\NewHtmlFormatter`

BatchBundle
-----------
- Class `Oro\Bundle\BatchBundle\Job\DoctrineJobRepository`
    - changed the constructor signature: parameter `EntityManager $entityManager` was replaced with `ManagerRegistry $doctrine`

CacheBundle
-----------
- Added tag `oro.config_cache_warmer.provider` to be able to register custom warmer configuration provider for `Oro\Bundle\CacheBundle\EventListener\CacheWarmerListener`. It must implement `Oro\Bundle\CacheBundle\Provider\ConfigCacheWarmerInterface`.

DashboardBundle
--------
- Class `Oro\Bundle\DashboardBundle\Helper\DateHelper`
    - In method `addDatePartsSelect` removed the last one argument `$useCurrentTimeZone`
    - In method `getEnforcedTimezoneFunction` removed the last one argument `$useCurrentTimeZone`

DataAuditBundle
---------------
- Class `Oro\Bundle\DataAuditBundle\Provider\AuditConfigProvider`
    - changed the constructor signature: parameter `ConfigProvider $configProvider` was replaces with `ConfigManager $configManager`

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

SecurityBundle
--------------
- Class `Oro\Bundle\SecurityBundle\Acl\Domain\PermissionGrantingStrategy`
    - added new granting strategy named `PERMISSION`, for details see `Oro\Bundle\SecurityBundle\Acl\Domain\PermissionGrantingStrategy::PERMISSION`
    - removed method `containsExtraPermissions`

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

SyncBundle
----------
- Class `Oro\Bundle\SyncBundle\Content\DoctrineTagGenerator`
    - removed property `generatedTags`
    - removed method `getCacheIdentifier`

UIBundle
--------
- `'oroui/js/tools'` JS-module does not contain utils methods from `Caplin.utils` any more. Require `'chaplin'` directly to get access to them.

UserBundle
----------
-  Removed the use of js-application build `js/oro.min.js` from login page. Use `head_script` twig placeholder to include custom script on login page.
- Class `Oro\Bundle\UserBundle\Api\ApiDoc\UserProfileRestRouteOptionsResolver`
    - changed the constructor signature: parameter `RestDocViewDetector $docViewDetector` was removed
- Class `Oro\Bundle\UserBundle\Api\Routing\UserProfileRestRouteOptionsResolver`
    - changed the constructor signature: parameter `RestDocViewDetector $docViewDetector` was removed

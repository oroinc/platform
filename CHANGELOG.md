## 3.1.0-rc (2018-11-30)
[Show detailed list of changes](incompatibilities-3-1-rc.md)

### Added
#### AssetBundle
* `AssetBundle` replaces the deprecated `AsseticBundle` to build assets using Webpack. 
It currently supports only styles assets. JS assets are still managed by [OroRequireJsBundle](src/Oro/Bundle/RequireJSBundle).

#### ApiBundle
* Added `custom_fields` as a possible value for `exclusion_policy` option of `entities` section of `Resources/config/oro/api.yml`. This value can be used if it is required to exclude all custom fields (fields with `is_extend` = `true` and `owner` = `Custom` in `extend` scope in entity configuration) that are not configured explicitly.

#### AttachmentBundle
* Added possibility to set available mime types from configuration.
To add or remove available mime types, add changes to the `upload_file_mime_types` section and `upload_image_mime_types` in the config.yml file:

```yml
oro_attachment:
    upload_file_mime_types:
        - application/msword
        - application/vnd.ms-excel
        - application/pdf
        - application/zip
        - image/gif
        - image/jpeg
        - image/png
    upload_image_mime_types:
        - image/gif
        - image/jpeg
        - image/png
```

### Removed
#### AsseticBundle
* Bundle was removed, use AssetBundle instead
#### QueryDesignerBundle
* The unused alias `oro_query_designer.virtual_field_provider` for the service `oro_entity.virtual_field_provider.chain` was removed.

### Changed
#### AssetBundle
* Syntax of `Resources/config/oro/assets.yml` files for the management-console was changed to follow the same standard as the configuration files for the OroCommerce storefront.
Use the `inputs` node instead of the group names.
```diff
- assets:
css:
-    'my_custom_asset_group':
+    inputs:
      - 'bundles/app/css/scss/first.scss'
      - 'bundles/app/css/scss/second.scss'
-    'another_asset_group':
      - 'bundles/app/css/scss/third.scss'             
```
#### ApiBundle
* Fixed the `depends_on` configuration option of the `entities.fields` section of `Resources/config/oro/api.yml`. Now, only entity property names (or paths that contain entity property names) can be used in it. In addition, exception handling of invalid values for this option was improved to return more useful exception messages.

## 3.1.0-rc (2018-11-30)
[Show detailed list of changes](incompatibilities-3-1-rc.md)

### Added
#### ApiBundle
* Enable filters for to-many associations. The following operators are implemented: `=` (`eq`), `!=` (`neq`), `*` (`exists`), `!*` (`neq_or_null`), `~` (`contains`) and `!~` (`not_contains`).
* Added [documentation about filters](./src/Oro/Bundle/ApiBundle/Resources/doc/filters.md).
* Added data flow diagrams for public actions. See [Actions](./src/Oro/Bundle/ApiBundle/Resources/doc/actions.md).
* Added `rest_api_prefix` and `rest_api_pattern` configuration options and `oro_api.rest.prefix` and `oro_api.rest.pattern` DIC parameters to be able to reconfigure REST API base path.
* Added trigger `disposeLayout` on DOM element in `layout`
#### DatagridBundle
* Added [Datagrid Settings](./src/Oro/Bundle/DataGridBundle/Resources/doc/frontend/datagrid_settings.md) functionality for flexible managing of filters and grid columns

#### CacheBundle
* Added `oro.cache.abstract.without_memory_cache` that is the same as `oro.cache.abstract` but without using additional in-memory caching, it can be used to avoid unnecessary memory usage and performance penalties if in-memory caching is not needed, e.g. you implemented some more efficient in-memory caching strategy around your cache service.

#### SecurityBundle
* Added `Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension` trait that can be used in functional tests where you need to change permissions for security roles.

#### UIBundle
* Added the `addBeforeActionPromise` static method of `BaseController` in JS which enables to postpone route action if the required async process is in progress.

### Removed
#### DataAuditBundle
* The event `oro_audit.collect_audit_fields` was removed. Use decoration of `oro_dataaudit.converter.change_set_to_audit_fields` service instead.
* The alias `oro_dataaudit.listener.entity_listener` for the service `oro_dataaudit.listener.send_changed_entities_to_message_queue` was removed.
#### EntityConfigBundle
* Removed `oro.entity_config.field.after_remove` event. Use `oro.entity_config.post_flush` event and `ConfigManager::getFieldConfigChangeSet('extend', $className, $fieldName)` method to check if a field was removed. If the change set has `is_deleted` attribute and its value is changed from `false` to `true` than a field was removed.
#### NotificationBundle
* Removed the following DIC parameters: `oro_notification.event_entity.class`, `oro_notification.emailnotification.entity.class`, `oro_notification.massnotification.entity.class`, `oro_notification.entity_spool.class`, `oro_notification.manager.class`, `oro_notification.email_handler.class`, `oro_notification.doctrine_listener.class`, `oro_notification.event_listener.mass_notification.class`, `oro_notification.form.type.email_notification.class`, `oro_notification.form.type.recipient_list.class`, `oro_notification.form.handler.email_notification.class`, `oro_notification.form.type.email_notification_entity_choice.class`, `oro_notification.email_notification.manager.api.class`, `oro_notification.mailer.transport.spool_db.class`, `oro_notification.mailer.transport.spool_entity.class`, `oro_notification.event_listener.email_notification_service.class`, `oro_notification.email_notification_entity_provider.class`, `oro_notification.mass_notification_sender.class`.
#### UIBundle
* Removed the `loadBeforeAction` and `addToReuse` static methods of `BaseController` in JS. Global Views and Components can now be defined in the HTML over data attributes, the same way as an ordinary [Page Component](./src/Oro/Bundle/UIBundle/Resources/doc/reference/page-component.md).
#### SecurityBundle
* Removed `oro_security.acl_helper.process_select.after` event, create [Access Rule](./src/Oro/Bundle/SecurityBundle/Resources/doc/access-rules.md) instead.
* Removed `Oro\Bundle\SecurityBundle\ORM\Walker\AclWalker`, `Oro\Bundle\SecurityBundle\ORM\Walker\Condition\AclConditionInterface`, `Oro\Bundle\SecurityBundle\ORM\Walker\Condition\AclCondition`, `Oro\Bundle\SecurityBundle\ORM\Walker\Condition\JoinAclCondition`, `Oro\Bundle\SecurityBundle\ORM\Walker\Condition\JoinAssociationCondition`, `Oro\Bundle\SecurityBundle\ORM\Walker\Condition\AclConditionStorage`, `Oro\Bundle\SecurityBundle\ORM\Walker\Condition\SubRequestAclConditionStorage` and `Oro\Bundle\SecurityBundle\ORM\Walker\AclConditionalFactorBuilder` classes because now ACL restrictions applies with Access Rules by `Oro\Bundle\SecurityBundle\ORM\Walker\AccessRuleWalker`.
* Removed `Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper::applyAclToCriteria` method. Please use `apply` method with Doctrine Query or Query builder instead.
#### DatagridBundle
* Removed all logic related with column manager. The logic of column manager was transformed and expanded in [Datagrid Settings](./src/Oro/Bundle/DataGridBundle/Resources/doc/frontend/datagrid_settings.md)
#### EntitySerializer Component
* Removed `excluded_fields` deprecated configuration attribute for an entity. Use `exclude` attribute for a field instead.
* Removed `result_name` deprecated configuration attribute for a field. Use `property_path` attribute instead.
* Removed `orderBy` deprecated configuration attribute. Use `order_by` attribute instead.
* Removed deprecated signature `function (array &$item) : void` of post serialization handler that can be specified in `post_serialize` configuration attribute. Use `function (array $item, array $context) : array` instead.
#### Testing Component
* The class `Oro\Component\Testing\Validator\AbstractConstraintValidatorTest` was removed. Use `Symfony\Component\Validator\Test\ConstraintValidatorTestCase` instead.

### Changed
#### AddressBundle
* Changes in `/api/addresses` REST API resource:
    - the attribute `created` was renamed to `createdAt`
    - the attribute `updated` was renamed to `updatedAt`
#### ApiBundle
* By default processors for `customize_loaded_data` action are executed only for primary and included entities. Use `identifier_only: true` tag attribute if your processor should be executed for relationships.
* `finish_submit` event for `customize_form_data` action was renamed to `post_validate` and new `pre_validate` event was added.
#### NotificationBundle
* Renamed the service `oro_notification.event_listener.email_notification_service` to `oro_notification.grid_helper`.
* Marked the following services as `private`: `oro_notification.entity_spool`, `oro_notification.form.subscriber.additional_emails`, `oro_notification.doctrine.event.listener`, `oro_notification.model.notification_settings`, `oro_notification.email_handler`, `oro_notification.mailer.spool_db`, `oro_notification.mailer.transport.eventdispatcher`, `oro_notification.mailer.transport`, `swiftmailer.mailer.db_spool_mailer`, `oro_notification.email_notification_entity_provider`, `oro_notification.form.subscriber.contact_information_emails`, `oro_notification.provider.email_address_with_context_preferred_language_provider`.
#### UIBundle
* Changed all UI of backoffice
* Updated version of bootstrap from 2.3.0 to 4.1.1
* All global JS Views and Components are defined in the HTML through data attributes.
* Change target and name of a layout event. Now `layout` triggers `initLayout` event on DOM element instead `layoutInit` on `mediator`
#### RequireJsBundle
* `oro_require_js.js_engine` configuration option was removed. Use `oro_asset.nodejs_path` instead.
#### SecurityBundle
* `Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper::apply` method logic was changed to support Access rules.
* `oro_security.encoder.mcrypt` service was changed to `oro_security.encoder.default`.
#### TagBundle
* Changes in `/api/taxonomies` REST API resource:
    - the attribute `created` was renamed to `createdAt`
    - the attribute `updated` was renamed to `updatedAt`
#### MessageQueue Component
* In case when message processor specified in message not found this message will be rejected and exception will be thrown. 

## 3.0.0 (2018-07-27)
[Show detailed list of changes](incompatibilities-3-0.md)

## 3.0.0-rc (2018-05-31)
[Show detailed list of changes](incompatibilities-3-0-rc.md)

### Added
#### ApiBundle
* Added `direction` option for fields in the `actions` section to be able to specify if the request data and the the response data can contain a field. Possible values are `input-only`, `output-only` or `bidirectional`. The `bidirectional` is the default value.
* Added the following operators for ComparisonFilter: `*` (`exists`), `!*` (`neq_or_null`), `~` (`contains`), `!~` (`not_contains`), `^` (`starts_with`), `!^` (`not_starts_with`), `$` (`ends_with`), `!$` (`not_ends_with`). For details see [how_to.md](./src/Oro/Bundle/ApiBundle/Resources/doc/how_to.md#enable-advanced-operators-for-string-filter).
* Added the `case_insensitive` and `value_transformer` options for ComparisonFilter. See [how_to.md](./src/Oro/Bundle/ApiBundle/Resources/doc/how_to.md#enable-case-insensitive-string-filter) for more details.

### Removed
#### ApiBundle
* Removed deprecated routes contain `_format` placeholder.

### Changed
#### ApiBundle
* The `oro_api.get_config.add_owner_validator` service was renamed to `oro_organization.api.config.add_owner_validator`
* The `oro_api.request_type_provider` DIC tag was renamed to `oro.api.request_type_provider`
* The `oro_api.routing_options_resolver` DIC tag was renamed to `oro.api.routing_options_resolver`
* The `oro_api.api_doc_annotation_handler` DIC tag was renamed to `oro.api.api_doc_annotation_handler`

## 3.0.0-beta (2018-03-30)
[Show detailed list of changes](incompatibilities-3-0-beta.md)

### Added
#### ApiBundle
* Added a possibility to enable custom API. See [how_to.md](./src/Oro/Bundle/ApiBundle/Resources/doc/how_to.md#enable-custom-api) for more information.

### Removed
#### ApiBundle
* Removed the deprecated `Oro\Bundle\ApiBundle\Processor\CustomizeLoadedDataContext` class
* Removed the deprecated `Oro\Bundle\ApiBundle\Model\EntityDescriptor` class

#### EntityConfigBundle
* Removed the deprecated `getDefaultTimeout` and `setDefaultTimeout` methods from the `Oro\Bundle\EntityConfigBundle\Tools\CommandExecutor` class

#### ImportExportBundle
* Removed the `Oro\Bundle\ImportExportBundle\EventListener\ExportJoinListener` class and the corresponding `oro_importexport.event_listener.export_join_listener` service 
* The `%oro_importexport.file.split_csv_file.size_of_batch%` parameter was removed; use `%oro_importexport.import.size_of_batch%` instead.

#### InstallerBundle
* Removed the deprecated `getDefaultTimeout` and `setDefaultTimeout` methods from the `Oro\Bundle\InstallerBundle\CommandExecutor` class 

#### UIBundle
* Removed twig filter `oro_html_tag_trim`; use `oro_html_escape` instead. See [documentation](./src/Oro/Bundle/UIBundle/Resources/doc/reference/twig-filters.md#oro_html_escape).
* Removed twig filter `oro_html_purify`; use `oro_html_strip_tags` instead. See [documentation](./src/Oro/Bundle/UIBundle/Resources/doc/reference/twig-filters.md#oro_html_strip_tags).

#### WorkflowBundle
* Removed the `oro_workflow.cache.provider.workflow_definition` cache provider. Doctrine result cache is used instead.

### Changed
#### ApiBundle
* The HTTP method depended routes and controllers were replaced with the more general ones. The following is the full list of changes:

    | Removed Route | Removed Controller | New Route | New Controller |
    | --- | --- | --- | --- |
    | oro_rest_api_get | OroApiBundle:RestApi:get | oro_rest_api_item | OroApiBundle:RestApi:item |
    | oro_rest_api_delete | OroApiBundle:RestApi:delete | oro_rest_api_item | OroApiBundle:RestApi:item |
    | oro_rest_api_patch | OroApiBundle:RestApi:patch | oro_rest_api_item | OroApiBundle:RestApi:item |
    | oro_rest_api_post | OroApiBundle:RestApi:post | oro_rest_api_list | OroApiBundle:RestApi:list |
    | oro_rest_api_cget | OroApiBundle:RestApi:cget | oro_rest_api_list | OroApiBundle:RestApi:list |
    | oro_rest_api_cdelete | OroApiBundle:RestApi:cdelete | oro_rest_api_list | OroApiBundle:RestApi:list |
    | oro_rest_api_get_subresource | OroApiBundle:RestApiSubresource:get | oro_rest_api_subresource | OroApiBundle:RestApi:subresource |
    | oro_rest_api_get_relationship | OroApiBundle:RestApiRelationship:get | oro_rest_api_relationship | OroApiBundle:RestApi:relationship |
    | oro_rest_api_patch_relationship | OroApiBundle:RestApiRelationship:patch | oro_rest_api_relationship | OroApiBundle:RestApi:relationship |
    | oro_rest_api_post_relationship | OroApiBundle:RestApiRelationship:post | oro_rest_api_relationship | OroApiBundle:RestApi:relationship |
    | oro_rest_api_delete_relationship | OroApiBundle:RestApiRelationship:delete | oro_rest_api_relationship | OroApiBundle:RestApi:relationship |

#### UIBundle
* Twig filter `oro_tag_filter` was renamed to `oro_html_strip_tags`. See [documentation](./src/Oro/Bundle/UIBundle/Resources/doc/reference/twig-filters.md#oro_html_strip_tags).

#### UserBundle
* The `oro_rest_api_get_user_profile` route was removed; use the `oro_rest_api_user_profile` route instead.
* The `Oro\Bundle\UserBundle\Api\Routing\UserProfileRestRouteOptionsResolver` and the `Oro\Bundle\UserBundle\Api\ApiDoc\UserProfileRestRouteOptionsResolver` route option resolvers were removed in favor of [routing.yml](./src/Oro/Bundle/ApiBundle/Resources/doc/how_to.md#add-a-custom-route).

## 2.6.0 (2018-01-31)
[Show detailed list of changes](incompatibilities-2-6.md)

### Added
#### ConfigBundle
* Added the configuration search provider functionality (see [documentation](./src/Oro/Bundle/ConfigBundle/Resources/doc/system_configuration.md#search-type-provider))
    * Service should be registered as a service with the `oro_config.configuration_search_provider` tag.
    * Class should implement `Oro\Bundle\ConfigBundle\Provider\SearchProviderInterface` interface.
#### EntityBundle
* Added the `oro_entity.structure.options` event (see [documentation](./src/Oro/Bundle/EntityBundle/Resources/doc/events.md#entity-structure-options-event))
* Added the `Oro\Bundle\EntityBundle\Provider\EntityStructureDataProvider`provider to retrieve data of entities structure (see [documentation](./src/Oro/Bundle/EntityBundle/Resources/doc/entity_structure_data_provider.md))
* Added JS `EntityModel`[[?]](https://github.com/oroinc/platform/tree/2.6.0/src/Oro/Bundle/EntityBundle/Resources/public/js/app/models/entity-model.js) (see [documentation](./src/Oro/Bundle/EntityBundle/Resources/doc/client-side/entity-model.md))
* Added JS `EntityStructureDataProvider`[[?]](https://github.com/oroinc/platform/tree/2.6.0/src/Oro/Bundle/EntityBundle/Resources/public/js/app/services/entity-structure-data-provider.js) (see [documentation](./src/Oro/Bundle/EntityBundle/Resources/doc/client-side/entity-structure-data-provider.md))
* Added `FieldChoiceView`[[?]](https://github.com/oroinc/platform/tree/2.6.0/src/Oro/Bundle/EntityBundle/Resources/public/js/app/views/field-choice-view.js) Backbone view, as replacement for jQuery widget `oroentity.fieldChoice`.
#### EntityExtendBundle
* The `Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper::convertName` method was renamed to `convertEnumNameToCode`, visibility of this method was changed from `public` to `private` and it will throw an exception when the `iconv` function fails on converting the input string, instead of hashing the input string.
#### QueryDesignerBundle
* Added `FunctionChoiceView`[[?]](https://github.com/oroinc/platform/tree/2.6.0/src/Oro/Bundle/QueryDesignerBundle/Resources/public/js/app/views/function-choice-view.js) Backbone view, as replacement for jQuery widget `oroquerydesigner.functionChoice`.
#### SegmentBundle
* Added `SegmentChoiceView`[[?]](https://github.com/oroinc/platform/tree/2.6.0/src/Oro/Bundle/SegmentBundle/Resources/public/js/app/views/segment-choice-view.js) Backbone view, as replacement for jQuery widget `orosegment.segmentChoice`.
#### UIBundle
* Added JS `Registry`[[?]](https://github.com/oroinc/platform/tree/2.6.0/src/Oro/Bundle/UIBundle/Resources/public/js/app/services/registry/registry.js) (see [documentation](./src/Oro/Bundle/UIBundle/Resources/doc/reference/client-side/registry.md))
#### PlatformBundle
* Added a new DIC compiler pass `Oro\Bundle\PlatformBundle\DependencyInjection\Compiler\ConsoleGlobalOptionsCompilerPass`
* Added the `oro_platform.console.global_options_provider` tag to be able to register the console command global options provider for `GlobalOptionsProviderRegistry`<sup>[[?]](./src/Oro/Bundle/PlatformBundle/Provider/Console/GlobalOptionsProviderRegistry.php "Oro\Bundle\PlatformBundle\Provider\Console\GlobalOptionsProviderRegistry")</sup> and it will be used in `GlobalOptionsListener`<sup>[[?]](./src/Oro/Bundle/PlatformBundle/EventListener/Console/GlobalOptionsListener.php "Oro\Bundle\PlatformBundle\EventListener\Console\GlobalOptionsListener")</sup>. This providers must implement `GlobalOptionsProviderInterface`<sup>[[?]](./src/Oro/Bundle/PlatformBundle/Provider/Console/GlobalOptionsProviderInterface.php "Oro\Bundle\PlatformBundle\Provider\Console\GlobalOptionsProviderInterface")</sup>.

### Changed

#### ApiBundle
* The `build_query` group was removed from `update` and `delete` actions. From now the updating/deleting entity is loaded by `Oro\Bundle\ApiBundle\Processor\Shared\LoadEntity` processor instead of `Oro\Bundle\ApiBundle\Processor\Shared\LoadEntityByOrmQuery` processor.
* The priorities of some groups for the `update` action were changed. All changes are in the following table:

    | Group | Old Priority | New Priority |
    | --- | --- | --- |
    | load_data | -50 | -40 |
    | transform_data | -60 | -50 |
    | save_data | -70 | -60 |
    | normalize_data | -80 | -70 |
    | finalize | -90 | -80 |
    | normalize_result | -100 | -90 |

* The priorities of some groups for the `delete` action were changed. All changes are in the following table:

    | Group | Old Priority | New Priority |
    | --- | --- | --- |
    | load_data | -50 | -40 |
    | delete_data | -60 | -50 |
    | finalize | -70 | -60 |
    | normalize_result | -80 | -70 |

* Handling of `percent` data type in POST and PATCH requests was fixed. Before the fix, the percent value in GET and POST/PATCH requests was inconsistent; in POST/PATCH requests it was divided by 100, but GET request returned it as is. In this fix, the division by 100 was removed.
* For string filters the default value of the `allow_array` option was changed from `true` to `false`. This was done to allow filter data if a string field contains a comma.
#### DataGridBundle
* Parameter `count_hints` will have value of `hints` unless otherwise specified.
If other words from now
```yaml
datagrids:
    grid-name:
       ...
       source:
           ...
           hints:
               - SOME_QUERY_HINT
```
equivalent
```yaml
datagrids:
    grid-name:
       ...
       source:
           ...
           hints:
               - SOME_QUERY_HINT
           count_hints:
               - SOME_QUERY_HINT
```
#### SegmentBundle
* Refactored the `SegmentComponent` js-component to use `EntityStructureDataProvider`.
#### SidebarBundle
* In the `Oro\Bundle\SidebarBundle\Model\WidgetDefinitionRegistry` class, the return type in the `getWidgetDefinitions` and `getWidgetDefinitionsByPlacement` methods were changed from `ArrayCollection` to `array`.
#### UIBundle
* The `loadModules` method of the `'oroui/js/tools'` js-module now returns a promise object.
* The default value for the `keepElement` property of a `Chaplin.View` has changed from `false` to `null` when no element is provided, and from `false` to `true` when the element is provided in the options.
#### WorkflowBundle
* Refactored the `WorkflowEditorComponent` and `WorkflowViewerComponent` js-components to use `EntityStructureDataProvider`.

### Deprecated
#### EntityBundle
* JS util `EntityFieldsUtil` is deprecated in favor of `EntityStructureDataProvider`.

### Removed
#### AttachmentBundle
* The parameter `oro_attachment.listener.file_listener.class` was removed form the service container.
#### CommentBundle
* The parameter `oro_comment.comment_lifecycle_listener.class` was removed form the service container.
#### EntityBundle
* A jQuery widget `oroentity.fieldChoice` is replaced with the `FieldChoiceView` Backbone view.
* A jQuery widget `oroentity.fieldsLoader` is removed. Please use `EntityStructureDataProvider` instead.
#### ImapBundle
* The parameter `oro_imap.listener.user_email_origin.class` was removed form the service container
#### QueryDesignerBundle
* A jQuery widget `oroquerydesigner.functionChoice` is replaced with the `FunctionChoiceView` Backbone view.
#### ReminderBundle
* The parameter `oro_reminder.event_listener.reminder_listener.class` was removed form the service container.
#### SegmentBundle
* A jQuery widget `orosegment.segmentChoice` is replaced with the `SegmentChoiceView` Backbone view.
#### SidebarBundle
* The parameter `oro_sidebar.widget_definition.registry.class` was removed form the service container.
* The service `oro_sidebar.request.handler` was removed.
#### SSOBundle
* The parameter `oro_sso.event_listener.user_email_change_listener.class` was removed form the service container.
#### UIBundle
* Removed the `loadModule` methods from `'oroui/js/tools'` js-module.  Please use `loadModules` instead.

#### WorkflowBundle
* The parameter `oro_workflow.listener.process_data_serialize.class` was removed form the service container.
* The parameter `oro_workflow.listener.workflow_data_serialize.class` was removed form the service container.

## 2.5.0 (2017-11-30)
[Show detailed list of changes](incompatibilities-2-5.md)

### Added
#### ActivityListBundle
* Added `ActivityConditionView` as substitution for removed `oroactivity.activityCondition` jQuery widget.
#### ApiBundle
* Added an additional syntax for data filters: `key[operator_name]=value`. For example `GET /api/users?filter[id][neq]=2` can be used instead of `GET /api/users?filter[id]!=2`. The supported operators are `eq`, `neq`, `lt`, `lte`, `gt` and `gte`.
* Added a possibility to specify the **documentation_resource** option for the same entity in different `Resources/config/oro/api.yml` files. It can be helpful when some bundle needs to add a field to an entity declared in another bundle.
* Added a possibility to configure own identifier field(s) instead of the database primary key. For details see [how_to.md](./src/Oro/Bundle/ApiBundle/Resources/doc/how_to.md#using-a-non-primary-key-to-identify-entity)
* Added filters for the following data types: `smallint`, `date`, `time`, `guid`, `percent`, `money` and `duration`
* Added a range filter and option `allow_range` that allow to enable or disable this filter. An example of usage of this filter `/api/leads?filter[createdAt]=2017-10-19T10:00:00..2017-10-19T10:30:00`
#### DataAuditBundle
* Added `DataAuditConditionView` as substitution for removed `oroauditquerydesigner.dataAuditCondition` jQuery widget.
#### EntityConfigBundle
* Added the`Oro\Bundle\EntityConfigBundle\Attribute\Type\AttributeTypeInterface` interface that should be implemented in case a new type of arguments was added.
#### MessageQueue Component
* Added method `onPreCreateDelayed` to `Oro\Component\MessageQueue\Job\ExtensionInterface` interface.
* Added Stale Jobs functionality
    * Added the `setJobConfigurationProvider` method to `Oro\Component\MessageQueue\Job\JobProcessor`
    * Added the new oro.message_queue_job.status.stale state 
    * Added the new `Oro\Component\MessageQueue\Provider\JobConfigurationProviderInterface` interface 
#### MessageQueueBundle
* Added interface `Oro\Bundle\MessageQueueBundle\Consumption\Extension\ClearerInterface`. For details see [container_in_consumer.md](./src/Oro/Bundle/MessageQueueBundle/Resources/doc/container_in_consumer.md#container-reset)
* Added configuration for Stale Jobs
#### QueryDesignerBundle
* Added `ConditionBuilderView` as substitution for removed `oroquerydesigner.conditionBuilder` jQuery widget.
* Added `AbstractConditionView` and `FieldConditionView`  as substitution for removed `oroquerydesigner.fieldCondition` jQuery widget.
* Added `AggregatedFieldConditionView` as substitution for removed `oroauditquerydesigner.aggregatedFieldCondition` jQuery widget.
#### SyncBundle
* Added parameters `websocket_frontend_path` and `websocket_backend_path`. [Usage](https://github.com/oroinc/platform/blob/master/src/Oro/Bundle/SyncBundle/README.md)
### Changed
#### ApiBundle
* Class `Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig`
    * method `hasDocumentationResource` was renamed to `hasDocumentationResources`
    * method `getDocumentationResource` was renamed to `getDocumentationResources`
    * method `setDocumentationResource` was renamed to `setDocumentationResources`
* Added a possibility to rename associations. This leads the following backward incompatible changes:
    * the data passed to `customize_loaded_data` processors were changed: from now these data contain already renamed fields and associations are not collapsed
#### ChainProcessor Component
* The performance and memory usage was optimized. As result the following changes were done:
    * the building of groups and processors maps functionality was moved from `Oro\Component\ChainProcessor\ProcessorBag` to `Oro\Component\ChainProcessor\ProcessorBagConfigBuilder`
    * methods `addGroup` and `addProcessor` were removed from `Oro\Component\ChainProcessor\ProcessorBag`
    * the schema of data stored in `Oro\Component\ChainProcessor\ProcessorBag::$processors` property was changed from `[action => [['processor' => processor id, 'attributes' => [attribute name => attribute value, ...]], ...], ...]` to `[action => [[processor id, [attribute name => attribute value, ...]], ...], ...]`
    * the schema of data stored in `Oro\Component\ChainProcessor\ProcessorIterator::$processors` property was changed from `[['processor' => processor id, 'attributes' => [attribute name => attribute value, ...]], ...]` to `[[processor id, [attribute name => attribute value, ...]], ...]`
    * the DIC compiler pass `Oro\Component\ChainProcessor\DependencyInjection\LoadProcessorsCompilerPass` was split into two compiler passes `Oro\Component\ChainProcessor\DependencyInjection\LoadProcessorsCompilerPass` and `Oro\Component\ChainProcessor\DependencyInjection\LoadApplicableCheckersCompilerPass`
    * added new DIC compiler pass `Oro\Component\ChainProcessor\DependencyInjection\LoadAndBuildProcessorsCompilerPass`
#### EntityConfigBundle
* Implementation should be registered as a service with the `oro_entity_config.attribute_type` tag.
#### EntitySerializer Component
* Added a possibility to rename associations. This leads the following backward incompatible changes:
    * the `Oro\Component\EntitySerializer\EntitySerializer` class was changed a lot. If you have classes extend this class, carefully check them
    * the data passed to `post_serialize` handlers were changed: from now these data contain already renamed fields and associations are not collapsed
#### MessageQueue Component
* Interface `Oro\Component\MessageQueue\Job\ExtensionInterface`
    * renamed method `onCreateDelayed` to `onPostCreateDelayed`
#### MessageQueueBundle
* Method `setPersistentServices` was moved from `Oro\Bundle\MessageQueueBundle\Consumption\Extension\ContainerResetExtension` to `Oro\Bundle\MessageQueueBundle\Consumption\Extension\ContainerClearer`
#### SearchBundle
* Entity `Oro\Bundle\WebsiteSearchBundle\Entity\IndexDecimal`:
    * changed decimal field `value`:
        * `precision` changed from `10` to `21`.
        * `scale` changed from `2` to `6`.
* Added the Oro\Bundle\SearchBundle\Formatter\DateTimeFormatter class that should be used to format the \DateTime object in a specific string. [Documentation](./src/Oro/Bundle/SearchBundle/Resources/doc/date-time-formatter.md) 
#### WorkflowBundle
* The property `restrictions` was excluded from output results of the method "Get Workflow Definition" (`/api/rest/{version}/workflowdefinition/{workflowDefinition}.{_format}`).
### Deprecated
#### SearchBundle
* Class `Oro/Bundle/SearchBundle/Engine/Orm/DBALPersistenceDriverTrait` is deprecated. The functionality was merged into `BaseDriver`
### Removed
#### ActivityListBundle
* Refactored setup of ActivityCondition for QueryDesigner's ConditionBuilder. 
    * jQuery widget `oroactivity.activityCondition` replaced with `ActivityConditionView` Backbone view, removed unused extensions support in its options.
    * Removed class `Oro\Bundle\ActivityListBundle\EventListener\SegmentWidgetOptionsListener`.
#### DataAuditBundle
* jQuery widget `oroauditquerydesigner.dataAuditCondition` replaced with `DataAuditConditionView` Backbone view.
#### QueryDesignerBundle
* jQuery widget `oroquerydesigner.conditionBuilder` replaced with `ConditionBuilderView` Backbone view.
* jQuery widget `oroquerydesigner.fieldCondition` refactored into `AbstractConditionView` and `FieldConditionView` Backbone views.
* jQuery widget `oroauditquerydesigner.aggregatedFieldCondition` replaced with `AggregatedFieldConditionView` Backbone view.
#### SearchBundle
* Removed service `oro_search.search.engine.storer`
#### SecurityBundle
* Class `Oro\Bundle\SecurityBundle\Owner\AbstractOwnerTreeProvider`
     * internal cache parameter `$tree` was removed cause all cache providers are already automatically decorated by the memory cache provider
#### WorkflowBundle
* Removed `renderResetButton()` macro from Oro/Bundle/WorkflowBundle/Resources/views/macros.html.twig. Also removed usage of this macro from two files:
    * `Oro/Bundle/WorkflowBundle/Resources/views/Widget/widget/button.html.twig`
    * `Oro/Bundle/WorkflowBundle/Resources/views/Widget/widget/buttons.html.twig`
#### OroBehatExtension
* Removed --show-execution-time and --log-feature-execution-time parameters along the MeasureExecutionTimeController

#### FormBundle
* Class `OroTextareaType`<sup>[[?]](https://github.com/oroinc/platform/blob/2.3.11/src/Oro/Bundle/FormBundle/Form/Type/OroTextareaType.php "Oro\Bundle\FormBundle\Form\Type\OroTextareaType")</sup> was removed. The `strip_tags` form option should be used instead.
* service `oro_form.type.textarea` was removed.

## 2.4.0 (2017-09-29)

### Added
#### CacheBundle
* Added tag `oro.config_cache_warmer.provider` to be able to register custom warmer configuration provider for `CacheWarmerListener`<sup>[[?]](https://github.com/oroinc/platform/tree/2.4.0/src/Oro/Bundle/CacheBundle/EventListener/CacheWarmerListener.php "Oro\Bundle\CacheBundle\EventListener\CacheWarmerListener")</sup>. It must implement `ConfigCacheWarmerInterface`<sup>[[?]](https://github.com/oroinc/platform/tree/2.4.0/src/Oro/Bundle/CacheBundle/Provider/ConfigCacheWarmerInterface.php "Oro\Bundle\CacheBundle\Provider\ConfigCacheWarmerInterface")</sup>.
#### ImportExportBundle
* Was added new parameter to `ConfigurableAddOrReplaceStrategy`<sup>[[?]](https://github.com/oroinc/platform/tree/2.4.0/src/Oro/Bundle/ImportExportBundle/Strategy/Import/ConfigurableAddOrReplaceStrategy.php "Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy")</sup> class constructor and 
`oro_importexport.strategy.configurable_add_or_replace` service. New parameter id `oro_security.owner.checker` service that helps check the owner during import.
* `JobResult`<sup>[[?]](https://github.com/oroinc/platform/tree/2.4.0/src/Oro/Bundle/ImportExportBundle/Job/JobResult.php "Oro\Bundle\ImportExportBundle\Job\JobResult")</sup> have new `needRedelivery` flag.
`JobExecutor`<sup>[[?]](https://github.com/oroinc/platform/tree/2.4.0/src/Oro/Bundle/ImportExportBundle/Job/JobExecutor.php "Oro\Bundle\ImportExportBundle\Job\JobExecutor")</sup> in case of any of catched exception during Job processing is a type of
`Doctrine\DBAL\Exception\UniqueConstraintViolationException` JobResult will have a `needRedelivery` flag set to true.
* `ImportMessageProcessor`<sup>[[?]](https://github.com/oroinc/platform/tree/2.4.0/src/Oro/Bundle/ImportExportBundle/Async/Import/ImportMessageProcessor.php "Oro\Bundle\ImportExportBundle\Async\Import\ImportMessageProcessor")</sup> is able to catch new 
`Oro\Component\MessageQueue\Exception\JobRedeliveryException` and it this case is able to requeue a message to process
#### MessageQueue component
* Added interface `Oro\Component\MessageQueue\Job\ExtensionInterface` that can be used to do some additional work before and after job processing.
### Changed
#### ApiBundle
* Class `HtmlFormatter`<sup>[[?]](https://github.com/oroinc/platform/tree/2.4.0/src/Oro/Bundle/ApiBundle/ApiDoc/HtmlFormatter.php "Oro\Bundle\ApiBundle\ApiDoc\HtmlFormatter")</sup> was renamed to `NewHtmlFormatter`<sup>[[?]](https://github.com/oroinc/platform/tree/2.4.0/src/Oro/Bundle/ApiBundle/ApiDoc/NewHtmlFormatter.php "Oro\Bundle\ApiBundle\ApiDoc\NewHtmlFormatter")</sup>
#### DataGridBundle
* Some inline underscore templates were moved to separate .html file for each template.
* Class `PreciseOrderByExtension`<sup>[[?]](https://github.com/oroinc/platform/tree/2.4.0/src/Oro/Bundle/DataGridBundle/Extension/Sorter/PreciseOrderByExtension.php "Oro\Bundle\DataGridBundle\Extension\Sorter\PreciseOrderByExtension")</sup> was renamed to `HintExtension`<sup>[[?]](https://github.com/oroinc/platform/tree/2.4.0/src/Oro/Bundle/DataGridBundle/Extension/Sorter/HintExtension.php "Oro\Bundle\DataGridBundle\Extension\Sorter\HintExtension")</sup>. Hint name and priority now passed as 2nd and 3rd constructor arguments
* `HINT_DISABLE_ORDER_BY_MODIFICATION_NULLS` was enabled by default for all data grids. To enable order by nulls behavior same to MySQL for PostgreSQL next hint should be added to data grid config
```yaml
datagrids:
    grid-name:
       ...
       source:
           ...
           hints:
               - { name: HINT_DISABLE_ORDER_BY_MODIFICATION_NULLS, value: false }
```
#### ElasticSearchBundle
* Tokenizer configuration has been changed. A full rebuilding of the backend search index is required.
#### EmailBundle
* Email entity is not ACL protected entity so it should not contain any permissions for it.
* method `handleChangedAddresses` in class `EmailOwnerManager`<sup>[[?]](https://github.com/oroinc/platform/tree/2.4.0/src/Oro/Bundle/EmailBundle/Entity/Manager/EmailOwnerManager.php "Oro\Bundle\EmailBundle\Entity\Manager\EmailOwnerManager")</sup> does not persist new EmailAddresses anymore, but returns array of updated entities and entities to create
#### FilterBundle
* Some inline underscore templates were moved to separate .html file for each template.
#### ImportExportBundle
* Class `ConfigurableTableDataConverter`<sup>[[?]](https://github.com/oroinc/platform/tree/2.4.0/src/Oro/Bundle/ImportExportBundle/Converter/ConfigurableTableDataConverter.php "Oro\Bundle\ImportExportBundle\Converter\ConfigurableTableDataConverter")</sup> does not initialize backend headers
    during import anymore. Method `getHeaderConversionRules` previously called `initialize` method to load both conversion
    rules and backend headers, but now it calls only `initializeRules`
#### MessageQueueBundle
* Parameter `oro_message_queue.maintance.idle_time` was renamed to `oro_message_queue.maintenance.idle_time`
* Class `Oro\Component\MessageQueue\Consumption\Extension\SignalExtension`
    * the visibility of method `interruptExecutionIfNeeded` was changed from `public` to `protected`
#### UIBundle
* Some inline underscore templates were moved to separate .html file for each template.
* `'oroui/js/tools'` JS-module does not contain utils methods from `Caplin.utils` any more. Require `'chaplin'` directly to get access to them.
* `'oroui/js/app/components/base/component-container-mixin'` Each view on which we want to call `'initLayout()'` method 
(to intialize all components within) have to be marked as separated layout by adding `'data-layout="separate"'` 
attribute. Otherwise `'Error'` will be thrown.
### Removed
#### ApiBundle
* The `data_transformer` option for fields was removed from `Resources/config/oro/api.yml`. This option is required rarely and it is quite confusing for developers because its name is crossed with data transformers used in Symfony Forms. However, the purpose of this option was different and it was used to transform a field value from one data type to another when loading data. If you used this option for some of your API resources, please replace it with a processor for [customize_loaded_data](./src/Oro/Bundle/ApiBundle/Resources/doc/actions.md#customize_loaded_data-action) action.
* Class `ApiActions`<sup>[[?]](https://github.com/oroinc/platform/tree/2.4.0/src/Oro/Bundle/ApiBundle/Request/ApiActions.php "Oro\Bundle\ApiBundle\Request\ApiActions")</sup>
    * removed methods `isInputAction`, `isOutputAction` and `getActionOutputFormatActionType`. They were moved to `RestDocHandler`<sup>[[?]](https://github.com/oroinc/platform/tree/2.4.0/src/Oro/Bundle/ApiBundle/ApiDoc/RestDocHandler.php "Oro\Bundle\ApiBundle\ApiDoc\RestDocHandler")</sup>
    * removed method `isIdentifierNeededForAction`. This code was moved to `ApiDocMetadataParser`<sup>[[?]](https://github.com/oroinc/platform/tree/2.4.0/src/Oro/Bundle/ApiBundle/ApiDoc/Parser/ApiDocMetadataParser.php "Oro\Bundle\ApiBundle\ApiDoc\Parser\ApiDocMetadataParser")</sup>
#### FormBundle
* Removed usage of the `'tinymce/jquery.tinymce'` extension. Use `'tinymce/tinymce'` directly instead
#### SearchBundle.
* Removed method `getUniqueId` from class `BaseDriver`<sup>[[?]](https://github.com/oroinc/platform/tree/2.4.0/src/Oro/Bundle/SearchBundle/Engine/Orm/BaseDriver.php "Oro\Bundle\SearchBundle\Engine\Orm\BaseDriver")</sup>. Use method `getJoinAttributes` instead.
#### SegmentBundle
* Services `oro_segment.query_converter.segment` and `oro_segment.query_converter.segment.link` were removed.
#### UserBundle
* Removed the use of js-application build `js/oro.min.js` from login page. Use `head_script` twig placeholder to include custom script on login page.
### Fixed
#### MessageQueueBundle
* Fixed handling of `priority` attribute of the tag `oro_message_queue.consumption.extension` to work in the same way
as other Symfony's tagged services. From now the highest the priority number, the earlier the extension is executed.
* Service `oro_message_queue.client.consume_messages_command` was removed
* Service `oro_message_queue.command.consume_messages` was removed
* The extension `TokenStorageClearerExtension`<sup>[[?]](https://github.com/oroinc/platform/tree/2.4.0/src/Oro/Bundle/MessageQueueBundle/Consumption/Extension/TokenStorageClearerExtension.php "Oro\Bundle\MessageQueueBundle\Consumption\Extension\TokenStorageClearerExtension")</sup> was removed. This 
job is handled by `ContainerResetExtension`<sup>[[?]](https://github.com/oroinc/platform/tree/2.4.0/src/Oro/Bundle/MessageQueueBundle/Consumption/Extension/ContainerResetExtension.php "Oro\Bundle\MessageQueueBundle\Consumption\Extension\ContainerResetExtension")</sup> extension.

## 2.3.1 (2017-07-28)

### Changed
#### SegmentBundle

- Class `SegmentQueryConverterFactory`<sup>[[?]](https://github.com/oroinc/platform/tree/2.3.1/src/Oro/Bundle/SegmentBundle/Query/SegmentQueryConverterFactory.php "Oro\Bundle\SegmentBundle\Query\SegmentQueryConverterFactory")</sup> was created. It was registered as the service `oro_segment.query.segment_query_converter_factory`.

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
- Service `oro_segment.query.segment_query_converter_factory.link` was created to initialize the service `oro_segment.query.segment_query_converter_factory` in `DynamicSegmentQueryBuilder`<sup>[[?]](https://github.com/oroinc/platform/tree/2.3.1/src/Oro/Bundle/SegmentBundle/Query/DynamicSegmentQueryBuilder.php "Oro\Bundle\SegmentBundle\Query\DynamicSegmentQueryBuilder")</sup>.

    services.yml
    ```yml
    oro_segment.query.segment_query_converter_factory.link:
        tags:
            - { name: oro_service_link,  service: oro_segment.query.segment_query_converter_factory }
    ```
- Class `DynamicSegmentQueryBuilder`<sup>[[?]](https://github.com/oroinc/platform/tree/2.3.1/src/Oro/Bundle/SegmentBundle/Query/DynamicSegmentQueryBuilder.php "Oro\Bundle\SegmentBundle\Query\DynamicSegmentQueryBuilder")</sup> was changed to use service `oro_segment.query.segment_query_converter_factory.link` instead of `oro_segment.query_converter.segment.link`.
    - public method `setSegmentQueryConverterFactoryLink(ServiceLink $segmentQueryConverterFactoryLink)` was added.
- Definition of service `oro_segment.query.dynamic_segment.query_builder` was changed in services.yml.
    Before
    ```yml
    oro_segment.query.dynamic_segment.query_builder:
        class: %oro_segment.query.dynamic_segment.query_builder.class%
        arguments:
            - '@oro_segment.query_converter.segment.link'
            - '@doctrine'
    ```
    After
    ```yml
    oro_segment.query.dynamic_segment.query_builder:
        class: %oro_segment.query.dynamic_segment.query_builder.class%
        arguments:
            - '@oro_segment.query_converter.segment.link'
            - '@doctrine'
        calls:
            - [setSegmentQueryConverterFactoryLink, ['@oro_segment.query.segment_query_converter_factory.link']]
    ```
## 2.3.0 (2017-07-28)

### Added
#### ImportExportBundle
* Added a possibility to change aggregation strategy for a job summary. An aggregator should implement `ContextAggregatorInterface`<sup>[[?]](https://github.com/oroinc/platform/tree/2.3.0/src/Oro/Bundle/ImportExportBundle/Job/Context/ContextAggregatorInterface.php "Oro\Bundle\ImportExportBundle\Job\Context\ContextAggregatorInterface")</sup>. Added two job summary aggregators:
    * `SimpleContextAggregator`<sup>[[?]](https://github.com/oroinc/platform/tree/2.3.0/src/Oro/Bundle/ImportExportBundle/Job/Context/SimpleContextAggregator.php "Oro\Bundle\ImportExportBundle\Job\Context\SimpleContextAggregator")</sup>, it summarizes counters by the type from all steps and it is a default aggregator
    * `SelectiveContextAggregator`<sup>[[?]](https://github.com/oroinc/platform/tree/2.3.0/src/Oro/Bundle/ImportExportBundle/Job/Context/SelectiveContextAggregator.php "Oro\Bundle\ImportExportBundle\Job\Context\SelectiveContextAggregator")</sup>, it summarizes counters by the type from all steps marked as `add_to_job_summary`
* Added trait `AddToJobSummaryStepTrait`<sup>[[?]](https://github.com/oroinc/platform/tree/2.3.0/src/Oro/Bundle/ImportExportBundle/Job/Step/AddToJobSummaryStepTrait.php "Oro\Bundle\ImportExportBundle\Job\Step\AddToJobSummaryStepTrait")</sup> that can be used in steps support `add_to_job_summary` parameter.
#### IntegrationBundle
* Class `LoggerClientDecorator`<sup>[[?]](https://github.com/oroinc/platform/tree/2.3.0/src/Oro/Bundle/IntegrationBundle/Provider/Rest/Client/Decorator/LoggerClientDecorator.php "Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Decorator\LoggerClientDecorator")</sup> was added. Implements `RestClientInterface`. Use it for logging client. Add the ability to make additional requests to the server.
#### MigrationBundle
* Added event `oro_migration.data_fixtures.pre_load` that is raised before data fixtures are loaded
* Added event `oro_migration.data_fixtures.post_load` that is raised after data fixtures are loaded
#### NoteBundle
* Added new action `create_note` related class `CreateNoteAction`<sup>[[?]](https://github.com/oroinc/platform/tree/2.3.0/src/Oro/Bundle/NoteBundle/Action/CreateNoteAction.php "Oro\Bundle\NoteBundle\Action\CreateNoteAction")</sup>
#### ReportBundle
* Class `ReportCacheCleanerListener`<sup>[[?]](https://github.com/oroinc/platform/tree/2.3.0/src/Oro/Bundle/ReportBundle/EventListener/ReportCacheCleanerListener.php "Oro\Bundle\ReportBundle\EventListener\ReportCacheCleanerListener")</sup> was added. It cleans cache of report grid on postUpdate event of Report entity.
#### WorkflowBundle
* Added provider `oro_workflow.provider.workflow_definition` to manage cached instances of `WorkflowDefinitions`.
* Added cache provider `oro_workflow.cache.provider.workflow_definition` to hold cached instances of `WorkflowDefinitions`.
### Changed
#### EmailBundle
* Class `EmailExtension`<sup>[[?]](https://github.com/oroinc/platform/tree/2.3.0/src/Oro/Bundle/EmailBundle/Twig/EmailExtension.php "Oro\Bundle\EmailBundle\Twig\EmailExtension")</sup> method `getSecurityFacade` was replaces with `getAuthorizationChecker` and `getTokenAccessor`
* Class `EmailQueryFactory`<sup>[[?]](https://github.com/oroinc/platform/tree/2.3.0/src/Oro/Bundle/EmailBundle/Datagrid/EmailQueryFactory.php "Oro\Bundle\EmailBundle\Datagrid\EmailQueryFactory")</sup> method `prepareQuery` renamed to `addFromEmailAddress`
* The performance of the following data grids was improved. As a result, their definitions and TWIG templates were significantly changed. The main change is to return only the fields required for the grid, instead of returning the whole entity
    * `base-email-grid`
    * `email-grid`
    * `dashboard-recent-emails-inbox-grid`
    * `dashboard-recent-emails-sent-grid`
    * `dashboard-recent-emails-new-grid`
    * `EmailBundle/Resources/views/Email/Datagrid/Property/contacts.html.twig`
    * `EmailBundle/Resources/views/Email/Datagrid/Property/date.html.twig`
    * `EmailBundle/Resources/views/Email/Datagrid/Property/date_long.html.twig`
    * `EmailBundle/Resources/views/Email/Datagrid/Property/from.html.twig`
    * `EmailBundle/Resources/views/Email/Datagrid/Property/mailbox.html.twig`
    * `EmailBundle/Resources/views/Email/Datagrid/Property/recipients.html.twig`
    * `EmailBundle/Resources/views/Email/Datagrid/Property/subject.html.twig`
    * TWIG macro `wrapTextToTag` was marked as deprecated
#### FormBundle
* Updated jQuery Validation plugin to 1.6.0 version
* Updated TinyMCE to 4.6.* version
#### IntegrationBundle
* Interface `RestResponseInterface`<sup>[[?]](https://github.com/oroinc/platform/tree/2.3.0/src/Oro/Bundle/IntegrationBundle/Provider/Rest/Client/RestResponseInterface.php "Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface")</sup> was changed:
    * Methods `getContentEncoding`, `getContentLanguage`, `getContentLength`, `getContentLocation`, `getContentDisposition`, `getContentMd5`, `getContentRange`, `getContentType`, `isContentType` were superseded by `getHeader` method 
#### LocaleBundle
* Updated Moment.js to 2.18.* version
* Updated Numeral.js to 2.0.6 version
#### NotificationBundle
* Entity `EmailNotification`<sup>[[?]](https://github.com/oroinc/platform/tree/2.3.0/src/Oro/Bundle/NotificationBundle/Model/EmailNotification.php "Oro\Bundle\NotificationBundle\Model\EmailNotification")</sup> became Extend
#### ReportBundle
* Class Oro\Bundle\ReportBundle\Grid\ReportDatagridConfigurationProvider was modified to use doctrine cache instead of caching the DatagridConfiguration value in property $configuration
     Before
     ```PHP
        class ReportDatagridConfigurationProvider
        {
            /**
             * @var DatagridConfiguration
             */
            protected $configuration;
            public function getConfiguration($gridName)
            {
                if ($this->configuration === null) {
                    ...
                    $this->configuration = $this->builder->getConfiguration();
                }
                return $this->configuration;
            }
        }
     ```
     After
     ```PHP
        class ReportDatagridConfigurationProvider
        {
            /**
             * Doctrine\Common\Cache\Cache
             */
            protected $reportCacheManager;
            public function getConfiguration($gridName)
            {
                $cacheKey = $this->getCacheKey($gridName);
                if ($this->reportCacheManager->contains($cacheKey)) {
                    $config = $this->reportCacheManager->fetch($cacheKey);
                    $config = unserialize($config);
                } else {
                    $config = $this->prepareConfiguration($gridName);
                    $this->reportCacheManager->save($cacheKey, serialize($config));
                }
                return $config;
            }
        }
     ```
#### SecurityBundle
* Class `OroSecurityExtension`<sup>[[?]](https://github.com/oroinc/platform/tree/2.3.0/src/Oro/Bundle/SecurityBundle/Twig/OroSecurityExtension.php "Oro\Bundle\SecurityBundle\Twig\OroSecurityExtension")</sup>
    * method `getSecurityFacade` was replaces with `getAuthorizationChecker` and `getTokenAccessor`
#### TestFrameworkBundle
* Class `TestListener` namespace added, use `TestListener`<sup>[[?]](https://github.com/oroinc/platform/tree/2.3.0/src/Oro/Bundle/TestFrameworkBundle/Test/TestListener.php "Oro\Bundle\TestFrameworkBundle\Test\TestListener")</sup> instead
#### UIBundle
* Updated ChaplinJS to 1.2.0 version
* Updated Autolinker.js to 1.4.* version
* Updated jQuery-Form to 4.2.1 version
* Updated jQuery.Numeric to 1.5.0 version
* Updated Lightgallery.js to 1.4.0 version
* Updated RequireJS test.js plugin to 2.0.* version
* Updated Jquery-UI-Multiselect-Widget to 2.0.1 version
* Updated Timepicker.js plugin to 1.11.* version
* Updated Datepair.js plugin to 0.4.* version
* Updated jQuery.Uniform to 4.2.0 version
#### WorkflowBundle
* Class `WorkflowRegistry`<sup>[[?]](https://github.com/oroinc/platform/tree/2.3.0/src/Oro/Bundle/WorkflowBundle/Model/WorkflowRegistry.php "Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry")</sup>:
    * following protected methods were moved to `WorkflowDefinitionProvider`:
        * `refreshWorkflowDefinition`
        * `getEntityManager`
        * `getEntityRepository`
* Datagrid filter `WorkflowFilter`<sup>[[?]](https://github.com/oroinc/platform/tree/2.3.0/src/Oro/Bundle/WorkflowBundle/Datagrid/Filter/WorkflowFilter.php "Oro\Bundle\WorkflowBundle\Datagrid\Filter\WorkflowFilter")</sup> changed namespace
### Deprecated
#### EmailBundle
* Class `EmailRecipientRepository`<sup>[[?]](https://github.com/oroinc/platform/tree/2.3.0/src/Oro/Bundle/EmailBundle/Entity/Repository/EmailRecipientRepository.php "Oro\Bundle\EmailBundle\Entity\Repository\EmailRecipientRepository")</sup> method `getThreadUniqueRecipients` was marked as deprecated. Use `EmailGridResultHelper::addEmailRecipients` instead
* Class `FolderType`<sup>[[?]](https://github.com/oroinc/platform/tree/2.3.0/src/Oro/Bundle/EmailBundle/Model/FolderType.php "Oro\Bundle\EmailBundle\Model\FolderType")</sup> method `outcomingTypes` was marked as deprecated. Use `outgoingTypes` instead
* Class `EmailExtension`<sup>[[?]](https://github.com/oroinc/platform/tree/2.3.0/src/Oro/Bundle/EmailBundle/Twig/EmailExtension.php "Oro\Bundle\EmailBundle\Twig\EmailExtension")</sup> method `getEmailThreadRecipients` was marked as deprecated. Use `EmailGridResultHelper::addEmailRecipients` instead
#### SecurityBundle
* Interface `AccessLevelOwnershipDecisionMakerInterface`<sup>[[?]](https://github.com/oroinc/platform/tree/2.3.0/src/Oro/Bundle/SecurityBundle/Acl/Extension/AccessLevelOwnershipDecisionMakerInterface.php "Oro\Bundle\SecurityBundle\Acl\Extension\AccessLevelOwnershipDecisionMakerInterface")</sup>
    * method `isGlobalLevelEntity` was marked as deprecated, use method `isOrganization` instead
    * method `isLocalLevelEntity` was marked as deprecated, use method `isBusinessUnit` instead
    * method `isBasicLevelEntity` was marked as deprecated, use method `isUser` instead
    * method `isAssociatedWithGlobalLevelEntity` was marked as deprecated, use method `isAssociatedWithOrganization` instead
    * method `isAssociatedWithLocalLevelEntity` was marked as deprecated, use method `isAssociatedWithBusinessUnit` instead
    * method `isAssociatedWithBasicLevelEntity` was marked as deprecated, use method `isAssociatedWithUser` instead
* Interface `OwnerTreeInterface`<sup>[[?]](https://github.com/oroinc/platform/tree/2.3.0/src/Oro/Bundle/SecurityBundle/Owner/OwnerTreeInterface.php "Oro\Bundle\SecurityBundle\Owner\OwnerTreeInterface")</sup> was renamed to `OwnerTreeBuilderInterface`
    * method `addBasicEntity` was marked as deprecated, use method `addUser` instead
    * method `addGlobalEntity` was marked as deprecated, use method `addUserOrganization` instead
    * method `addLocalEntityToBasic` was marked as deprecated, use method `addUserBusinessUnit` instead
    * method `addDeepEntity` was marked as deprecated, use method `addBusinessUnitRelation` instead
    * method `addLocalEntity` was marked as deprecated, use method `addBusinessUnit` instead
* Interface `OwnershipMetadataInterface`<sup>[[?]](https://github.com/oroinc/platform/tree/2.3.0/src/Oro/Bundle/SecurityBundle/Owner/Metadata/OwnershipMetadataInterface.php "Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface")</sup>
    * method `isBasicLevelOwned` was marked as deprecated, use method `isUserOwned` instead
    * method `isLocalLevelOwned` was marked as deprecated, use method `isBusinessUnitOwned` instead
    * method `isGlobalLevelOwned` was marked as deprecated, use method `isOrganizationOwned` instead
    * method `isSystemLevelOwned` was marked as deprecated
    * method `getGlobalOwnerColumnName` was marked as deprecated, use method `getOrganizationColumnName` instead
    * method `getGlobalOwnerFieldName` was marked as deprecated, use method `getOrganizationFieldName` instead
* Interface `MetadataProviderInterface`<sup>[[?]](https://github.com/oroinc/platform/tree/2.3.0/src/Oro/Bundle/SecurityBundle/Owner/Metadata/MetadataProviderInterface.php "Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface")</sup> was renamed to `OwnershipMetadataProviderInterface`
    * method `getBasicLevelClass` was marked as deprecated, use method `getUserClass` instead
    * method `getLocalLevelClass` was marked as deprecated, use method `getBusinessUnitClass` instead
    * method `getGlobalLevelClass` was marked as deprecated, use method `getOrganizationClass` instead
### Removed
#### EmailBundle
* Class `ExtendClassLoader`<sup>[[?]](https://github.com/oroinc/platform/tree/2.3.0/src/Oro/Bundle/EntityExtendBundle/Tools/ExtendClassLoader.php "Oro\Bundle\EntityExtendBundle\Tools\ExtendClassLoader")</sup> was removed. The `Oro\Component\PhpUtils\ClassLoader` is used instead of it
* service `oro_email.listener.role_subscriber` was removed.
#### IntegrationBundle
* Interface `RestResponseInterface`<sup>[[?]](https://github.com/oroinc/platform/tree/2.3.0/src/Oro/Bundle/IntegrationBundle/Provider/Rest/Client/RestResponseInterface.php "Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface")</sup> was changed. Methods `getRawHeaders`, `xml`, `getRedirectCount`, `getEffectiveUrl` were completely removed
* Interface `RestClientInterface`<sup>[[?]](https://github.com/oroinc/platform/tree/2.3.0/src/Oro/Bundle/IntegrationBundle/Provider/Rest/Client/RestClientInterface.php "Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface")</sup> was changed. Method `getXML` was completely removed.
* Class `GuzzleRestClient`<sup>[[?]](https://github.com/oroinc/platform/tree/2.3.0/src/Oro/Bundle/IntegrationBundle/Provider/Rest/Client/Guzzle/GuzzleRestClient.php "Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Guzzle\GuzzleRestClient")</sup> method `getXML` was removed, please use a simple `get` method instead and convert its result to XML
* Class `GuzzleRestResponse`<sup>[[?]](https://github.com/oroinc/platform/tree/2.3.0/src/Oro/Bundle/IntegrationBundle/Provider/Rest/Client/Guzzle/GuzzleRestResponse.php "Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Guzzle\GuzzleRestResponse")</sup>:
    * Methods `getRawHeaders`, `xml`, `getRedirectCount`, `getEffectiveUrl` were removed, in case you need them just use the construction such as `$response->getSourceResponse()->xml()`
    * Methods `getContentEncoding`, `getContentLanguage`, `getContentLength`, `getContentLocation`, `getContentDisposition`, `getContentMd5`, `getContentRange`, `getContentType`, `isContentType` were removed, but you can get the same values if you use `$response->getHeader('Content-Type')` or `$response->getHeader('Content-MD5')`, for example.
* Removed translation label `oro.integration.sync_error_invalid_credentials`
* Removed translation label `oro.integration.progress`
* Updated translation label `oro.integration.sync_error`
* Updated translation label `oro.integration.sync_error_integration_deactivated`
#### NavigationBundle
* Class `MenuUpdateBuilder`<sup>[[?]](https://github.com/oroinc/platform/tree/2.3.0/src/Oro/Bundle/NavigationBundle/Builder/MenuUpdateBuilder.php "Oro\Bundle\NavigationBundle\Builder\MenuUpdateBuilder")</sup>:
    * abstract service `oro_navigation.menu_update.builder.abstract` was removed, use instead class `MenuUpdateBuilder`
#### SearchBundle
* Class `ReindexDemoDataListener`<sup>[[?]](https://github.com/oroinc/platform/tree/2.3.0/src/Oro/Bundle/SearchBundle/EventListener/ReindexDemoDataListener.php "Oro\Bundle\SearchBundle\EventListener\ReindexDemoDataListener")</sup> was removed. Logic was moved to `ReindexDemoDataFixturesListener`<sup>[[?]](https://github.com/oroinc/platform/tree/2.3.0/src/Oro/Bundle/SearchBundle/EventListener/ReindexDemoDataFixturesListener.php "Oro\Bundle\SearchBundle\EventListener\ReindexDemoDataFixturesListener")</sup>. Service `oro_search.event_listener.reindex_demo_data` was replaced with `oro_search.migration.demo_data_fixtures_listener.reindex`.
#### SecurityBundle
* Class `OwnershipConditionDataBuilder`<sup>[[?]](https://github.com/oroinc/platform/tree/2.3.0/src/Oro/Bundle/SecurityBundle/ORM/Walker/OwnershipConditionDataBuilder.php "Oro\Bundle\SecurityBundle\ORM\Walker\OwnershipConditionDataBuilder")</sup>
    * removed deprecated method `fillOrganizationBusinessUnitIds`
    * removed deprecated method `fillOrganizationUserIds`
* Removed DI container parameter `oro_security.owner.tree.class`
* Removed DI container parameter `oro_security.owner.decision_maker.abstract.class`
* Removed service `oro_security.owner.tree`
* Removed service `oro_security.owner.decision_maker.abstract`
* Removed service `oro_security.link.ownership_tree_provider`
* Class `AbstractMetadataProvider`<sup>[[?]](https://github.com/oroinc/platform/tree/2.3.0/src/Oro/Bundle/SecurityBundle/Owner/Metadata/AbstractMetadataProvider.php "Oro\Bundle\SecurityBundle\Owner\Metadata\AbstractMetadataProvider")</sup> was removed. The logic was moved to `AbstractOwnershipMetadataProvider`
    * changed the constructor signature: old signature was `__construct(array $owningEntityNames)`, new signature is `__construct(ConfigManager $configManager)`
    * removed property `localCache`
    * removed property `owningEntityNames`
    * removed method `setContainer`
    * removed method `getContainer`
    * removed method `getConfigProvider`
    * removed method `getEntityClassResolver`
    * removed method `setAccessLevelClasses`
* Class `ChainMetadataProvider`<sup>[[?]](https://github.com/oroinc/platform/tree/2.3.0/src/Oro/Bundle/SecurityBundle/Owner/Metadata/ChainMetadataProvider.php "Oro\Bundle\SecurityBundle\Owner\Metadata\ChainMetadataProvider")</sup> was removed. Logic was moved to `ChainOwnershipMetadataProvider`
#### TestFrameworkBundle
* Removed `--applicable-suites` parameter from behat. Now every bundle should provide only features that are applicable to any application that includes that bundle.
#### TranslationBundle
* Removed service `oro_translation.distribution.package_manager.link`
#### WorkflowBundle
* Removed service container parameters:
    * `oro_workflow.configuration.config.workflow_sole.class`
    * `oro_workflow.configuration.config.workflow_list.class`
    * `oro_workflow.configuration.handler.step.class`
    * `oro_workflow.configuration.handler.attribute.class`
    * `oro_workflow.configuration.handler.transition.class`
    * `oro_workflow.configuration.handler.workflow.class`
    * `oro_workflow.configuration.config.process_definition_sole.class`
    * `oro_workflow.configuration.config.process_definition_list.class`
    * `oro_workflow.configuration.config.process_trigger_sole.class`
    * `oro_workflow.configuration.config.process_trigger_list.class`
    * `oro_workflow.configuration.provider.workflow_config.class`
    * `oro_workflow.configuration.provider.process_config.class`
    * `oro_workflow.configuration.builder.workflow_definition.class`
    * `oro_workflow.configuration.builder.workflow_definition.handle.class`
    * `oro_workflow.configuration.builder.process_configuration.class`
## 2.2.0 (2017-05-31)
[Show detailed list of changes](incompatibilities-2-2.md)

### Added
#### ApiBundle
* Added the `form_event_subscriber` option to `Resources/config/oro/api.yml`. It can be used to add an event subscriber(s) to the form of such actions as `create`, `update`, `add_relationship`, `update_relationship` and `delete_relationship`. See `/src/Oro/Bundle/ApiBundle/Resources/doc/configuration.md` for more information.
#### WorkflowBundle
* Added processor tag `oro_workflow.processor` and `oro_workflow.processor_bag` service to collect processors.
* Class `WorkflowAwareCache`<sup>[[?]](https://github.com/oroinc/platform/tree/2.2.0/src/Oro/Bundle/WorkflowBundle/EventListener/WorkflowAwareCache.php "Oro\Bundle\WorkflowBundle\EventListener\WorkflowAwareCache")</sup> added:
    * ***purpose***: to check whether an entity has been involved as some workflow related entity in cached manner to avoid DB calls
    * ***methods***:
        - `hasRelatedActiveWorkflows($entity)`
        - `hasRelatedWorkflows($entity)`
    - invalidation of cache occurs on workflow changes events: 
        - `oro.workflow.after_update`
        - `oro.workflow.after_create`
        - `oro.workflow.after_delete`
        - `oro.workflow.activated`
        - `oro.workflow.deactivated`
* Created action `@get_available_workflow_by_record_group` class `GetAvailableWorkflowByRecordGroup`<sup>[[?]](https://github.com/oroinc/platform/tree/2.2.0/src/Oro/Bundle/WorkflowBundle/Model/Action/GetAvailableWorkflowByRecordGroup.php "Oro\Bundle\WorkflowBundle\Model\Action\GetAvailableWorkflowByRecordGroup")</sup>
* Added `variable_definitions` to workflow definition
* Added new `CONFIGURE` permission for workflows
### Changed
#### ApiBundle
* Static class `FormUtil`<sup>[[?]](https://github.com/orocrm/platform/tree/2.1.0/src/Oro/Bundle/ApiBundle/Form/FormUtil.php#L15 "Oro\Bundle\ApiBundle\Form\FormUtil")</sup> was replaced with `FormHelper`<sup>[[?]](https://github.com/orocrm/platform/tree/2.1.0/src/Oro/Bundle/ApiBundle/Form/FormHelper.php "Oro\Bundle\ApiBundle\Form\FormHelper")</sup> which is available as a service `oro_api.form_helper`
* Changed implementation of `CompleteDefinition`<sup>[[?]](https://github.com/orocrm/platform/tree/2.1.0/src/Oro/Bundle/ApiBundle/Processor/Config/Shared/CompleteDefinition.php#L130 "Oro\Bundle\ApiBundle\Processor\Config\Shared\CompleteDefinition")</sup> processor. All logic was moved to the following classes:
    * `CompleteAssociationHelper`<sup>[[?]](https://github.com/orocrm/platform/tree/2.1.0/src/Oro/Bundle/ApiBundle/Processor/Config/Shared/CompleteDefinition/CompleteAssociationHelper.php#L130 "Oro\Bundle\ApiBundle\Processor\Config\Shared\CompleteDefinition\CompleteAssociationHelper")</sup>
    * `CompleteCustomAssociationHelper`<sup>[[?]](https://github.com/orocrm/platform/tree/2.1.0/src/Oro/Bundle/ApiBundle/Processor/Config/Shared/CompleteDefinition/CompleteCustomAssociationHelper.php#L130 "Oro\Bundle\ApiBundle\Processor\Config\Shared\CompleteDefinition\CompleteCustomAssociationHelper")</sup>
    * `CompleteEntityDefinitionHelper`<sup>[[?]](https://github.com/orocrm/platform/tree/2.1.0/src/Oro/Bundle/ApiBundle/Processor/Config/Shared/CompleteDefinition/CompleteEntityDefinitionHelper.php#L130 "Oro\Bundle\ApiBundle\Processor\Config\Shared\CompleteDefinition\CompleteEntityDefinitionHelper")</sup>
    * `CompleteObjectDefinitionHelper`<sup>[[?]](https://github.com/orocrm/platform/tree/2.1.0/src/Oro/Bundle/ApiBundle/Processor/Config/Shared/CompleteDefinition/CompleteObjectDefinitionHelper.php#L130 "Oro\Bundle\ApiBundle\Processor\Config\Shared\CompleteDefinition\CompleteObjectDefinitionHelper")</sup>
#### EmailBundle
* template `Resources/views/Form/autoresponseFields.html.twig` was removed as it contained possibility to add a collection item after an arbitrary item, which is unnecessary with new form
* The following templates were changed:
    * `Resources/views/AutoResponseRule/dialog/update.html.twig`
    * `Resources/views/Configuration/Mailbox/update.html.twig`
    * `EmailBundle/Resources/views/Form/fields.html.twig`
* Class `AutoResponseRuleController`<sup>[[?]](https://github.com/oroinc/platform/tree/2.2.0/src/Oro/Bundle/EmailBundle/Controller/AutoResponseRuleController.php "Oro\Bundle\EmailBundle\Controller\AutoResponseRuleController")</sup>
    * action `update` now returns following data: `form`, `saved`, `data`, `metadata`
#### FormBundle
* Form types `OroEncodedPlaceholderPasswordType`, `OroEncodedPasswordType` acquired `browser_autocomplete` option with default value set to `false`, which means that password autocomplete is off by default.
#### ImportExportBundle
* Class `CliImportMessageProcessor`<sup>[[?]](https://github.com/oroinc/platform/tree/2.2.0/src/Oro/Bundle/ImportExportBundle/Async/Import/CliImportMessageProcessor.php "Oro\Bundle\ImportExportBundle\Async\Import\CliImportMessageProcessor")</sup>
    * does not implement TopicSubscriberInterface now.
    * subscribed topic moved to tag in `mq_processor.yml`.  
    * service `oro_importexport.async.http_import` decorates `oro_importexport.async.import`
* Class `HttpImportMessageProcessor`<sup>[[?]](https://github.com/oroinc/platform/tree/2.2.0/src/Oro/Bundle/ImportExportBundle/Async/Import/HttpImportMessageProcessor.php "Oro\Bundle\ImportExportBundle\Async\Import\HttpImportMessageProcessor")</sup>
    * does not implement TopicSubscriberInterface now.
    * subscribed topic moved to tag in `mq_processor.yml`.  
    * service `oro_importexport.async.cli_import` decorates `oro_importexport.async.import`
#### SegmentBundle
* Class `Oro/Bundle/SegmentBundle/Entity/Manager/StaticSegmentManager`:
    * method `run` now also accepts a dynamic segment
#### WorkflowBundle
* Class `WorkflowItemListener`<sup>[[?]](https://github.com/oroinc/platform/tree/2.2.0/src/Oro/Bundle/WorkflowBundle/EventListener/WorkflowItemListener.php "Oro\Bundle\WorkflowBundle\EventListener\WorkflowItemListener")</sup> auto start workflow part were moved into `WorkflowStartListener`<sup>[[?]](https://github.com/oroinc/platform/tree/2.2.0/src/Oro/Bundle/WorkflowBundle/EventListener/WorkflowStartListener.php "Oro\Bundle\WorkflowBundle\EventListener\WorkflowStartListener")</sup>
### Deprecated
#### SegmentBundle
* Class `Oro/Bundle/SegmentBundle/Entity/Manager/StaticSegmentManager` method `bindParameters` is deprecated and will be removed.
### Removed
#### ActionBundle
* The `ButtonListener`<sup>[[?]](https://github.com/orocrm/platform/tree/2.1.0/src/Oro/Bundle/ActionBundle/Datagrid/EventListener/ButtonListener.php "Oro\Bundle\ActionBundle\Datagrid\EventListener\ButtonListener")</sup> class was removed. Logic was transferred to `DatagridActionButtonProvider`<sup>[[?]](https://github.com/oroinc/platform/blob/2.2.0/src/Oro/Bundle/ActionBundle/Datagrid/Provider/DatagridActionButtonProvider.php "Oro\Bundle\ActionBundle\Datagrid\Provider\DatagridActionButtonProvider")</sup> class.
* Service `oro_action.datagrid.event_listener.button` was removed and new `oro_action.datagrid.action.button_provider` added with tag `oro_datagrid.extension.action.provider`
#### DataGridBundle
* Removed event `oro_datagrid.datagrid.extension.action.configure-actions.before`, now it is a call of `DatagridActionProviderInterface::hasActions`<sup>[[?]](https://github.com/orocrm/platform/tree/2.2.0/src/Oro/Bundle/DataGridBundle/Extension/Action/DatagridActionProviderInterface.php#L13 "Oro\Bundle\DataGridBundle\Extension\Action\DatagridActionProviderInterface")</sup> of registered through a `oro_datagrid.extension.action.provider` tag services.
#### EmailBundle
* Class `AutoResponseRuleType`<sup>[[?]](https://github.com/oroinc/platform/tree/2.2.0/src/Oro/Bundle/EmailBundle/Form/Type/AutoResponseRuleType.php "Oro\Bundle\EmailBundle\Form\Type\AutoResponseRuleType")</sup> form field `conditions` was removed. Use field `definition` instead.
* The `AutoResponseRule::$conditions`<sup>[[?]](https://github.com/orocrm/platform/tree/2.1.0/src/Oro/Bundle/EmailBundle/Entity/AutoResponseRule.php#L46 "Oro\Bundle\EmailBundle\Entity\AutoResponseRule::$conditions")</sup> property was removed. Use methods related to `definition` property instead.
#### ImportExportBundle
* Message topics `oro.importexport.cli_import`, `oro.importexport.import_http_validation`, `oro.importexport.import_http` with the constants were removed.
#### InstallerBundle
* The option `--force` was removed from `oro:install` cli command.
#### PlatformBundle
* Service `jms_serializer.link` was removed.
#### WorkflowBundle
* Class `TransitionCustomFormHandler`<sup>[[?]](https://github.com/oroinc/platform/tree/2.2.0/src/Oro/Bundle/WorkflowBundle/Form/Handler/TransitionCustomFormHandler.php "Oro\Bundle\WorkflowBundle\Form\Handler\TransitionCustomFormHandler")</sup> and service `@oro_workflow.handler.transition.form.page_form` removed (see `CustomFormProcessor`<sup>[[?]](https://github.com/oroinc/platform/tree/2.2.0/src/Oro/Bundle/WorkflowBundle/Processor/Transition/CustomFormProcessor.php "Oro\Bundle\WorkflowBundle\Processor\Transition\CustomFormProcessor")</sup>)
* Class `TransitionFormHandler`<sup>[[?]](https://github.com/oroinc/platform/tree/2.2.0/src/Oro/Bundle/WorkflowBundle/Form/Handler/TransitionFormHandler.php "Oro\Bundle\WorkflowBundle\Form\Handler\TransitionFormHandler")</sup> and service `@oro_workflow.handler.transition.form` removed see replacements:
    * `DefaultFormProcessor`<sup>[[?]](https://github.com/oroinc/platform/tree/2.2.0/src/Oro/Bundle/WorkflowBundle/Processor/Transition/DefaultFormProcessor.php "Oro\Bundle\WorkflowBundle\Processor\Transition\DefaultFormProcessor")</sup>
    * `DefaultFormStartHandleProcessor`<sup>[[?]](https://github.com/oroinc/platform/tree/2.2.0/src/Oro/Bundle/WorkflowBundle/Processor/Transition/DefaultFormStartHandleProcessor.php "Oro\Bundle\WorkflowBundle\Processor\Transition\DefaultFormStartHandleProcessor")</sup>
* Class `TransitionHelper`<sup>[[?]](https://github.com/oroinc/platform/tree/2.2.0/src/Oro/Bundle/WorkflowBundle/Handler/Helper/TransitionHelper.php "Oro\Bundle\WorkflowBundle\Handler\Helper\TransitionHelper")</sup> and service `@oro_workflow.handler.transition_helper` removed (see `FormSubmitTemplateResponseProcessor`<sup>[[?]](https://github.com/oroinc/platform/tree/2.2.0/src/Oro/Bundle/WorkflowBundle/Processor/Transition/Template/FormSubmitTemplateResponseProcessor.php "Oro\Bundle\WorkflowBundle\Processor\Transition\Template\FormSubmitTemplateResponseProcessor")</sup>)
* Class `StartTransitionHandler`<sup>[[?]](https://github.com/oroinc/platform/tree/2.2.0/src/Oro/Bundle/WorkflowBundle/Handler/StartTransitionHandler.php "Oro\Bundle\WorkflowBundle\Handler\StartTransitionHandler")</sup> and service `@oro_workflow.handler.start_transition_handler` removed (see `StartHandleProcessor`<sup>[[?]](https://github.com/oroinc/platform/tree/2.2.0/src/Oro/Bundle/WorkflowBundle/Processor/Transition/StartHandleProcessor.php "Oro\Bundle\WorkflowBundle\Processor\Transition\StartHandleProcessor")</sup>)
* Class `TransitionHandler`<sup>[[?]](https://github.com/oroinc/platform/tree/2.2.0/src/Oro/Bundle/WorkflowBundle/Handler/TransitionHandler.php "Oro\Bundle\WorkflowBundle\Handler\TransitionHandler")</sup> and service `@oro_workflow.handler.transition_handler` removed (see `TransitionHandleProcessor`<sup>[[?]](https://github.com/oroinc/platform/tree/2.2.0/src/Oro/Bundle/WorkflowBundle/Processor/Transition/TransitionHandleProcessor.php "Oro\Bundle\WorkflowBundle\Processor\Transition\TransitionHandleProcessor")</sup>)
* Class `TransitionWidgetHelper`<sup>[[?]](https://github.com/oroinc/platform/tree/2.2.0/src/Oro/Bundle/WorkflowBundle/Helper/TransitionWidgetHelper.php "Oro\Bundle\WorkflowBundle\Helper\TransitionWidgetHelper")</sup>:
    * Constant `TransitionWidgetHelper::DEFAULT_TRANSITION_TEMPLATE`<sup>[[?]](https://github.com/oroinc/platform/tree/2.2.0/src/Oro/Bundle/WorkflowBundle/Helper/TransitionWidgetHelper.php#L0 "Oro\Bundle\WorkflowBundle\Helper\TransitionWidgetHelper::DEFAULT_TRANSITION_TEMPLATE")</sup> moved into `DefaultFormTemplateResponseProcessor::DEFAULT_TRANSITION_TEMPLATE`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/WorkflowBundle/Processor/Transition/Template/DefaultFormTemplateResponseProcessor.php#L0 "Oro\Bundle\WorkflowBundle\Processor\Transition\Template\DefaultFormTemplateResponseProcessor::DEFAULT_TRANSITION_TEMPLATE")</sup>
    * Constant `TransitionWidgetHelper::DEFAULT_TRANSITION_CUSTOM_FORM_TEMPLATE`<sup>[[?]](https://github.com/oroinc/platform/tree/2.2.0/src/Oro/Bundle/WorkflowBundle/Helper/TransitionWidgetHelper.php#L0 "Oro\Bundle\WorkflowBundle\Helper\TransitionWidgetHelper::DEFAULT_TRANSITION_CUSTOM_FORM_TEMPLATE")</sup> moved into `CustomFormTemplateResponseProcessor::DEFAULT_TRANSITION_CUSTOM_FORM_TEMPLATE`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/WorkflowBundle/Processor/Transition/Template/CustomFormTemplateResponseProcessor.php#L0 "Oro\Bundle\WorkflowBundle\Processor\Transition\Template\CustomFormTemplateResponseProcessor::DEFAULT_TRANSITION_CUSTOM_FORM_TEMPLATE")</sup>
* Class `WorkflowReplacementSelectType`<sup>[[?]](https://github.com/oroinc/platform/tree/2.2.0/src/Oro/Bundle/WorkflowBundle/Form/Type/WorkflowReplacementSelectType.php "Oro\Bundle\WorkflowBundle\Form\Type\WorkflowReplacementSelectType")</sup> was removed. Logic was moved to `WorkflowReplacementType`<sup>[[?]](https://github.com/oroinc/platform/tree/2.2.0/src/Oro/Bundle/WorkflowBundle/Form/Type/WorkflowReplacementType.php "Oro\Bundle\WorkflowBundle\Form\Type\WorkflowReplacementType")</sup>
### Fixed
#### ApiBundle
* Fixed handling of `property_path` option from `api.yml` for cases when the property path contains several fields, e.g. `customerAssociation.account`
## 2.1.0 (2017-03-30)
[Show detailed list of changes](incompatibilities-2-1.md)

### Added
#### Action Component
* Added Class `Oro\Component\Action\Model\DoctrineTypeMappingExtension`. That can be used as base for services definitions
#### ActionBundle
* Added new action with alias `resolve_destination_page` and class `ResolveDestinationPage`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/ActionBundle/Action/ResolveDestinationPage.php "Oro\Bundle\ActionBundle\Action\ResolveDestinationPage")</sup>
* Added new tag `oro.action.extension.doctrine_type_mapping` to collect custom doctrine type mappings used to resolve types for serialization at `AttributeGuesser`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/ActionBundle/Model/AttributeGuesser.php "Oro\Bundle\ActionBundle\Model\AttributeGuesser")</sup>
#### BatchBundle
* Added `BufferedIdentityQueryResultIterator`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/BatchBundle/ORM/Query/BufferedIdentityQueryResultIterator.php "Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator")</sup> that allows to iterate through the changing dataset
#### EntityBundle
* Added class `Oro\Bundle\EntityBundle\ORM\DiscriminatorMapListener' that should be used for entities with single table inheritance.
    Example:
```yml
oro_acme.my_entity.discriminator_map_listener:
    class: 'Oro\Bundle\EntityBundle\ORM\DiscriminatorMapListener'
    public: false
    calls:
        - [ addClass, ['oro_acme_entity', '%oro_acme.entity.acme_entity.class%'] ]
    tags:
        - { name: doctrine.event_listener, event: loadClassMetadata }
```
#### FormBundle
* Class `UpdateHandlerFacade`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/FormBundle/Model/UpdateHandlerFacade.php "Oro\Bundle\FormBundle\Model\UpdateHandlerFacade")</sup> added as a replacement of standard `UpdateHandler`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/FormBundle/Model/UpdateHandler.php "Oro\Bundle\FormBundle\Model\UpdateHandler")</sup>. So please consider to use it when for a new entity management development.
* Interface `FormHandlerInterface`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/FormBundle/Form/Handler/FormHandlerInterface.php "Oro\Bundle\FormBundle\Form\Handler\FormHandlerInterface")</sup> added for standard form handlers.
* Class `FormHandler`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/FormBundle/Form/Handler/FormHandler.php "Oro\Bundle\FormBundle\Form\Handler\FormHandler")</sup> added (service 'oro_form.form.handler.default') as default form processing mechanism.
* Tag `oro_form.form.handler` added to register custom form handlers under its `alias`.
* Class `CallbackFormHandler`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/FormBundle/Form/Handler/CallbackFormHandler.php "Oro\Bundle\FormBundle\Form\Handler\CallbackFormHandler")</sup> added as interface compatibility helper for callable.
* Interface `FormTemplateDataProviderInterface`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/FormBundle/Provider/FormTemplateDataProviderInterface.php "Oro\Bundle\FormBundle\Provider\FormTemplateDataProviderInterface")</sup>  added for common update template data population.
* Class `FromTemplateDataProvider`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/FormBundle/Provider/FromTemplateDataProvider.php "Oro\Bundle\FormBundle\Provider\FromTemplateDataProvider")</sup> (service `oro_form.provider.from_template_data.default`) as default update template data provider.
* Tag `oro_form.form_template_data_provider` added to register custom update template data providers.
* Class `FormTemplateDataProviderRegistry`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/FormBundle/Model/FormTemplateDataProviderRegistry.php "Oro\Bundle\FormBundle\Model\FormTemplateDataProviderRegistry")</sup> added to collect tagged with `oro_form.form_template_data_provider` services.
* Class `CallbackFormTemplateDataProvider`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/FormBundle/Provider/CallbackFormTemplateDataProvider.php "Oro\Bundle\FormBundle\Provider\CallbackFormTemplateDataProvider")</sup> added as interface compatibility helper for callable.
#### ImportExportBundle
* Class `FileManager`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/ImportExportBundle/File/FileManager.php "Oro\Bundle\ImportExportBundle\File\FileManager")</sup> and its service `oro_importexport.file.file_manager` were added. We should use it instead of the `FileSystemOperator`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/ImportExportBundle/File/FileSystemOperator.php "Oro\Bundle\ImportExportBundle\File\FileSystemOperator")</sup>
* Command `oro:cron:import-clean-up-storage` (class `CleanupStorageCommand`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/ImportExportBundle/Command/Cron/CleanupStorageCommand.php "Oro\Bundle\ImportExportBundle\Command\Cron\CleanupStorageCommand")</sup>) was added.
#### LayoutBundle
* Added alias `layout` for `oro_layout.layout_manager` service to make it more convenient to access it from container
#### UserBundle
* Added Configurable Permission `default` for View and Edit pages of User Role (see [configurable-permissions.md](./src/Oro/Bundle/SecurityBundle/Resources/doc/configurable-permissions.md))
### Changed
#### ActionBundle
* The service `oro_action.twig.extension.operation` was marked as `private`
#### AddressBundle
* The service `oro_address.twig.extension.phone` was marked as `private`
#### AsseticBundle
* The service `oro_assetic.twig.extension` was marked as `private`
#### AttachmentBundle
* The service `oro_attachment.twig.file_extension` was marked as `private`
* Class `FileManager`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/AttachmentBundle/Manager/FileManager.php "Oro\Bundle\AttachmentBundle\Manager\FileManager")</sup> method `writeStreamToStorage` was changed to `public`
#### ConfigBundle
* The service `oro_config.twig.config_extension` was marked as `private`
#### CurrencyBundle
* The service `oro_currency.twig.currency` was marked as `private`
#### DashboardBundle
* The service `oro_dashboard.twig.extension` was marked as `private`
#### DataGridBundle
* Class `GridController`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/DataGridBundle/Controller/GridController.php "Oro\Bundle\DataGridBundle\Controller\GridController")</sup> renamed method `filterMetadata` to `filterMetadataAction`
* Class `ExportHandler`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/DataGridBundle/Handler/ExportHandler.php "Oro\Bundle\DataGridBundle\Handler\ExportHandler")</sup> (service `oro_datagrid.handler`) changed its service calls: it doesn't call `setRouter` and `setConfigManager` any more but calls `setFileManager` now.
* Topic `oro.datagrid.export` doesn't start datagrid export any more. Use `oro.datagrid.pre_export` topic instead.
* The service `oro_datagrid.twig.datagrid` was marked as `private`
#### DependencyInjection Component
* Class `Oro\Component\DependencyInjection\ServiceLinkRegistry` together with
`Oro\Component\DependencyInjection\ServiceLinkRegistryAwareInterface` for injection awareness. Can be used to provide
injection of a collection of services that are registered in system, but there no need to instantiate
all of them on every runtime. The registry has `@service_container` dependency (`Symfony\Component\DependencyInjection\ContainerInterface`)
and uses `Oro\Component\DependencyInjection\ServiceLink` instances internally. It can register public services by `ServiceLinkRegistry::add`
with `service_id` and `alias`. Later service can be resolved from registry by its alias on demand (method `::get($alias)`).
* Class `Oro\Component\DependencyInjection\Compiler\TaggedServiceLinkRegistryCompilerPass` to easily setup a tag by 
which services will be gathered into `Oro\Component\DependencyInjection\ServiceLinkRegistry` and then injected to 
provided service (usually that implements `Oro\Component\DependencyInjection\ServiceLinkRegistryAwareInterface`).
#### EmailBundle
* Class `AssociationManager`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/EmailBundle/Async/Manager/AssociationManager.php "Oro\Bundle\EmailBundle\Async\Manager\AssociationManager")</sup> changed the return type of `getOwnerIterator` method from `BufferedQueryResultIterator` to `\Iterator`
* The service `oro_email.twig.extension.email` was marked as `private`
#### EmbeddedFormBundle
* The service `oro_embedded_form.back_link.twig.extension` was marked as `private`
#### EntityBundle
* The service `oro_entity.twig.extension.entity` was marked as `private`
#### EntityConfigBundle
* Class `ConfigCache`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/EntityConfigBundle/Config/ConfigCache.php "Oro\Bundle\EntityConfigBundle\Config\ConfigCache")</sup> the implementation was changed significantly, by performance reasons. The most of `protected` methods were removed or marked as `private`
* The service `oro_entity_config.twig.extension.config` was marked as `private`
* The service `oro_entity_config.twig.extension.dynamic_fields_attribute_decorator` was marked as `private`
#### EntityExtendBundle
* Class `ExtendExtension`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/EntityExtendBundle/Migration/Extension/ExtendExtension.php "Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension")</sup>
    * calls to `addManyToManyRelation`, `addManyToOneRelation` methods now create unidirectional relations.
    To create bidirectional relation you _MUST_ call `*InverseRelation` method respectively
    * call to `addOneToManyRelation` creates bidirectional relation according to Doctrine [documentation](http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/association-mapping.html#one-to-many-bidirectional)
    * throw exception when trying to use not allowed option while creating relation in migration
* To be able to create bidirectional relation between entities and use "Reuse existing relation" functionality on UI you _MUST_ select "bidirectional" field while creating relation
* The service `oro_entity_extend.twig.extension.dynamic_fields` was marked as `private`
* The service `oro_entity_extend.twig.extension.enum` was marked as `private`
#### EntityMergeBundle
* The service `oro_entity_merge.twig.extension` was marked as `private`
#### EntityPaginationBundle
* The service `oro_entity_pagination.twig_extension.entity_pagination` was marked as `private`
#### FeatureToggleBundle
* The service `oro_featuretoggle.twig.feature_extension` was marked as `private`
#### FormBundle
* The service `oro_form.twig.form_extension` was marked as `private`
#### HelpBundle
* The service `oro_help.twig.extension` was marked as `private`
#### ImportExportBundle
* Class `ExportMessageProcessor`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/ImportExportBundle/Async/Export/ExportMessageProcessor.php "Oro\Bundle\ImportExportBundle\Async\Export\ExportMessageProcessor")</sup>
    * changed the namespace from `Async`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/ImportExportBundle/Async.php "Oro\Bundle\ImportExportBundle\Async")</sup> to `Export`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/ImportExportBundle/Async/Export.php "Oro\Bundle\ImportExportBundle\Async\Export")</sup>
    * construction signature was changed now it takes next arguments:
        * ExportHandler $exportHandler,
        * JobRunner $jobRunner,
        * DoctrineHelper $doctrineHelper,
        * TokenStorageInterface $tokenStorage,
        * LoggerInterface $logger,
        * JobStorage $jobStorage
* Class `AbstractImportHandler`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/ImportExportBundle/Handler/AbstractImportHandler.php "Oro\Bundle\ImportExportBundle\Handler\AbstractImportHandler")</sup> (service `oro_importexport.handler.import.abstract`) changed its service calls: it doesn't call `setRouter` and `setConfigManager` any more but calls `setReaderChain` now.
* Command `oro:import:csv` (class `ImportCommand`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/ImportExportBundle/Command/ImportCommand.php "Oro\Bundle\ImportExportBundle\Command\ImportCommand")</sup>) was renamed to `oro:import:file`
* Class `ImportExportJobSummaryResultService`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/ImportExportBundle/Async/ImportExportJobSummaryResultService.php "Oro\Bundle\ImportExportBundle\Async\ImportExportJobSummaryResultService")</sup> was renamed to `ImportExportResultSummarizer`. It will be moved after add supporting templates in notification process.
* Route `oro_importexport_import_error_log` with path `/import_export/import-error/{jobId}.log` was renamed to `oro_importexport_job_error_log` with path `/import_export/job-error-log/{jobId}.log`
#### IntegrationBundle
* The service `oro_integration.twig.integration` was marked as `private`
#### LayoutBundle
* Changed default value option name for `page_title` block type, from `text` to `defaultValue`
#### LocaleBundle
* The following services were marked as `private`:
    * `oro_locale.twig.date_format`
    * `oro_locale.twig.locale`
    * `oro_locale.twig.calendar`
    * `oro_locale.twig.address`
    * `oro_locale.twig.number`
    * `oro_locale.twig.localization`
    * `oro_locale.twig.date_time_organization`
* Class `LocalizedFallbackValue`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/LocaleBundle/Entity/LocalizedFallbackValue.php "Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue")</sup> will become not extended in 2.3 release
#### MessageQueue Component
* Unify percentage value for `Job::$jobProgress`. Now 100% is stored as 1 instead of 100.
#### MessageQueueBundle
* The service `oro_message_queue.job.calculate_root_job_status_service` was renamed to `oro_message_queue.job.root_job_status_calculator` and marked as `private`
* The service `oro_message_queue.job.calculate_root_job_progress_service` was renamed to `oro_message_queue.job.root_job_progress_calculator` and marked as `private`
#### MigrationBundle
* The service `oro_migration.twig.schema_dumper` was marked as `private`
#### NavigationBundle
* The following services were marked as `private`:
    * `oro_menu.twig.extension`
    * `oro_navigation.title_service.twig.extension`
#### PlatformBundle
* The service `oro_platform.twig.platform_extension` was marked as `private`
#### ReminderBundle
* The service `oro_reminder.twig.extension` was marked as `private`
#### RequireJSBundle
* The service `oro_requirejs.twig.requirejs_extension` was marked as `private`
#### ScopeBundle
* Class `ScopeManager`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/ScopeBundle/Manager/ScopeManager.php "Oro\Bundle\ScopeBundle\Manager\ScopeManager")</sup>:
    * changed the return type of `findBy` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
    * changed the return type of `findRelatedScopes` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
#### SearchBundle
* `entityManager` instead of `em` should be used in `BaseDriver` children
* `OrmIndexer` should be decoupled from `DbalStorer` dependency
* The service `oro_search.twig.search_extension` was marked as `private`
* The `oro:search:reindex` command now works synchronously by default. Use the `--scheduled` parameter if you need the old, async behaviour
#### SecurityBundle
* Service overriding in compiler pass was replaced by service decoration for next services:
    * `sensio_framework_extra.converter.doctrine.orm`
    * `security.acl.dbal.provider`
    * `security.acl.cache.doctrine`
    * `security.acl.voter.basic_permissions`
* The service `oro_security.twig.security_extension` was marked as `private`
#### SegmentBundle
* The service `oro_segment.twig.extension.segment` was marked as `private`
#### SidebarBundle
* The service `oro_sidebar.twig.extension` was marked as `private`
#### SyncBundle
* The service `oro_wamp.twig.sync_extension` was marked as `private`
#### TagBundle
* The service `oro_tag.twig.tag.extension` was marked as `private`
#### ThemeBundle
* The service `oro_theme.twig.extension` was marked as `private`
#### TranslationBundle
* The service `oro_translation.twig.translation.extension` was marked as `private`
* Added `array $filtersType = []` parameter to the `generate` method, that receives an array of filter types to be applies on the route in order to support filters such as `contains` when generating routes
* Class `AddLanguageType`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/TranslationBundle/Form/Type/AddLanguageType.php "Oro\Bundle\TranslationBundle\Form\Type\AddLanguageType")</sup>
    * Changed parent from type from `locale` to `oro_choice`
* Updated service definition for `oro_translation.extension.transtation_packages_provider` changed publicity to `false`
#### UIBundle
* The following services were marked as `private`:
    * `oro_ui.twig.extension.formatter`
    * `oro_ui.twig.tab_extension`
    * `oro_ui.twig.html_tag`
    * `oro_ui.twig.placeholder_extension`
    * `oro_ui.twig.ui_extension`
#### UserBundle
* The service `oro_user.twig.user_extension` was marked as `private`
* Class `StatusController`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/UserBundle/Controller/StatusController.php "Oro\Bundle\UserBundle\Controller\StatusController")</sup>
    * renamed method `setCurrentStatus` to `setCurrentStatusAction`
    * renamed method `clearCurrentStatus` to `clearCurrentStatusAction`
#### WindowsBundle
* The service `oro_windows.twig.extension` was marked as `private`
#### WorkflowBundle
* The service `oro_workflow.twig.extension.workflow` was marked as `private`
### Deprecated
#### ActionBundle
* `RouteExists`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/ActionBundle/Condition/RouteExists.php "Oro\Bundle\ActionBundle\Condition\RouteExists")</sup> deprecated because of:
    * work with `RouteCollection` is performance consuming
    * it was used to check bundle presence, which could be done with `service_exists`
#### BatchBundle
* `DeletionQueryResultIterator`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/BatchBundle/ORM/Query/DeletionQueryResultIterator.php "Oro\Bundle\BatchBundle\ORM\Query\DeletionQueryResultIterator")</sup> is deprecated. Use `BufferedIdentityQueryResultIterator`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/BatchBundle/ORM/Query/BufferedIdentityQueryResultIterator.php "Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator")</sup> instead
#### CronBundle
* Interface `CronCommandInterface`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/CronBundle/Command/CronCommandInterface.php "Oro\Bundle\CronBundle\Command\CronCommandInterface")</sup>
    * deprecated method `isActive`
#### DataGridBundle
* `DeletionIterableResult`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/DataGridBundle/Datasource/Orm/DeletionIterableResult.php "Oro\Bundle\DataGridBundle\Datasource\Orm\DeletionIterableResult")</sup> is deprecated. Use `BufferedIdentityQueryResultIterator`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/BatchBundle/ORM/Query/BufferedIdentityQueryResultIterator.php "Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator")</sup> instead
#### DistributionBundle
* The method `ErrorHandler::handle`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/DistributionBundle/Error/ErrorHandler.php#L96 "Oro\Bundle\DistributionBundle\Error\ErrorHandler::handle")</sup> is deprecated. Use `ErrorHandler::handleErrors`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/DistributionBundle/Error/ErrorHandler.php#L48 "Oro\Bundle\DistributionBundle\Error\ErrorHandler::handleErrors")</sup> instead.
#### EmailBundle
* The service `oro_email.link.autoresponserule_manager` was marked as deprecated
#### EntityConfigBundle
* The service `oro_entity_config.link.config_manager` was marked as deprecated
#### EntityExtendBundle
* Class `ExtendExtension`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/EntityExtendBundle/Migration/Extension/ExtendExtension.php "Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension")</sup>
    * deprecated `addOneToManyInverseRelation`
#### FormBundle
* Class `UpdateHandler`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/FormBundle/Model/UpdateHandler.php "Oro\Bundle\FormBundle\Model\UpdateHandler")</sup>:
    * marked as deprecated, use `UpdateHandlerFacade`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/FormBundle/Model/UpdateHandlerFacade.php "Oro\Bundle\FormBundle\Model\UpdateHandlerFacade")</sup> (service `oro_form.update_handler`) instead
#### ImportExportBundle
* Class `FileSystemOperator`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/ImportExportBundle/File/FileSystemOperator.php "Oro\Bundle\ImportExportBundle\File\FileSystemOperator")</sup> is deprecated now. Use `FileManager`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/ImportExportBundle/File/FileManager.php "Oro\Bundle\ImportExportBundle\File\FileManager")</sup> instead.
#### LocaleBundle
* Class `ExtendLocalizedFallbackValue`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/LocaleBundle/Model/ExtendLocalizedFallbackValue.php "Oro\Bundle\LocaleBundle\Model\ExtendLocalizedFallbackValue")</sup>
    * deprecated and will be removed in 2.3 release
#### SearchBundle
* `DbalStorer` is deprecated. If you need its functionality, please compose your class with `DBALPersistenceDriverTrait`
* Deprecated services and classes:
    * `oro_search.search.engine.storer`
    * `DbalStorer`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/SearchBundle/Engine/Orm/DbalStorer.php "Oro\Bundle\SearchBundle\Engine\Orm\DbalStorer")</sup>
* Interface `EngineV2Interface`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/SearchBundle/Engine/EngineV2Interface.php "Oro\Bundle\SearchBundle\Engine\EngineV2Interface")</sup> marked as deprecated - please, use `EngineInterface`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/SearchBundle/Engine/EngineInterface.php "Oro\Bundle\SearchBundle\Engine\EngineInterface")</sup> instead
* `PdoMysql`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/SearchBundle/Engine/PdoMysql.php "Oro\Bundle\SearchBundle\Engine\PdoMysql")</sup> `getWords` method is deprecated. All non alphanumeric chars are removed in `BaseDriver`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/SearchBundle/Engine/BaseDriver.php "Oro\Bundle\SearchBundle\Engine\BaseDriver")</sup> `filterTextFieldValue` from fulltext search for MySQL and PgSQL
### Removed
#### AddressBundle
* The parameter `oro_address.twig.extension.phone.class` was removed from DIC
* The service `oro_address.provider.phone.link` was removed
#### AsseticBundle
* The parameter `oro_assetic.twig_extension.class` was removed from DIC
#### AttachmentBundle
* The parameter `oro_attachment.twig.file_extension.class` was removed from DIC
#### ConfigBundle
* The parameter `oro_config.twig_extension.class` was removed from DIC
#### CurrencyBundle
* The parameter `oro_currency.twig.currency.class` was removed from DIC
#### DashboardBundle
* The service `oro_dashboard.widget_config_value.date_range.converter.link` was removed
#### DataGridBundle
* Class `GroupConcat`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/DataGridBundle/Engine/Orm/PdoMysql/GroupConcat.php "Oro\Bundle\DataGridBundle\Engine\Orm\PdoMysql\GroupConcat")</sup> was removed. Use `GroupConcat` from package `oro/doctrine-extensions` instead.
#### EmailBundle
* `Oro/Bundle/EmailBundle/Migrations/Data/ORM/EnableEmailFeature` removed, feature enabled by default
* The parameter `oro_email.twig.extension.email.class` was removed from DIC
#### EmbeddedFormBundle
* The parameter `oro_embedded_form.back_link.twig.extension.class` was removed from DIC
#### EntityBundle
* The parameter `oro_entity.twig.extension.entity.class` was removed from DIC
* The service `oro_entity.fallback.resolver.entity_fallback_resolver.link` was removed
#### EntityConfigBundle
* The parameter `oro_entity_config.twig.extension.config.class` was removed from DIC
#### EntityExtendBundle
* The parameter `oro_entity_extend.twig.extension.dynamic_fields.class` was removed from DIC
* The parameter `oro_entity_extend.twig.extension.enum.class` was removed from DIC
#### EntityMergeBundle
* The parameter `oro_entity_merge.twig.extension.class` was removed from DIC
#### EntityPaginationBundle
* The parameter `oro_entity_pagination.twig_extension.entity_pagination.class` was removed from DIC
#### FormBundle
* The parameter `oro_form.twig.form.class` was removed from DIC
* The parameter `oro_form.twig.js_validation_extension.class` was removed from DIC
* The service `oro_form.twig.js_validation_extension` was removed from DIC
* Class `JsValidationExtension`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/FormBundle/Twig/JsValidationExtension.php "Oro\Bundle\FormBundle\Twig\JsValidationExtension")</sup> was removed. Its functionality was moved to `FormExtension`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/FormBundle/Twig/FormExtension.php "Oro\Bundle\FormBundle\Twig\FormExtension")</sup>
#### HelpBundle
* The parameter `oro_help.twig.extension.class` was removed from DIC
#### ImportExportBundle
* Class `AbstractPreparingHttpImportMessageProcessor`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/ImportExportBundle/Async/Import/AbstractPreparingHttpImportMessageProcessor.php "Oro\Bundle\ImportExportBundle\Async\Import\AbstractPreparingHttpImportMessageProcessor")</sup> and its service `oro_importexport.async.abstract_preparing_http_import` were removed. You can use `PreHttpImportMessageProcessor`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/ImportExportBundle/Async/Import/PreHttpImportMessageProcessor.php "Oro\Bundle\ImportExportBundle\Async\Import\PreHttpImportMessageProcessor")</sup> and `HttpImportMessageProcessor`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/ImportExportBundle/Async/Import/HttpImportMessageProcessor.php "Oro\Bundle\ImportExportBundle\Async\Import\HttpImportMessageProcessor")</sup>.
* Class `PreparingHttpImportMessageProcessor`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/ImportExportBundle/Async/Import/PreparingHttpImportMessageProcessor.php "Oro\Bundle\ImportExportBundle\Async\Import\PreparingHttpImportMessageProcessor")</sup> and its service `oro_importexport.async.preparing_http_import` were removed. You can use `PreHttpImportMessageProcessor`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/ImportExportBundle/Async/Import/PreHttpImportMessageProcessor.php "Oro\Bundle\ImportExportBundle\Async\Import\PreHttpImportMessageProcessor")</sup> and `HttpImportMessageProcessor`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/ImportExportBundle/Async/Import/HttpImportMessageProcessor.php "Oro\Bundle\ImportExportBundle\Async\Import\HttpImportMessageProcessor")</sup>.
* Class `PreparingHttpImportValidationMessageProcessor`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/ImportExportBundle/Async/Import/PreparingHttpImportValidationMessageProcessor.php "Oro\Bundle\ImportExportBundle\Async\Import\PreparingHttpImportValidationMessageProcessor")</sup> and its service `oro_importexport.async.preparing_http_import_validation` were removed. You can use `PreHttpImportMessageProcessor`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/ImportExportBundle/Async/Import/PreHttpImportMessageProcessor.php "Oro\Bundle\ImportExportBundle\Async\Import\PreHttpImportMessageProcessor")</sup> and `HttpImportMessageProcessor`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/ImportExportBundle/Async/Import/HttpImportMessageProcessor.php "Oro\Bundle\ImportExportBundle\Async\Import\HttpImportMessageProcessor")</sup>.
* Class `AbstractChunkImportMessageProcessor`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/ImportExportBundle/Async/Import/AbstractChunkImportMessageProcessor.php "Oro\Bundle\ImportExportBundle\Async\Import\AbstractChunkImportMessageProcessor")</sup> and its service `oro_importexport.async.abstract_chunk_http_import` were removed. You can use `PreHttpImportMessageProcessor`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/ImportExportBundle/Async/Import/PreHttpImportMessageProcessor.php "Oro\Bundle\ImportExportBundle\Async\Import\PreHttpImportMessageProcessor")</sup> and `HttpImportMessageProcessor`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/ImportExportBundle/Async/Import/HttpImportMessageProcessor.php "Oro\Bundle\ImportExportBundle\Async\Import\HttpImportMessageProcessor")</sup>.
* Class `ChunkHttpImportMessageProcessor`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/ImportExportBundle/Async/Import/ChunkHttpImportMessageProcessor.php "Oro\Bundle\ImportExportBundle\Async\Import\ChunkHttpImportMessageProcessor")</sup> and its service `oro_importexport.async.chunck_http_import` were removed. You can use `PreHttpImportMessageProcessor`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/ImportExportBundle/Async/Import/PreHttpImportMessageProcessor.php "Oro\Bundle\ImportExportBundle\Async\Import\PreHttpImportMessageProcessor")</sup> and `HttpImportMessageProcessor`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/ImportExportBundle/Async/Import/HttpImportMessageProcessor.php "Oro\Bundle\ImportExportBundle\Async\Import\HttpImportMessageProcessor")</sup>.
* Class `ChunkHttpImportValidationMessageProcessor`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/ImportExportBundle/Async/Import/ChunkHttpImportValidationMessageProcessor.php "Oro\Bundle\ImportExportBundle\Async\Import\ChunkHttpImportValidationMessageProcessor")</sup> and its service `oro_importexport.async.chunck_http_import_validation` were removed. You can use `PreHttpImportMessageProcessor`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/ImportExportBundle/Async/Import/PreHttpImportMessageProcessor.php "Oro\Bundle\ImportExportBundle\Async\Import\PreHttpImportMessageProcessor")</sup> and `HttpImportMessageProcessor`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/ImportExportBundle/Async/Import/HttpImportMessageProcessor.php "Oro\Bundle\ImportExportBundle\Async\Import\HttpImportMessageProcessor")</sup>.
* Class `CliImportValidationMessageProcessor`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/ImportExportBundle/Async/Import/CliImportValidationMessageProcessor.php "Oro\Bundle\ImportExportBundle\Async\Import\CliImportValidationMessageProcessor")</sup> and its service `oro_importexport.async.cli_import_validation` were removed. You can use `PreCliImportMessageProcessor`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/ImportExportBundle/Async/Import/PreCliImportMessageProcessor.php "Oro\Bundle\ImportExportBundle\Async\Import\PreCliImportMessageProcessor")</sup> and `CliImportMessageProcessor`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/ImportExportBundle/Async/Import/CliImportMessageProcessor.php "Oro\Bundle\ImportExportBundle\Async\Import\CliImportMessageProcessor")</sup>.
* Class `SplitterCsvFiler`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/ImportExportBundle/Splitter/SplitterCsvFiler.php "Oro\Bundle\ImportExportBundle\Splitter\SplitterCsvFiler")</sup> and its service `oro_importexport.splitter.csv` were removed. You can use `BatchFileManager`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/ImportExportBundle/File/BatchFileManager.php "Oro\Bundle\ImportExportBundle\File\BatchFileManager")</sup> instead.
#### InstallerBundle
* The parameter `oro_installer.listener.request.class` was removed from DIC
#### IntegrationBundle
* The parameter `oro_integration.twig.integration.class` was removed from DIC
#### LayoutBundle
* Removed the following parameters from the DI container:
    * `oro_layout.layout_factory_builder.class`
    * `oro_layout.twig.extension.layout.class`
    * `oro_layout.twig.renderer.class`
    * `oro_layout.twig.renderer.engine.class`
    * `oro_layout.twig.layout_renderer.class`
    * `oro_layout.twig.form.engine.class`
#### LocaleBundle
* The service `oro_locale.twig.name` was removed
* The service `oro_translation.event_listener.language_change` was removed
* Removed the following parameters from DIC:
    * `oro_locale.twig.date_format.class`
    * `oro_locale.twig.locale.class`
    * `oro_locale.twig.calendar.class`
    * `oro_locale.twig.date_time.class`
    * `oro_locale.twig.name.class`
    * `oro_locale.twig.address.class`
    * `oro_locale.twig.number.class`
#### MessageQueue Component
* Class `Oro\Component\MessageQueue\Job\CalculateRootJobStatusService` was removed. Logic was transferred to `Oro\Component\MessageQueue\Job\RootJobStatusCalculator`
#### MigrationBundle
* The parameter `oro_migration.twig.schema_dumper.class` was removed from DIC
#### NavigationBundle
* Removed the following parameters from DIC:
    * `oro_menu.twig.extension.class`
    * `oro_navigation.event.master_request_route_listener.class`
    * `oro_navigation.title_service.twig.extension.class`
    * `oro_navigation.title_service.event.request.listener.class`
    * `oro_navigation.twig_hash_nav_extension.class`
#### OrganizationBundle
* Removed the following parameters from DIC:
    * `oro_organization.twig.get_owner.class`
    * `oro_organization.twig.business_units.class`
* The following services were removed:
    * `oro_organization.twig.get_owner`
    * `oro_organization.twig.business_units`
#### PlatformBundle
* The parameter `oro_platform.twig.platform_extension.class` was removed from DIC
#### ReminderBundle
* The parameter `oro_reminder.twig.extension.class` was removed from DIC
#### SearchBundle
* The parameter `oro_search.twig_extension.class` was removed from DIC
#### SecurityBundle
* Next container parameters were removed:
    * `oro_security.acl.voter.class`
    * `oro_security.twig.security_extension.class`
    * `oro_security.twig.security_organization_extension`
    * `oro_security.twig.acl.permission_extension.class`
    * `oro_security.listener.context_listener.class`
    * `oro_security.listener.console_context_listener.class`
* The service `oro_security.twig.security_organization_extension` was removed
* The service `oro_security.twig.acl.permission_extension` was removed
* Class `PermissionExtension`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/SecurityBundle/Twig/Acl/PermissionExtension.php "Oro\Bundle\SecurityBundle\Twig\Acl\PermissionExtension")</sup> was removed
* Class `OroSecurityOrganizationExtension`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/SecurityBundle/Twig/OroSecurityOrganizationExtension.php "Oro\Bundle\SecurityBundle\Twig\OroSecurityOrganizationExtension")</sup> was removed
#### SegmentBundle
* The parameter `oro_segment.twig.extension.segment.class` was removed from DIC
#### SidebarBundle
* The parameter `oro_sidebar.twig.extension.class` was removed from DIC
* The parameter `oro_sidebar.request.handler.class` was removed from DIC
#### SyncBundle
* The parameter `oro_wamp.twig.class` was removed from DIC
* The service `oro_sync.twig.content.tags_extension` was removed
#### TagBundle
* The parameter `oro_tag.twig.tag.extension.class` was removed from DIC
#### TestFrameworkBundle
* `@dbIsolation` annotation removed, applied as default behavior
* `@dbReindex` annotation removed, use `SearchExtensionTrait::clearIndexTextTable`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/SearchBundle/Tests/Functional/SearchExtensionTrait.php#L88 "Oro\Bundle\SearchBundle\Tests\Functional\SearchExtensionTrait::clearIndexTextTable")</sup>
#### ThemeBundle
* The parameter `oro_theme.twig.extension.class` was removed from DIC
#### UIBundle
* Removed the following parameters from DIC:
    * `oro_ui.twig.sort_by.class`
    * `oro_ui.twig.ceil.class`
    * `oro_ui.twig.extension.class`
    * `oro_ui.twig.mobile.class`
    * `oro_ui.twig.widget.class`
    * `oro_ui.twig.date.class`
    * `oro_ui.twig.regex.class`
    * `oro_ui.twig.skype_button.class`
    * `oro_ui.twig.form.class`
    * `oro_ui.twig.formatter.class`
    * `oro_ui.twig.placeholder.class`
    * `oro_ui.twig.tab.class`
    * `oro_ui.twig.content.class`
    * `oro_ui.twig.url.class`
    * `oro_ui.twig.js_template.class`
    * `oro_ui.twig.merge_recursive.class`
    * `oro_ui.twig.block.class`
    * `oro_ui.twig.html_tag.class`
    * `oro_ui.twig.extension.formatter.class`
    * `oro_ui.view.listener.class`
    * `oro_ui.view.content_provider.listener.class`
* Removed the following services:
    * `oro_ui.twig.sort_by_extension`
    * `oro_ui.twig.ceil_extension`
    * `oro_ui.twig.mobile_extension`
    * `oro_ui.twig.form_extension`
    * `oro_ui.twig.view_extension`
    * `oro_ui.twig.formatter_extension`
    * `oro_ui.twig.widget_extension`
    * `oro_ui.twig.date_extension`
    * `oro_ui.twig.regex_extension`
    * `oro_ui.twig.skype_button_extension`
    * `oro_ui.twig.content_extension`
    * `oro_ui.twig.url_extension`
    * `oro_ui.twig.js_template`
    * `oro_ui.twig.merge_recursive`
    * `oro_ui.twig.block`
#### UserBundle
* The parameter `oro_user.twig.user_extension.class` was removed from DIC
#### WindowsBundle
* The parameter `oro_windows.twig.extension.class` was removed from DIC
### Fixed
#### ChainProcessor Component
* Fixed an issue with invalid execution order of processors. The issue was that processors from different groups are intersected. During the fix the calculation of internal priorities of processors was changed, this may affect existing configuration of processors in case if you have common (not bound to any action) processors and ungrouped processors which should work with regular grouped processors.
    The previous priority rules:
    | Processor type | Processor priority | Group priority |
    |----------------|--------------------|----------------|
    | initial common processors | from -255 to 255 |  |
    | initial ungrouped processors | from -255 to 255 |  |
    | grouped processors | from -255 to 255 | from -254 to 252 |
    | final ungrouped processors | from -65535 to -65280 |  |
    | final common processors | from min int to -65536 |  |
    The new priority rules:
    | Processor type | Processor priority | Group priority |
    |----------------|--------------------|----------------|
    | initial common processors | greater than or equals to 0 |  |
    | initial ungrouped processors | greater than or equals to 0 |  |
    | grouped processors | from -255 to 255 | from -255 to 255 |
    | final ungrouped processors | less than 0 |  |
    | final common processors | less than 0 |  |
    So, the new rules means that:
        * common and ungrouped processors with the priority greater than or equals to 0 will be executed before grouped processors
        * common and ungrouped processors with the priority less than 0 will be executed after grouped processors
        * now there are no any magic numbers for priorities of any processors
#### SearchBundle
* Return value types in `SearchQueryInterface`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/SearchBundle/Query/SearchQueryInterface.php "Oro\Bundle\SearchBundle\Query\SearchQueryInterface")</sup> and
`AbstractSearchQuery`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/SearchBundle/Query/AbstractSearchQuery.php "Oro\Bundle\SearchBundle\Query\AbstractSearchQuery")</sup> were fixed to support fluent interface
`Orm`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/SearchBundle/Engine/Orm.php "Oro\Bundle\SearchBundle\Engine\Orm")</sup> `setDrivers` method and `$drivers` and injected directly to `SearchIndexRepository`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/SearchBundle/Entity/Repository/SearchIndexRepository.php "Oro\Bundle\SearchBundle\Entity\Repository\SearchIndexRepository")</sup>
`OrmIndexer`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/SearchBundle/Engine/OrmIndexer.php "Oro\Bundle\SearchBundle\Engine\OrmIndexer")</sup> `setDrivers` method and `$drivers` and injected directly to `SearchIndexRepository`<sup>[[?]](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/SearchBundle/Entity/Repository/SearchIndexRepository.php "Oro\Bundle\SearchBundle\Entity\Repository\SearchIndexRepository")</sup>
## 2.0.0 (2017-01-16)

This changelog references the relevant changes (new features, changes and bugs) done in 2.0 versions.
  * Changed minimum required php version to 5.6
  * PhpUnit 5.7 support
  * Extend fields default mode is `ConfigModel::MODE_READONLY`<sup>[[?]](https://github.com/oroinc/platform/tree/2.0.0/src/Oro/Bundle/EntityConfigBundle/Entity/ConfigModel.php#L0 "Oro\Bundle\EntityConfigBundle\Entity\ConfigModel::MODE_READONLY")</sup>
  * Added support of PHP 7.1

## 1.10.0

This changelog references the relevant changes (new features, changes and bugs) done in 1.10.0 versions.
  * The application has been upgraded to Symfony 2.8 (Symfony 2.8.10 doesn't supported because of [Symfony issue](https://github.com/symfony/symfony/issues/19840))
  * Added support php 7
  * Changed minimum required php version to 5.5.9

## 1.9.0

This changelog references the relevant changes (new features, changes and bugs) done in 1.9.0 versions.
* 1.9.0 (2016-02-15)
 * Inline editing in grids
 * Grid column management
 * New UX for Tags
 * Automated REST API for GET requests
 * Performance improvements
 * Apply range filters for numerical fields in grids
 * Manage field tooltips from the UI
 * Override calendar-view.js in customizations
 * Profiler of duplicated queries
 * Importing layout updates

## 1.8.0

This changelog references the relevant changes (new features, changes and bugs) done in 1.8.0 versions.
* 1.8.0 (2015-08-26)
 * Visual workflow configurator
 * New and extended APIs to work with emails
 * Segmentation based on Data audit
 * Improvements to search
 * Improved filtering on option set attributes, allowing for multiple selections
 * The application has been upgraded to Symfony 2.7 and migrated to Doctrine 2.5
 * Select2 component has been improved to automatically initializes select2 widget
 * Documentation for the new Oro Layout component has been added with examples of use

## 1.7.0

This changelog references the relevant changes (new features, changes and bugs) done in 1.7.0 versions.
* 1.7.0 (2015-04-28)
 * New page layouts and layout themes
 * Added Google single sign-on
 * Added Change or reset users' passwords
 * Added Grid views
 * Dashboard widget configuration
 * Email auto-response in workflow definition

## 1.6.0

This changelog references the relevant changes (new features, changes and bugs) done in 1.6.0 versions.
* 1.6.0 (2015-01-19)
 * Comments to activities.
With this feature, the users will be able to add comments to various record activities, such as calls, notes, calendar events, tasks, and so on, making it possible to leave permanent remarks to particular activities they find important, and even engage in conversations that might come in handy later.
Comments are added to every activity record separately, in a linear thread. In addition to text they might contain a file attachment (1 file/image per comment). Comments may be enabled or disabled for any activity in Entity Management. The ability to add, edit, delete, and view others’ comments is subject to user’s ACL configuration.
 * WYSIWYG rich text editor for emails and notes.
This feature allows users to create rich text emails and notes with the built-in WYSIWYG text editor. It allows to mark text as bold, italic, and underlined; change text color and background; create bullet and numbered lists; insert hyperlinks and chunks of source code.
Rich text editor may be turned off in System configuration—in this case, editor will no longer be available and all previously created rich text pieces will be stripped of any formatting to plain text.

## 1.5.0

This changelog references the relevant changes (new features, changes and bugs) done in 1.5.0 versions.
* 1.5.0 (2014-12-18)
 * Invitations to calendar events.
It is now possible to invite other Oro users to events, send them email notifications about this invitation and receive feedback about their responses or lack thereof.
To invite a user to your event, simply open its edit form and choose guests in a respectively named selector control. After you save the event with invitees, they will receive email notifications about the invitation with a link to their copy of the event in OroCRM. On the view page of that event they will be able to respond to an invitation with three options: Attend, Tentatively attend, and Not attend. Response status (including no response yet) will be displayed on the event tile in the calendar view, and next to the guest's name in the event view. An invitee will be able to change his response after the initial choice, i.e. choose to not attend a previously agreed event. For every response to an invitation, or a change in plans, you (i.e. the organizer of the event) will receive an email notification.
 * System calendars.
This feature allows developers to add so-called System calendars to OroPlatform. Use cases for such calendars include company-wide holiday calendar; organization-wide calendar of conferences and conventions, and so on. (Note that organization calendars will only be available in Enterprise Edition 1.7.0).
These calendars and their events will be automatically added to Calendar views of all users in the entire system. Events of these calendars can be managed on their view forms that are available under System > System Calendars. The permission to add or modify events might be assigned to as many people as needed—e.g. the HR and the office manager.
 * Task calendar.
Task calendar is a special kind of system calendar that displays tasks assigned to the user on the calendar view in addition to calendar events. For now, there is no way to add tasks via the calendar view, but it is possible to edit or delete existing tasks. It is not possible to view other users' task calendars either—only the personal task calendar is available.
The calendar view also features a button that leads to the grid of all tasks, similarly to the existing Events button.
 * Color coding for calendars and calendar events.
The user now may change the color of the calendar from the default one in the calendar actions popup menu. Similarly, the user can change the color of the individual event in its Edit dialogue. A palette of standard colors is offered in both cases, with the option to select a custom color with the color wheel.
Standard palettes for calendars and events may be configured in the system configuration under Display settings > Calendar settings.
 * Other minor changes to calendar view.
It is now possible to turn calendars on and off without removing them from the list by clicking on the colored square or via the popup menu.
Click on the event tile opens its View Event form, not Edit.
 * Calls, Tasks, and Calendar events as entity activities.
This is an expansion to the entity activity feature that was first released with 1.3.0 where we introduced the concept of entity activity to the platform and converted emailing to the activity mechanism. Now we are adding three more ubiquitous user actions to this list: logging calls, creating tasks, and scheduling calendar events.
In order to better accommodate the expanding lot of activities we also have customized the UI for them. Previously, every action/activity had its own button regardless of the number of activities available, so if the admin has enabled a lot of activities, users could easily get confused with a long row of buttons, especially on a low resolution screen. Now all activities and non-activity based actions other than Edit and Delete are conveniently grouped into a single More Actions dropdown button.
 * Record Activities Widget.
The Record Activities Widget replaces the Record Activity block, where activities were listed by their type in separate tabs. Instead of tabs, the widget puts all record activities—emails, calls, tasks, calendar events, etc—in a single paginated list.
The user is able to filter the list by activity type and by date of activity. It is possible to configure the the list to be sorted either by creation date or by last update date.
 * Custom fields without schema update.
It is now possible to add custom fields to entities and immediately use them without schema update. This ability comes with drawbacks: these "serialized" fields can only store textual or numeric data—they cannot be option sets, relations, or files/images; nor they are available in reports or segments. But these fields will be displayed on entity view/add forms, and may be added to grid and export/import profile if necessary.
To create such fields, click Create field button on the entity view page in Entity management, and then choose "Serialized field" in Storage type selector. To create regular field, choose "Table column."
 * Entity records pagination.
This feature allows the user to "remember" a set of entity records that existed on the grid (i.e. with filters applied) when he moves to the view page of any record, and then quickly navigate through these records with a new pagination control that appears in top right corner of the page.
Pagination only works when the user comes to a view page from the main entity grid; in any other case (e.g. search, direct link, grid on another page, segment) the pagination control will not be displayed. Pagination is preserved on a pinned page in both control and in breadcrumbs.

## 1.4.3

This changelog references the relevant changes (new features, changes and bugs) done in 1.4.3 versions.
* 1.4.3 (2014-12-05)
 * List of improvements and fixed bugs
 - Fixed extended entity is set to "false" after oro:entity-config:update with force

## 1.4.2

This changelog references the relevant changes (new features, changes and bugs) done in 1.4.2 versions.
* 1.4.2 (2014-12-02)
 * List of improvements and fixed bugs
 - Implemented form type guessers for custom fields of existing entities
 - Added support of cascade option for association in Extend Extension
 - Fixed insecure content from websockets when HTTPS used
 - Fixed IMAP Sync with date parsing exception
 - Magento Integration: Sensitive data displayed in API request logs
 - Magento Integration: Memory Issue on Error
 - Magento Integration: Duplicated jobs on two way Magento sync

## 1.4.1

This changelog references the relevant changes (new features, changes and bugs) done in 1.4.1 versions.
* 1.4.1 (2014-11-17)
 * List of improvements and fixed bugs
 - Refactor extended entity to prevent class name collisions
 - Implement form type guessers for custom fields of existing entities
 - Use route from config in email address link to avoid potential errors
 - Fixed duplicates of entities during magento import
 - Error in "oro_multiple_entity" if it's used without "default_element" option
 - Lost organization name after upgrade

## 1.4.0

This changelog references the relevant changes (new features, changes and bugs) done in 1.4.0 versions.
* 1.4.0 (2014-10-15)
 * The re-introduction of Channels.
We started the implementation of a new vision for the Channels in 1.3 version and now we bring Channels back, although under a new definition.
The general idea behind channels may be explained as follows: a channel in OroCRM represents an outside source customer and sales data, where "customer" and "sales" must be understood in the broadest sense possible. Depending on the nature of the outside source, the channel may or may not require a data integration.
This new definition leads to multiple noticeable changes across the system.
 * Integration management.
Albeit the Integrations grid still displays all integrations that exist in the system, you now may create only "non-customer" standalone integrations, such as Zendesk integration. The "customer" integrations, such as Magento integration, may be created only in scope of a channel and cannot exist without it.
 * Marketing lists.
Marketing lists serve as the basis for marketing activities, such as email campaigns (see below). They represent a target auditory of the activity—that is, people, who will be contacted when the activity takes place. Marketing lists have little value by themselves; they exist in scope of some marketing campaign and its activities.
Essentially, marketing list is a segment of entities that contain some contact information, such as email or phone number or physical address. Lists are build based on some rules using Oro filtering tool. Similarly to segments, marketing lists can be static or dynamic; the rules are the same. The user can build marketing lists of contacts, Magento customers, leads, etc.
In addition to filtering rules, the user can manually tweak contents of the marketing list by removing items ("subscribers") from it. Removed subscribers will no longer appear in the list even if they fit the conditions. It is possible to move them back in the list, too.
Every subscriber can also unsubscribe from the list. In this case, he will remain in the list, but will no longer receive email campaigns that are sent to this list. Note that subscription status is managed on per-list basis; the same contact might be subscribed to one list and unsubscribed from another.
 * Email campaigns.
Email campaign is a first example of marketing activity implemented in OroCRM. The big picture is following: Every marketing campaign might contain multiple marketing activities, e.g. an email newsletter, a context ad campaign, a targeted phone advertisement. All these activities serve the common goal of the "big" marketing campaign.
In its current implementation, email campaign is a one-time dispatch of an email to a list of subscribers. Hence, the campaign consists of three basic parts:
Recipients—represented by a Marketing list.
Email itself—the user may choose a template, or create a campaign email from scratch.
Sending rules—for now, only one-time dispatch is available.
Email campaign might be tied to a marketing campaign, but it might exist on its own as well.
 * Improved Email templates.
Previously, email templates were used only for email notifications. Now their role is expanded: it is now possible to use templates in email activities to create a new email from the template, and for email campaigns.
Support for variables in templates was extended: in addition to "contextual" variables that were related to attributes of the template entity, templates may include "system-wide" variables like current user's first name, or current time, or name of the organization. It is also possible to create a "generic" template that is not related to any entity; in this case it may contain only system variables.
New templates are subject to ACL and have owner of user type.
 * Other improvements
 <ul><li>Multiple improvements to Web API</li>
 <li>A new implementation of option sets</li>
 <li>Improved grids</li></ul>
 * Community requests.
Here is the list of Community requests that were addressed in this version.
Features & improvements
  <ul><li>#50 Add the way to filter on empty fields</li>
  <li>#116 Add custom templates to workflow transitions</li>
  <li>#118 Extending countries</li>
  <li>#136 Console command for CSV import/export</li>
  <li>#149 New "link" type for datagrid column format</li></ul>
 * Bugs fixed
  <ul><li>#47 Problems with scrolling in iOS 7</li>
  <li>#62 Problems with the Recent Emails widget</li>
  <li>#139 Error 500 after removing unique key of entity</li>
  <li>#158 Update doctrine version to 2.4.4</li></ul>

## 1.4.0-RC1

This changelog references the relevant changes (new features, changes and bugs) done in 1.4.0-RC1 versions.
* 1.4.0-RC1 (2014-09-30)
 * The re-introduction of Channels.
We started the implementation of a new vision for the Channels in 1.3 version and now we bring Channels back, although under a new definition.
The general idea behind channels may be explained as follows: a channel in OroCRM represents an outside source customer and sales data, where "customer" and "sales" must be understood in the broadest sense possible. Depending on the nature of the outside source, the channel may or may not require a data integration.
This new definition leads to multiple noticeable changes across the system.
 * Integration management.
Albeit the Integrations grid still displays all integrations that exist in the system, you now may create only "non-customer" standalone integrations, such as Zendesk integration. The "customer" integrations, such as Magento integration, may be created only in scope of a channel and cannot exist without it.
 * Marketing lists.
Marketing lists serve as the basis for marketing activities, such as email campaigns (see below). They represent a target auditory of the activity—that is, people, who will be contacted when the activity takes place. Marketing lists have little value by themselves; they exist in scope of some marketing campaign and its activities.
Essentially, marketing list is a segment of entities that contain some contact information, such as email or phone number or physical address. Lists are build based on some rules using Oro filtering tool. Similarly to segments, marketing lists can be static or dynamic; the rules are the same. The user can build marketing lists of contacts, Magento customers, leads, etc.
In addition to filtering rules, the user can manually tweak contents of the marketing list by removing items ("subscribers") from it. Removed subscribers will no longer appear in the list even if they fit the conditions. It is possible to move them back in the list, too.
Every subscriber can also unsubscribe from the list. In this case, he will remain in the list, but will no longer receive email campaigns that are sent to this list. Note that subscription status is managed on per-list basis; the same contact might be subscribed to one list and unsubscribed from another.
 * Email campaigns.
Email campaign is a first example of marketing activity implemented in OroCRM. The big picture is following: Every marketing campaign might contain multiple marketing activities, e.g. an email newsletter, a context ad campaign, a targeted phone advertisement. All these activities serve the common goal of the "big" marketing campaign.
In its current implementation, email campaign is a one-time dispatch of an email to a list of subscribers. Hence, the campaign consists of three basic parts:
Recipients—represented by a Marketing list.
Email itself—the user may choose a template, or create a campaign email from scratch.
Sending rules—for now, only one-time dispatch is available.
Email campaign might be tied to a marketing campaign, but it might exist on its own as well.
 * Improved Email templates.
Previously, email templates were used only for email notifications. Now their role is expanded: it is now possible to use templates in email activities to create a new email from the template, and for email campaigns.
Support for variables in templates was extended: in addition to "contextual" variables that were related to attributes of the template entity, templates may include "system-wide" variables like current user's first name, or current time, or name of the organization. It is also possible to create a "generic" template that is not related to any entity; in this case it may contain only system variables.
New templates are subject to ACL and have owner of user type.
 * Other improvements
 <ul><li>Multiple improvements to Web API</li>
 <li>A new implementation of option sets</li>
 <li>Improved grids</li></ul>
 * Community requests.
Here is the list of Community requests that were addressed in this version.
Features & improvements
  <ul><li>#50 Add the way to filter on empty fields</li>
  <li>#116 Add custom templates to workflow transitions</li>
  <li>#118 Extending countries</li>
  <li>#136 Console command for CSV import/export</li>
  <li>#149 New "link" type for datagrid column format</li></ul>
 * Bugs fixed
  <ul><li>#47 Problems with scrolling in iOS 7</li>
  <li>#62 Problems with the Recent Emails widget</li>
  <li>#139 Error 500 after removing unique key of entity</li>
  <li>#158 Update doctrine version to 2.4.4</li></ul>

## 1.3.1

This changelog references the relevant changes (new features, changes and bugs) done in 1.3.1 versions.

* 1.3.1 (2014-08-14)
 * Minimum PHP version: PHP 5.4.9
 * PostgreSQL support
 * Fixed issue: Not entire set of entities is exported
 * Fixed issue: Page crashes when big value is typed into the pagination control
 * Fixed issue: Error 500 on Schema update
 * Other minor issues

## 1.3.0

This changelog references the relevant changes (new features, changes and bugs) done in 1.3.0 versions.

* 1.3.0 (2014-07-23)
 * Redesign of the Navigation panel and left-side menu bar
 * Website event tracking
 * Processes
 * New custom field types for entities: File and Image
 * New control for record lookup (relations)
 * Data import in CSV format

## 1.2.0

This changelog references the relevant changes (new features, changes and bugs) done in 1.2.0 versions.

* 1.2.0 (2014-05-28)
 * Ability to delete Channels
 * Workflow view
 * Reset of Workflow data
 * Line charts in Reports
 * Fixed issues with Duplicated emails
 * Fixed Issue Use of SQL keywords as extended entity field names
 * Fixed Issue Creating one-to-many relationship on custom entity that inverses many-to-one relationship fails
 * Fixed Community requests

## 1.2.0-rc1

This changelog references the relevant changes (new features, changes and bugs) done in 1.2.0 RC1 versions.

* 1.2.0 RC1 (2014-05-12)
 * Ability to delete Channels
 * Workflow view
 * Reset of Workflow data
 * Fixed issues with Duplicated emails
 * Fixed Issue Use of SQL keywords as extended entity field names
 * Fixed Issue Creating one-to-many relationship on custom entity that inverses many-to-one relationship fails

## 1.1.0

This changelog references the relevant changes (new features, changes and bugs) done in 1.1.0 versions.

* 1.1.0 (2014-04-28)
 * Dashboard management
 * Fixed problem with creation of on-demand segments
 * Fixed broken WSSE authentication
 * Fixed Incorrectly calculated totals in grids

## 1.0.1

This changelog references the relevant changes (new features, changes and bugs) done in 1.0.1 versions.

* 1.0.1 (2014-04-18)
 * Issue #3979 � Problems with DB server verification on install
 * Issue #3916 � Memory consumption is too high on installation
 * Issue #3918 � Problems with installation of packages from console
 * Issue #3841 � Very slow installation of packages
 * Issue #3916 � Installed application is not working correctly because of knp-menu version
 * Issue #3839 � Cache regeneration is too slow
 * Issue #3525 � Broken filters on Entity Configuration grid
 * Issue #3974 � Settings are not saved in sidebar widgets
 * Issue #3962 � Workflow window opens with a significant delay
 * Issue #2203 � Incorrect timezone processing in Calendar
 * Issue #3909 � Multi-selection filters might be too long
 * Issue #3899 � Broken link from Opportunity to related Contact Request

## 1.0.0

This changelog references the relevant changes (new features, changes and bugs) done in 1.0.0 versions.

* 1.0.0 (2014-04-01)
 * Workflow management UI
 * Segmentation
 * Reminders
 * Package management
 * Page & Grand totals for grids
 * Proper formatting of Money and Percent values
 * Configurable Sidebars
 * Notification of content changes in the Pinbar

## 1.0.0-rc3

This changelog references the relevant changes (new features, changes and bugs) done in 1.0.0-rc3 versions.

* 1.0.0-rc3 (2014-02-25)
 * Embedded forms
 * CSV export

## 1.0.0-rc2

This changelog references the relevant changes (new features, changes and bugs) done in 1.0.0-rc2 versions.

* 1.0.0-rc2 (2014-01-30)
 * Package management
 * Translations management
 * FontAwesome web-application icons

## 1.0.0-rc1

This changelog references the relevant changes (new features, changes and bugs) done in 1.0.0-rc1 versions.

* 1.0.0-rc1 (2013-12-30)
 * Table reports creation wizard
 * Manageable labels of entities and entity fields
 * Record updates notification
 * Sidebars widgets
 * Mobile Web
 * Package Definition and Management
 * Themes
 * Notifications for owners
 * --force option for oro:install
 * Remove old Grid bundle
 * Basic dashboards

## 1.0.0-beta5

This changelog references the relevant changes (new features, changes and bugs) done in 1.0.0-beta5 versions.

* 1.0.0-beta5 (2013-12-05)
 * ACL management in scope of organization and business unit
 * "Option Set" Field Type for Entity Field
 * Form validation improvements
 * Tabs implementation on entity view pages
 * Eliminated registry js-component
 * Implemented responsive markup on most pages

## 1.0.0-beta4

This changelog references the relevant changes (new features, changes and bugs) done in 1.0.0-beta4 versions.

* 1.0.0-beta4 (2013-11-21)
 * Grid refactoring
 * Form validation improvements
 * Make all entities as Extended
 * JavaScript Tests
 * End support for Internet Explorer 9

## 1.0.0-beta3

This changelog references the relevant changes (new features, changes and bugs) done in 1.0.0-beta3 versions.

* 1.0.0-beta3 (2013-11-11)
 * Upgrade the Symfony framework to version 2.3.6
 * Oro Calendar
 * Email Communication
 * Removed bundle dependencies on application
 * One-to-many and many-to-many relations between extended/custom entities
 * Localizations and Internationalization of input and output

## 1.0.0-beta2

This changelog references the relevant changes (new features, changes and bugs) done in 1.0.0-beta2 versions.

* 1.0.0-beta2 (2013-10-28)
 * Minimum PHP version: PHP 5.4.4
 * Installer enhancements
 * Automatic bundles distribution for application
 * Routes declaration on Bundles level
 * System Help and Tooltips
 * RequireJS optimizer utilization
 * ACL Caching

## 1.0.0-beta1

This changelog references the relevant changes (new features, changes and bugs) done in 1.0.0-beta1 versions.

* 1.0.0-beta1 (2013-09-30)
 * New ACL implementation
 * Emails synchronization via IMAP
 * Custom entities and fields in usage
 * Managing relations between entities
 * Grid views

## 1.0.0-alpha6

This changelog references the relevant changes (new features, changes and bugs) done in 1.0.0-alpha6 versions.

* 1.0.0-alpha6 (2013-09-12)
 * Maintenance Mode
 * WebSocket messaging between browser and the web server
 * Asynchronous Module Definition of JS resources
 * Added multiple sorting for a Grid
 * System configuration

## 1.0.0-alpha5

This changelog references the relevant changes (new features, changes and bugs) done in 1.0.0-alpha5 versions.

* 1.0.0-alpha5 (2013-08-29)
 * Custom entity creation
 * Cron Job
 * Record ownership
 * Grid Improvements
 * Filter Improvements
 * Email Template Improvements
 * Implemented extractor for messages in PHP code
 * Removed dependency on SonataAdminBundle
 * Added possibility to unpin page using pin icon

## 1.0.0-alpha4

This changelog references the relevant changes (new features, changes and bugs) done in 1.0.0-alpha4 versions.

* 1.0.0-alpha4 (2013-07-31)
 * Upgrade Symfony to version 2.3
 * Entity and Entity's Field Management
 * Multiple Organizations and Business Units
 * Transactional Emails
 * Email Templates
 * Tags Management
 * Translations JS files
 * Pin tab experience update
 * Redesigned Page Header
 * Optimized load time of JS resources

## 1.0.0-alpha3

This changelog references the relevant changes (new features, changes and bugs) done in 1.0.0-alpha3 versions.

* 1.0.0-alpha3 (2013-06-27)
 * Placeholders
 * Developer toolbar works with AJAX navigation requests
 * Configuring hidden columns in a Grid
 * Auto-complete form type
 * Added Address Book
 * Localized countries and regions
 * Enhanced data change log with ability to save changes for collections
 * Removed dependency on lib ICU

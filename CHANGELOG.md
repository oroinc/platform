The upgrade instructions are available at [Oro documentation website](https://doc.oroinc.com/backend/setup/upgrade-to-new-version/).

The current file describes significant changes in the code that may affect the upgrade of your customizations.

## 4.2.4

### Added
* Added support for Right To Left UI design see more [Right to Left UI Support](https://doc.oroinc.com/frontend/rtl-support.html). 

#### BatchBundle
* Added \Oro\Bundle\BatchBundle\Step\CumulativeStepExecutor and \Oro\Bundle\BatchBundle\Step\CumulativeItemStep with writer call for empty items.

#### EntityBundle
* Added `\Oro\Bundle\EntityBundle\ORM\DoctrineHelper::getManager` to get manager by name.

#### ImportExportBundle
* Added `oro_importexport.strategy.configurable_import_strategy_helper` with performance improvements to replace `oro_importexport.strategy.import.helper` in strategries.
* Added `\Oro\Bundle\ImportExportBundle\Event\StrategyValidationEvent` to handle validation errors formatting cases.
* Added `\Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableImportStrategyHelper` to improve import performance.
* Added `\Oro\Bundle\ImportExportBundle\Writer\CumulativeWriter` to improve import performance.
* Added `\Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy::isEntityFieldFallbackValue` to support `\Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue` import.
* Added `\Oro\Bundle\ImportExportBundle\Strategy\Import\ImportStrategyHelper::getEntityPropertiesByClassName` to support `\Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableImportStrategyHelper` import.
* Added `\Oro\Bundle\ImportExportBundle\Strategy\Import\ImportStrategyHelper::verifyClass` to support `\Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableImportStrategyHelper` import.

#### LocaleBundle
* Added `\Oro\Bundle\LocaleBundle\EventListener\StrategyValidationEventListener` to format `LocalizedFallbackValue` error keys.

#### MessageQueueBundle
* Added `message_queue` connection.
* Added metadata cache for `message_queue` entity manager.
* Added `\Oro\Bundle\MessageQueueBundle\Platform\{OptionalListenerDriver,OptionalListenerDriverFactory,OptionalListenerExtension}` to bypass optional listeners from CLI to MQ.

#### MessageQueue component
* Added `\Oro\Component\MessageQueue\Consumption\Extension\LimitGarbageCollectionExtension` to limit consumer by GC runs.
* Added `\Oro\Component\MessageQueue\Consumption\Extension\LimitObjectExtension` to limit consumer by objects in runtime.

#### PlatformBundle
* Added \Oro\Bundle\PlatformBundle\DependencyInjection\Compiler\DoctrineTagMethodPass to handle unsupported method definitions for Doctrine events.

#### TestFrameworkBundle
* Optional listeners (except search listeners) disabled in functional tests by default. Use `$this->getOptionalListenerManager()->enableListener('oro_workflow.listener.event_trigger_collector');` to enable listeners in tests.
* Added additional hook for client cleanup - `@beforeResetClient`, use it instead of `@after` for full tests isolation.

### Changed

#### ApiBundle
* Changed connection from `batch` to `message_queue`

#### ImportExportBundle
* Changed step class and writer service for `entity_import_from_csv` to improve import performance.
* Changed `oro_importexport.strategy.add` and all strategies `oro_importexport.strategy.import.helper` implementation to `oro_importexport.strategy.configurable_import_strategy_helper`
* Changed `\Oro\Bundle\ImportExportBundle\Serializer\Normalizer\ScalarFieldDenormalizer` to handle advanced boolean fields cases - yes/no, true/false, 1/0.
* Changed `\Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy::process` to process validation errors gracefully.
* Changed `\Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy::updateRelations` to avoid massive collection changes.
* Changed `\Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy::processValidationErrors` to improve validation errors processing.
* Changed `\Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy::getObjectValue` to support edge cases, like User#roles.

#### LocaleBundle
* Changed `\Oro\Bundle\LocaleBundle\ImportExport\Strategy\LocalizedFallbackValueAwareStrategy` for performance reasons, error keys logic moved to `\Oro\Bundle\LocaleBundle\EventListener\StrategyValidationEventListener`.

#### MessageQueueBundle
* Changed connection from `batch` to `message_queue`

#### SearchBundle
* `oro_search.fulltext_index_manager` to use `doctrine.dbal.search_connection`
* `oro_search.event_listener.orm.fulltext_index_listener` to use `doctrine.dbal.search_connection`

#### TestFrameworkBundle
* Public methods `newBrowserTabIsOpened` and `newBrowserTabIsOpenedAndISwitchToIt` are moved from `Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext` to dedicated context `Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\BrowserTabContext`.

#### PlatformBundle
* Changed \Oro\Bundle\PlatformBundle\DependencyInjection\Compiler\UpdateDoctrineEventHandlersPass to apply default connection (instead of all) for Doctrine events when it's empty in a tag.

### Removed
#### BatchBundle
* Removed `batch` connection, use `message_queue` connection instead.

#### PlatformBundle
* `doctrine.exclude_listener_connections` parameter is no longer in use.

## 4.2.2

### Added
* Added support for Right To Left UI design see more [Right to Left UI Support](https://doc.oroinc.com/frontend/rtl-support.html).

## 4.2.0 (2020-01-29)
[Show detailed list of changes](incompatibilities-4-2.md)

### Added

#### SecurityBundle
* Added `generate_uuid` action. The action generates UUID and puts the value to the specified attribute.

#### TranslationBundle
* Added migration query `Oro\Bundle\TranslationBundle\Migration\DeleteTranslationsByDomainAndKeyPrefixQuery` that can be used to remove unused translations by domain and key prefix.

#### WorkflowBundle
* Added migration query `Oro\Bundle\WorkflowBundle\Migration\RemoveWorkflowAwareEntitiesQuery` that can be used to remove instances of entity created from the specified workflow.
* Added method `Oro\Bundle\WorkflowBundle\Model\WorkflowManager::transitUnconditionally()`. The method transits a workflow item without checking for preconditions and conditions.

### Changed

#### AttachmentBundle
* The service `oro_attachment.manager.media_cache_manager_registry` was renamed to `oro_attachment.media_cache_manager_registry`.
* The service `oro_attachment.provider.attachment_file_name_provider` was renamed to `oro_attachment.provider.file_name`.

#### EntityBundle
* The service `oro_entity.virtual_field_provider.chain` was renamed to `oro_entity.virtual_field_provider`.
* The service `oro_entity.virtual_relation_provider.chain` was renamed to `oro_entity.virtual_relation_provider`.

#### FormBundle
* Upgraded TinyMCE library to version 5.6.0, see [migration guide](https://www.tiny.cloud/blog/how-to-migrate-from-tinymce-4-to-tinymce-5/)
  * Removed the `bdesk_photo` plugin, use the standard `image` plugin and the toolbar button instead.
  * Major UX changes:
    * the default skin of editor 5.6.0
    * popups "add link" and "add image" are not oro-themed
    * fullscreen mode is actual full screen, without the page container limit like previously
    * changed UX for adding embedded image
  * Minor UX changes:
    * editor's width is 100% by default
    * status bar is turned on by default (to allow to resize the editor vertically)
    * the element path is mostly turned off by default. It is turned on only in places where the status bar was enabled before
  
#### PlatformBundle
* The handling of `priority` attribute for `oro_platform.console.global_options_provider` DIC tag
  was changed to correspond Symfony recommendations.
  If you have services with this tag, change the sign of the priority value for them.
  E.g. `{ name: oro_platform.console.global_options_provider, priority: 100 }` should be changed to
  `{ name: oro_platform.console.global_options_provider, priority: -100 }`

#### QueryDesignerBundle
* The class `Oro\Bundle\QueryDesignerBundle\QueryDesigner\AbstractQueryConverter` was refactored to decrease its complexity.
  The state of all query converters was moved to "context" classes.
  The base context class is `Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryConverterContext`.
  If you have own query converters, update them according to new architecture.

#### SecurityBundle
* The handling of `priority` attribute for `oro.security.filter.acl_privilege` DIC tag
  was changed to correspond Symfony recommendations.
  If you have services with this tag, change the sign of the priority value for them.
  E.g. `{ name: oro.security.filter.acl_privilege, priority: 100 }` should be changed to
  `{ name: oro.security.filter.acl_privilege, priority: -100 }`

#### UIBundle

* Moved layout themes build artefacts from `public/layout-build/{theme}` to `public/build/{theme}` folder.
* Moved admin theme build artefacts from `public/build` to `public/build/admin` folder.
* Changed the output path for the admin theme from `css/oro/oro.css` to `css/oro.css`.
* Changed the output path for tinymce CSS entry points from `css/tinymce/*` to `to tinymce/*`.

#### webpack-config-builder

* All the JavaScript dev-dependencies, including webpack, karma, and eslint, are now managed on the application level. As a result, there is no need to install node modules in the `vendor/oro/platform/build` folder anymore. Now the application has only one node_modules folder - in the root directory. This allows application developers to take full control of the dev-dependencies in the project.
* The `webpack-config-builder` module was moved to a separate package and now is published at npmjs.com as `oro-webpack-config-builder` package.
  The package provides an integration of OroPlatform based applications with the Webpack.
* The `public/bundles/npmassets` folder was deleted. This folder contained the full copy of the node_modules folder,
  which is unnecessary with the webpack build. Now you have to reference node modules directly by their names.
  - To migrate the scss code and configuration, replace the `npmassets/` and `bundles/nmpassets` prefixes with `~` for all the node modules paths:

    **assets.yml**
    ```diff
    # ...
        inputs:
    -        - 'bundles/npmassets/slick-carousel/slick/slick.scss'
    +        - '~slick-carousel/slick/slick.scss'
    ```
    **\*.scss**
    ```diff     
     
    - @import "npmassets/bootstrap/scss/variables";
    + @import "~bootstrap/scss/variables";
    
    - @import "bundles/npmassets/bootstrap/scss/variables";
    + @import "~bootstrap/scss/variables";
    ```
  - To migrate the javascript code and configuration, drop `npmassets/` and `bundles/nmpassets` prefixes from the node module path.

    **jsmodules.yml**
    ```diff
    # ...
    - slick$: npmassets/slick-carousel/slick/slick
    + slick$: slick-carousel/slick/slick
    ```
    **\*.js**
    ```diff
    # ... 
    - import 'npmassets/focus-visible/dist/focus-visible';
    + import 'focus-visible/dist/focus-visible';
    # ...
    - require('bundles/npmassets/Base64/base64');
    + require('Base64/base64');
    ```
* To make an NPM library assets publicly available (e.g. some plugins of a library are have to be loaded dynamically in runtime) you can define in your module that an utilized library requires context:
  ```js
  require.context(
    '!file-loader?name=[path][name].[ext]]&outputPath=../_static/&context=tinymce!tinymce/plugins',
    true,
    /.*/
  );
  ```
  This way Webpack will copy `tinymce/plugins` folder into public directory `public/build/_static/_/node_modules/tinymce/plugins`.

  Pay attention for the leading exclamation point, it says that all other loaders (e.g. css-loader) should be ignored for this context.
  If you nevertheless need to process all included css files by Webpack -- leading `!` has to be removed.
* The "oomphinc/composer-installers-extender" composer package was removed. As a result, composer components are not copied automatically to the `public/bundles/components` directory.
  To copy files that are not handled by webpack automatically to the public folder, you can use approach with `require.context` described above.
* The "resolve-url-loader" NPM dependency was removed. Now you should always specify the valid relative or absolute path in SCSS files explicitly. The absolute path must start with `~`:
    ```diff
    # ... 
    # The relative path works the same. You only might need to fix typos, 
    # as the resolve-url-loader ignored them because of the magic global search feature.
    background-image: url(../../img/glyphicons-halflings.png);
    # ...
    # The path without `~` is a relative path
    $icomoon-font-path: "fonts" !default;
    # ... 
    # An absolute path should be prefixed with `~`
    - $icomoon-font-path: "fonts" !default;
    + $icomoon-font-path: "~bundles/orocms/fonts/grapsejs/fonts" !default;
    ```

#### UserBundle
* The following changes were done in the `Oro\Bundle\UserBundle\Provider\RolePrivilegeCategoryProvider` class:
  - the method `getPermissionCategories` was renamed to `getCategories`
  - the method `getTabList` was renamed to `getTabIds`
  - the following methods were removed `getAllCategories`, `getTabbedCategories`, `getCategory`,
    `addProvider`, `getProviders`, `getProviderByName`, `hasProvider`

### Removed

* Package `twig/extensions` is abandoned by its maintainers and has been removed from Oro dependencies.

### FilterBundle
* The outdated filter `selectrow` was removed, as well as `Oro\Bundle\FilterBundle\Filter\SelectRowFilter`
  and `Oro\Bundle\FilterBundle\Form\Type\Filter\SelectRowFilterType` classes.
* The outdated filter `many-to-many` was removed, as well as `Oro\Bundle\FilterBundle\Filter\ManyToManyFilter`
  and `Oro\Bundle\FilterBundle\Form\Type\Filter\ManyToManyFilterType` classes.

#### UserBundle
* The `Oro\Bundle\UserBundle\Provider\PrivilegeCategoryProviderInterface` was removed.
  Use `Resources/config/oro/acl_categories.yml` files to configure ACL categories.

## 4.2.0-rc (2020-11-30)
[Show detailed list of changes](incompatibilities-4-2-rc.md)

### Added

#### ApiBundle
* Implemented support of the `inherit_data` form option for the `nestedObject` data type. It allows to configure
  nested objects even if an entity does not have a setter method for it.

#### LayoutBundle
* Added `is_xml_http_request` option to the Layout context which lets you know if the current request is an ajax request.
* Added two new options `onLoadingCssClass` and `disableControls` to the `layout_subtree_update` block configuration.

### Removed

### SyncBundle
* Removed long-unused the `orosync/js/content/grid-builder` component from the layout updates.

## 4.2.0-beta (2020-09-28)
[Show detailed list of changes](incompatibilities-4-2-beta.md)

## 4.2.0-alpha.3 (2020-07-30)
[Show detailed list of changes](incompatibilities-4-2-alpha-3.md)

### Changed

#### DataGridBundle
* The maximum number of items can be deleted at once during mass delete process was decreased to 100.

#### QueryDesignerBundle
* The class `Oro\Bundle\QueryDesignerBundle\QueryDesigner\FilterProcessor` was renamed to `Oro\Bundle\SegmentBundle\Query\FilterProcessor`.
* The service `oro_query_designer.query_designer.filter_processor` was renamed to `oro_segment.query.filter_processor`.

#### ScopeBundle
* TRIGGER database privilege became required

#### SSOBundle
* The configuration option `oro_sso.enable_google_sso` was renamed to `oro_google_integration.enable_sso`.
* The configuration option `oro_sso.domains` was renamed to `oro_google_integration.sso_domains`.
* The service `oro_sso.oauth_provider` was renamed to `oro_sso.oauth_user_provider`.

#### DataGridBundle
* The maximum number of items can be deleted at once during mass delete process was decreased to 100.

#### UserBundle
* The name for `/api/authstatuses` REST API resource was changed to `/api/userauthstatuses`.

#### UIBundle
* Modules of `jquery-ui` library are now declared separately, and each of them has to be imported directly, if necessary (`jquery-ui/widget`, `jquery-ui/widgets/sortable` etc.)

### Removed

#### CacheBundle
* The service "oro.file_cache.abstract" was removed because it is not used anywhere.

#### EntityExtendBundle
* The `origin` option was removed from entity and field configuration.
* The `ORIGIN_CUSTOM` and `ORIGIN_SYSTEM` constants were removed from `Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope`.
* The `skip-origin` argument was removed from the `oro:entity-extend:update-config` CLI command.

#### ImportExportBundle
* The `unique_job_slug` MQ message parameter was removed for `oro.importexport.pre_import` topic. 

#### UIBundle
* The `collectionField` TWIG macros was removed. Use the `form_row_collection` TWIG function instead.
  Before: `UI.collectionField(form.emails, 'oro.user.emails.label'|trans)`.
  After: `form_row_collection(form.emails)`.
  To change "add" button label use the `add_label` form option.
* Removed `cssVariablesManager.getVariables()` method as unused, and deleted dependency on the [jhildenbiddle/css-vars-ponyfill](https://github.com/jhildenbiddle/css-vars-ponyfill) library. 

## 4.2.0-alpha.2 (2020-05-29)
[Show detailed list of changes](incompatibilities-4-2-alpha-2.md)

### Changed

#### NotificationBundle

* `Oro\Bundle\NotificationBundle\Entity\Event` and
  `Oro\Bundle\NotificationBundle\DependencyInjection\Compiler\RegisterNotificationEventsCompilerPass` classes were deleted.

  To migrate custom notification events, delete all the usages of `Event` and `RegisterNotificationEventsCompilerPass` classes
  and register events with the YAML configuration according to [the documentation](http://doc.oroinc.com/master/backend/bundles/platform/NotificationBundle/notification-event/).

### Added

#### MessageQueueBundle
* Added a possibility to filter messages before they are sent to the message queue.
  See [Filtering Messages in the Message Producer](https://doc.oroinc.com/backend/mq/filtering-messages/).

### Removed

#### ApiBundle
* The class `Oro\Bundle\ApiBundle\ApiDoc\RemoveSingleItemRestRouteOptionsResolver` and the service
  `oro_api.rest.routing_options_resolver.remove_single_item_routes` were removed.
  Exclude the `get` action in `Resources/config/oro/api.yml` instead.

## 4.2.0-alpha (2020-03-30)
[Show detailed list of changes](incompatibilities-4-2-alpha.md)

### Removed

#### UserBundle
* Email template `user_reset_password_as_admin` has been removed. Use `force_reset_password` instead.

## 4.1.0 (2020-01-31)
[Show detailed list of changes](incompatibilities-4-1.md)

### Changed

#### InstallerBundle

* JS dependencies management has been moved from [Asset Packagist](https://asset-packagist.oroinc.com/) to
Composer + NPM solution. So the corresponding Asset Packagist entry in the `repositories` section of `composer.json`
must be removed.

	If there are bower or npm dependencies (packages with names starting with `bower-asset/` or `npm-asset/`) specified in your `composer.json`, then do the following:

	1) for package names starting with `npm-asset/`: remove the `npm-asset/` prefix, move the dependency to the `extra.npm` section
  of `composer.json`;
	2) for package names starting with `bower-asset/`: remove the `bower-asset/` prefix, find the corresponding or alternative
 npm packages instead of bower packages, and add them to the `extra.npm` section of `composer.json`.

	If you have your own `package.json` with npm dependencies, then move them to the `extra.npm` section of `composer.json`.
	If you need a custom script to be executed as well, then you can add your custom script to the `scripts` section of `composer.json`.

#### ConfigBundle
* The handling of `priority` attribute for `oro_config.configuration_search_provider` DIC tag
  was changed to correspond Symfony recommendations.
  If you have services with this tag, change the sign of the priority value for them.
  E.g. `{ name: oro_config.configuration_search_provider, priority: 100 }` should be changed to
  `{ name: oro_config.configuration_search_provider, priority: -100 }`

#### DataGridBundle
* The handling of `priority` attribute for `oro_datagrid.extension.action.provider` and
  `oro_datagrid.extension.mass_action.iterable_result_factory` DIC tags was changed to correspond Symfony recommendations.
  If you have services with these tags, change the sign of the priority value for them.
  E.g. `{ name: oro_datagrid.extension.action.provider, priority: 100 }` should be changed to
  `{ name: oro_datagrid.extension.action.provider, priority: -100 }`

#### TranslationBundle
* The handling of `priority` attribute for `oro_translation.extension.translation_context_resolver` and
  `oro_translation.extension.translation_strategy` DIC tags was changed to correspond Symfony recommendations.
  If you have services with these tags, change the sign of the priority value for them.
  E.g. `{ name: oro_translation.extension.translation_context_resolver, priority: 100 }` should be changed to
  `{ name: oro_translation.extension.translation_context_resolver, priority: -100 }`

#### WorkflowBundle
* The handling of `priority` attribute for `oro.workflow.configuration.handler` and
  `oro.workflow.definition_builder.extension` DIC tags was changed to correspond Symfony recommendations.
  If you have services with these tags, change the sign of the priority value for them.
  E.g. `{ name: oro.workflow.configuration.handler, priority: 100 }` should be changed to
  `{ name: oro.workflow.configuration.handler, priority: -100 }`

### Added

#### AttachmentBundle
* Added *MultiImage* and *MultiField* field types to Entity Manager. Read more in [documentation](https://doc.oroinc.com/bundles/platform/AttachmentBundle/).

### Removed
* `*.class` parameters for all entities were removed from the dependency injection container.
The entity class names should be used directly, e.g. `'Oro\Bundle\EmailBundle\Entity\Email'`
instead of `'%oro_email.email.entity.class%'` (in service definitions, datagrid config files, placeholders, etc.), and
`\Oro\Bundle\EmailBundle\Entity\Email::class` instead of `$container->getParameter('oro_email.email.entity.class')`
(in PHP code).

#### ActivityListBundle
* The `getActivityClass()` method was removed from `Oro\Bundle\ActivityListBundle\Model\ActivityListProviderInterface`.
  Use the `class` attribute of the `oro_activity_list.provider` DIC tag instead.
* The `getAclClass()` method was removed from `Oro\Bundle\ActivityListBundle\Model\ActivityListProviderInterface`.
  Use the `acl_class` attribute of the `oro_activity_list.provider` DIC tag instead.

#### DataGridBundle
* The `getName()` method was removed from `Oro\Bundle\DataGridBundle\Extension\Board\Processor\BoardProcessorInterface`.
  Use the `alias` attribute of the `oro_datagrid.board_processor` DIC tag instead.

#### EntityConfigBundle
* The `getType()` method was removed from `Oro\Bundle\EntityConfigBundle\Attribute\Type\AttributeTypeInterface`.
  Use the `type` attribute of the `oro_entity_config.attribute_type` DIC tag instead.

#### ReminderBundle
* The `getName()` method was removed from `Oro\Bundle\ReminderBundle\Model\SendProcessorInterface`.
  Use the `method` attribute of the `oro_reminder.send_processor` DIC tag instead.

#### RequireJsBundle
* The bundle was completely removed, see [tips](https://doc.oroinc.com/bundles/platform/AssetBundle/#migration-from-requirejs-to-jsmodules) how to migrate to Webpack builder

#### UIBundle
* The `getName()` method was removed from `Oro\Bundle\UIBundle\ContentProvider\ContentProviderInterface`.
  Use the `alias` attribute of the `oro_ui.content_provider` DIC tag instead.
* Unneeded `isEnabled()` and `setEnabled()` methods were removed from `Oro\Bundle\UIBundle\ContentProvider\ContentProviderInterface`.

## 4.1.0-rc (2019-12-10)
[Show detailed list of changes](incompatibilities-4-1-rc.md)

## 4.1.0-beta (2019-09-30)
[Show detailed list of changes](incompatibilities-4-1-beta.md)

### Changed

#### ActivityBundle
* The DIC tag `oro_activity.activity_entity_delete_handler` was removed.
  Use decoration of `oro_activity.activity_entity_delete_handler_extension` service to instead.
* The interface `Oro\Bundle\ActivityBundle\Entity\Manager\ActivityEntityDeleteHandlerInterface` was removed.
  Use `Oro\Bundle\ActivityBundle\Handler\ActivityEntityDeleteHandlerExtensionInterface` instead.

#### ApiBundle
* The section `relations` was removed from `Resources/config/oro/api.yml`. The action `get_relation_config` that
  was responsible to process this section was removed as well.
  This section was not used to build API that conforms JSON:API specification that is the main API type.
  In case if you need a special configuration for "plain" REST API, you can define it in
  `Resources/config/oro/api_plain.yml` configuration files or create a processor for the `get_config` action.
* The `delete_handler` configuration option was removed.
  The `Oro\Bundle\EntityBundle\Handler\EntityDeleteHandlerRegistry` class is used to get the deletion handler instead.
* The class `Oro\Bundle\ApiBundle\Request\ApiActions` was renamed to `Oro\Bundle\ApiBundle\Request\ApiAction`.
* The constant `NORMALIZE_RESULT_GROUP` was removed from
  `Oro\Bundle\ApiBundle\Processor\NormalizeResultActionProcessor`
  Use `NORMALIZE_RESULT` constant from `Oro\Bundle\ApiBundle\Request\ApiActionGroup` instead.
* The following classes were moved from `Oro\Bundle\ApiBundle\Config` namespace to `Oro\Bundle\ApiBundle\Config\Extension`:
    - ConfigExtensionInterface
    - AbstractConfigExtension
    - ConfigExtensionRegistry
    - FeatureConfigurationExtension
    - ActionsConfigExtension
    - FiltersConfigExtension
    - SortersConfigExtension
    - SubresourcesConfigExtension
* The following classes were moved from `Oro\Bundle\ApiBundle\Config` namespace to `Oro\Bundle\ApiBundle\Config\Extra`:
    - ConfigExtraInterface
    - ConfigExtraSectionInterface
    - ConfigExtraCollection
    - CustomizeLoadedDataConfigExtra
    - DataTransformersConfigExtra
    - DescriptionsConfigExtra
    - EntityDefinitionConfigExtra
    - ExpandRelatedEntitiesConfigExtra
    - FilterFieldsConfigExtra
    - FilterIdentifierFieldsConfigExtra
    - FiltersConfigExtra
    - MaxRelatedEntitiesConfigExtra
    - MetaPropertiesConfigExtra
    - RootPathConfigExtra
    - SortersConfigExtra
* The following classes were moved from `Oro\Bundle\ApiBundle\Config` namespace to `Oro\Bundle\ApiBundle\Config\Loader`:
    - ConfigLoaderInterface
    - AbstractConfigLoader
    - ConfigLoaderFactory
    - ConfigLoaderFactoryAwareInterface
    - ActionsConfigLoader
    - EntityDefinitionConfigLoader
    - EntityDefinitionFieldConfigLoader
    - FiltersConfigLoader
    - SortersConfigLoader
    - StatusCodesConfigLoader
    - SubresourcesConfigLoader
* The following classes were moved from `Oro\Bundle\ApiBundle\Metadata` namespace to `Oro\Bundle\ApiBundle\Metadata\Extra`:
    - MetadataExtraInterface
    - MetadataExtraCollection
    - ActionMetadataExtra
    - HateoasMetadataExtra
* All processors from `Oro\Bundle\ApiBundle\Processor\Config\GetConfig`
  and `Oro\Bundle\ApiBundle\Processor\Config\Shared` namespaces were moved
  to `Oro\Bundle\ApiBundle\Processor\GetConfig` namespace.
* The class `ConfigProcessor` was moved from `Oro\Bundle\ApiBundle\Processor\Config` namespace
  to `Oro\Bundle\ApiBundle\Processor\GetConfig` namespace.
* The class `ConfigContext` was moved from `Oro\Bundle\ApiBundle\Processor\Config` namespace
  to `Oro\Bundle\ApiBundle\Processor\GetConfig` namespace.
* The priority of `oro_api.validate_included_forms` processor was changed from `-70` to `-68`.
* The priority of `oro_api.validate_form` processor was changed from `-90` to `-70`.
* The priority of `oro_api.post_validate_included_forms` processor was changed from `-96` to `-78`.
* The priority of `oro_api.post_validate_form` processor was changed from `-97` to `-80`.

#### AssetBundle
* The new feature, Hot Module Replacement (HMR or Hot Reload) enabled for SCSS. To enable HMR for custom CSS links, please [follow the documentation](https://doc.oroinc.com/bundles/platform/AssetBundle/).

#### NavigationBundle
* The service `kernel.listener.nav_history_response` was renamed to `oro_navigation.event_listener.navigation_history`.
* The service `kernel.listener.hashnav_response` was renamed to `oro_navigation.event_listener.hash_navigation`.

#### OrganizationBundle
* The constant `SCOPE_KEY` in `Oro\Bundle\OrganizationBundle\Provider\ScopeOrganizationCriteriaProvider`
  was replaced with `ORGANIZATION`.

#### ScopeBundle
* The method `getCriteriaByContext()` was removed from `Oro\Bundle\ScopeBundle\Manager\ScopeCriteriaProviderInterface`.
* The method `getCriteriaForCurrentScope()` in `Oro\Bundle\ScopeBundle\Manager\ScopeCriteriaProviderInterface`
  was replaced with `getCriteriaValue()`.
* The class `Oro\Bundle\ScopeBundle\Manager\AbstractScopeCriteriaProvider` was removed.
  Use direct implementation of `Oro\Bundle\ScopeBundle\Manager\ScopeCriteriaProviderInterface` in your providers.

#### SecurityBundle
* The interface `Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface`
  was renamed to `Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface`.
  Also methods `getOrganizationContext` and `setOrganizationContext` were renamed to
  `getOrganization` and `setOrganization`.
* The class `Oro\Bundle\SecurityBundle\Exception\ForbiddenException` was removed.
  Use `Symfony\Component\Security\Core\Exception\AccessDeniedException` instead.

#### SoapBundle
* The interface `Oro\Bundle\SoapBundle\Handler\DeleteHandlerInterface` was replaced with
  `Oro\Bundle\EntityBundle\Handler\EntityDeleteHandlerInterface`
  and `Oro\Bundle\EntityBundle\Handler\EntityDeleteHandlerExtensionInterface`.

#### UserBundle
* The constant `SCOPE_KEY` in `Oro\Bundle\UserBundle\Provider\ScopeUserCriteriaProvider`
  was replaced with `USER`.

### Removed

#### All Bundles
* All `*.class` parameters for service definitions were removed from the dependency injection container.

#### Math component
* The deprecated method `Oro\Component\Math\BigDecimal::withScale()` was removed. Use `toScale()` method instead.

#### DataGridBundle
* The DIC parameter `oro_datagrid.extension.orm_sorter.class` was removed.
  If you use `%oro_datagrid.extension.orm_sorter.class%::DIRECTION_ASC`
  or `%oro_datagrid.extension.orm_sorter.class%::DIRECTION_DESC` in `Resources/config/oro/datagrids.yml`,
  replace them to `ASC` and `DESC` strings.
* The deprecated constant `Oro\Bundle\DataGridBundle\Datagrid\Builder::DATASOURCE_PATH` was removed.
  Use `Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration::DATASOURCE_PATH` instead.
* The deprecated constant `Oro\Bundle\DataGridBundle\Datagrid\Builder::DATASOURCE_TYPE_PATH` was removed.
  Use `Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration::DATASOURCE_TYPE_PATH`
  and `Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration::getDatasourceType()` instead.
* The deprecated constant `Oro\Bundle\DataGridBundle\Datagrid\Builder::DATASOURCE_ACL_PATH` was removed.
  Use `Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration::ACL_RESOURCE_PATH`
  and `Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration::getAclResource()` instead.
* The deprecated constant `Oro\Bundle\DataGridBundle\Datagrid\Builder::BASE_DATAGRID_CLASS_PATH` was removed.
  Use `Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration::BASE_DATAGRID_CLASS_PATH` instead.
* The deprecated constant `Oro\Bundle\DataGridBundle\Datagrid\Builder::DATASOURCE_SKIP_ACL_CHECK` was removed.
  Use `Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration::DATASOURCE_SKIP_ACL_APPLY_PATH`
  and `Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration::isDatasourceSkipAclApply()` instead.
* The deprecated constant `Oro\Bundle\DataGridBundle\Datagrid\Builder::DATASOURCE_SKIP_COUNT_WALKER_PATH` was removed.
  Use `Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration::DATASOURCE_SKIP_COUNT_WALKER_PATH` instead.
* The deprecated class `Oro\Bundle\DataGridBundle\Tools\GridConfigurationHelper`
  and service `oro_datagrid.grid_configuration.helper` were removed.

#### EntityConfigBundle
* The deprecated class `Oro\Bundle\EntityConfigBundle\Event\PersistConfigEvent` was removed.
  It was replaced with `Oro\Bundle\EntityConfigBundle\Event\PreFlushConfigEvent`.
* The deprecated class `Oro\Bundle\EntityConfigBundle\Event\FlushConfigEvent` was removed.
  It was replaced with `Oro\Bundle\EntityConfigBundle\Event\PostFlushConfigEvent`.

#### EntityExtendBundle
* Removed *HTML* field type, all HTML fields were converted to Text fields.

#### QueryDesignerBundle
* The deprecated constant `Oro\Bundle\QueryDesignerBundle\Grid\Extension\OrmDatasourceExtension::NAME_PATH` was removed.

#### MigrationBundle
* The deprecated method `Oro\Bundle\MigrationBundle\Migration\Extension\DataStorageExtension::put()` was removed. Use `set()` method instead.
* The deprecated constants `MAIN_FIXTURES_PATH` and `DEMO_FIXTURES_PATH` were removed from `Oro\Bundle\MigrationBundle\Command\LoadDataFixturesCommand`.
  Use `oro_migration.locator.fixture_path_locator` service instead.

#### SoapBundle
* The deprecated `Oro\Bundle\SoapBundle\Request\Parameters\Filter\HttpEntityNameParameterFilter` class was removed. Use `Oro\Bundle\SoapBundle\Request\Parameters\Filter\EntityClassParameterFilter` instead.

#### SecurityBundle
* The deprecated method `Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface::getGlobalOwnerFieldName()` was removed. Use `getOrganizationFieldName()` method instead.

#### TagBundle
* The deprecated constant `Oro\Bundle\TagBundle\Grid\AbstractTagsExtension::GRID_NAME_PATH` was removed.

#### TranslationBundle
* The deprecated option `is_translated_group` for `Symfony\Component\Form\Extension\Core\Type\ChoiceType` was removed.
  Use `translatable_groups` option instead.
* The deprecated option `is_translated_option` for `Symfony\Component\Form\Extension\Core\Type\ChoiceType` was removed.
  Use `translatable_options` option instead.

## 4.0.0 (2019-07-31)
[Show detailed list of changes](incompatibilities-4-0.md)

### Added

#### UIBundle

* CSSVariable parser `oroui/js/css-variables-manager` has been add. Source module [css-variables-manager](./src/Oro/Bundle/UIBundle/Resources/public/js/css-variables-manager.js)

  Github link [https://github.com/jhildenbiddle/css-vars-ponyfill](https://github.com/jhildenbiddle/css-vars-ponyfill)

### Changed

#### UIBundle

* viewportManager has been updated. Add sync with CSS breakpoint variables

### Removed

#### ApiBundle
* All filters and sorters were removed for all "relationships" resources that returns a collection,
  e.g. "GET /api/countries/{id}/relationships/regions". If you need filtered or sorted data, use sub-resources
  instead of relationships, e.g. "GET /api/countries/{id}/regions".

### Changed

#### EntitySerializer component
* The interface `Oro\Component\EntitySerializer\Filter\EntityAwareFilterInterface` was renamed to
  `Oro\Component\EntitySerializer\FieldFilterInterface` and the following changes was made in it:
    - the constants `FILTER_ALL`, `FILTER_VALUE` and `FILTER_NOTHING` were removed
    - the method `checkField` was changed from `checkField(object|array $entity, string $entityClass, string $field): int`
      to `checkField(object $entity, string $entityClass, string $field): ?bool`
* The class `Oro\Component\EntitySerializer\Filter\EntityAwareFilterChain` and the service
  `oro_security.serializer.filter_chain` were removed.
  Use decoration of `oro_security.entity_serializer.field_filter` and/or `oro_api.entity_serializer.field_filter`
  services instead.
* The method `setFieldsFilter` of `Oro\Component\EntitySerializer\EntitySerializer` was renamed to `setFieldFilter`.

#### ApiBundle
* The handling of HTTP response status code `403 Forbidden` was fixed. Now this status code is returned if there are
  no permissions to use an API resource. Before the fix `404 Not Found` status code was returned in both cases,
  when an entity did not exist and when there were no permissions to operate with it.
* The service `oro_api.entity_serializer.acl_filter` was renamed to `oro_api.entity_serializer.field_filter`.
* The method `normalizeObject` of `Oro\Bundle\ApiBundle\Normalizer\ObjectNormalizer`
  was replaced with `normalizeObjects`.

#### SearchBundle
* The following deprecated methods were removed from `Oro\Bundle\SearchBundle\Query\Query`:
    - andWhere
    - orWhere
    - where
    - getOptions
    - setMaxResults
    - getMaxResults
    - setFirstResult
    - getFirstResult
    - setOrderBy
    - getOrderBy
    - getOrderType
    - getOrderDirection
* The deprecated trait `Oro\Bundle\SearchBundle\EventListener\IndexationListenerTrait` was removed.
* The deprecated trait `Oro\Bundle\SearchBundle\Engine\Orm\DBALPersisterDriverTrait` was removed.

#### SecurityBundle
* The class `Oro\Bundle\SecurityBundle\Filter\SerializerFieldFilter` was renamed to
  `Oro\Bundle\SecurityBundle\Filter\EntitySerializerFieldFilter`.
* The service `oro_security.serializer.acl_filter` was renamed to `oro_security.entity_serializer.field_filter`.

## 4.0.0-rc (2019-05-29)
[Show detailed list of changes](incompatibilities-4-0-rc.md)

### Added

#### ApiBundle
* The class `Oro\Bundle\ApiBundle\Request\ValueTransformer` (service ID is `oro_api.value_transformer`) was added
  to help transformation of complex computed values to concrete data-type for API responses.

### Changed

#### ChainProcessor component
* The interface `Oro\Component\ChainProcessor\ProcessorFactoryInterface` was replaced with
  `Oro\Component\ChainProcessor\ProcessorRegistryInterface`.
* The class `Oro\Component\ChainProcessor\ChainProcessorFactory` was removed.
  Use the decoration to create a chain of processor registries instead.

#### Config component
* The methods `load()` and `registerResources()` of class `Oro\Component\Config\Loader\CumulativeConfigLoader`
  were changed to not accept `Symfony\Component\DependencyInjection\ContainerBuilder` as resources container.
  Use `Oro\Component\Config\Loader\ContainerBuilderAdapter` to adapt
  `Symfony\Component\DependencyInjection\ContainerBuilder` to `Oro\Component\Config\ResourcesContainerInterface`.

#### EntitySerializer component
* The method `transform` of `Oro\Component\EntitySerializer\DataTransformerInterface` was changed
  from `transform($class, $property, $value, array $config, array $context)`
  to `transform($value, array $config, array $context)`.
* The class `Oro\Component\EntitySerializer\EntityDataTransformer` was renamed to `Oro\Component\EntitySerializer\DataTransformer`
  and `$baseDataTransformer` property was removed from it.
* The execution of post serialize collection handlers was added to `to-one` associations; now both
  single item and collection post serialize handlers are executed for all types of associations.
* The execution of post serialize handlers was removed for associations in case only ID field is requested for them.

#### ApiBundle
* The parameter `$usePropertyPathByDefault` was removed from `getResultFieldName` method of `Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext`.

#### CacheBundle
* The approach based on `Oro\Bundle\CacheBundle\Loader\ConfigurationLoader` and `Oro\Component\Config\Dumper\CumulativeConfigMetadataDumper` has been replaced with the approach based on `Oro\Component\Config\Cache\PhpConfigProvider`.

#### EmailBundle
* The `Oro\Bundle\EmailBundle\Provider\EmailRenderer` was reimplemented to support computed variables.
  The following changes were made:
    - remove extending of this class from `Twig_Environment`
    - method `renderWithDefaultFilters` was renamed to `renderTemplate`
    - move loading of configuration to `Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateRendererConfigProvider`
    - move rendering of template to `Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateRenderer`
    - move formatting of entity related variables to `Oro\Bundle\EntityBundle\Twig\Sandbox\EntityFormatExtension`
* The interface `Oro\Bundle\EmailBundle\Processor\VariableProcessorInterface` was moved to
  `Oro\Bundle\EntityBundle\Twig\Sandbox\VariableProcessorInterface`.
  The method `process` was changed from `process($variable, array $definition, array $data = [])`
  to `process(string $variable, array $processorArguments, TemplateData $data): void`.
  This allows processors to add computed values.
* The interface `Oro\Bundle\EmailBundle\Provider\SystemVariablesProviderInterface` was moved to
  `Oro\Bundle\EntityBundle\Twig\Sandbox\SystemVariablesProviderInterface`.
  The method `getVariableDefinitions` was changed from `getVariableDefinitions()` to `getVariableDefinitions(): array`.
  The method `getVariableValues` was changed from `getVariableValues()` to `getVariableValues(): array`.
* The interface `Oro\Bundle\EmailBundle\Provider\EntityVariablesProviderInterface` was moved to
  `Oro\Bundle\EntityBundle\Twig\Sandbox\EntityVariablesProviderInterface`.
  The method `getVariableDefinitions` was changed from `getVariableDefinitions($entityClass = null)`
  to `getVariableDefinitions(): array`.
  The method `getVariableGetters` was changed from `getVariableGetters($entityClass = null)`
  to `getVariableGetters(): array`.
  By performance reasons new method `getVariableProcessors(string $entityClass): array` was added.
  If method `getVariableDefinitions` of your provider returns info about processors, move it to `getVariableProcessors`.
* Due to the updated version of `symfony/swiftmailer-bundle` parameter `mailer_transport: mail` is not supported anymore. Using old transport will cause such an exception -
 `Unable to replace alias “swiftmailer.mailer.default.transport.real” with actual definition “mail”.
  You have requested a non-existent service “mail”.` Please
  use `mailer_transport: sendmail` instead or another available swiftmailer transport type.

#### UIBundle
* The redundant methods `getFormatterName`, `getSupportedTypes` and `isDefaultFormatter` were removed from `Oro\Bundle\UIBundle\Formatter\FormatterInterface`.
  Use `data_type` attribute of `oro_formatter` tag to specify the default formatter for the data type.

### Removed

#### DependencyInjection component
* The `ServiceLinkRegistry` and all relates classes was removed.
  To define a bag of lazy loaded services use Symfony [Service Locator](https://symfony.com/doc/3.4/service_container/service_subscribers_locators.html#defining-a-service-locator).
  The list of removed classes:
    - `Oro\Component\DependencyInjection\ServiceLinkRegistry`
    - `Oro\Component\DependencyInjection\ServiceLinkRegistryAwareInterface`
    - `Oro\Component\DependencyInjection\ServiceLinkRegistryAwareTrait`
    - `Oro\Component\DependencyInjection\Compiler\TaggedServiceLinkRegistryCompilerPass`
    - `Oro\Component\DependencyInjection\Exception\UnknownAliasException`

#### ActionBundle
* The deprecated `route_exists` action (class `Oro\Bundle\ActionBundle\Condition\RouteExists`) was removed.

#### ChartBundle
* The possibility to define charts via ChartBundle configuration has been removed. Use `Resources/config/oro/charts.yml` instead.
* Methods `getConfigs()` and `getChartConfigs()` have been removed from `Oro\Bundle\ChartBundle\Model\ConfigProvider`. Use `getChartNames()` and `getChartConfig($chartName)` methods instead.

#### ConfigBundle
* Not used tag `oro_config.configuration_provider` has been removed.

#### DashboardBundle
* The `dashboards`, `widgets` and `widgets_configuration` sections have been removed from DashboardBundle configuration. Use `Resources/config/oro/dashboards.yml` instead.
* Methods `getConfigs()`, `getConfig($key)` and `hasConfig($key)` have been removed from `Oro\Bundle\DashboardBundle\Model\ConfigProvider`.

#### EntityBundle
* The `exclusions`, `entity_aliases`, `entity_alias_exclusions`, `virtual_fields`, `virtual_relations` and `entity_name_formats` sections have been removed from EntityBundle configuration. Use `Resources/config/oro/entity.yml` instead.

#### FilterBundle
* The `datasource` attribute for `oro_filter.extension.orm_filter.filter` tag has been removed as it is redundant.

#### HelpBundle
* The possibility to define `resources`, `vendors` and `routes` sections via HelpBundle configuration has been removed. Use `Resources/config/oro/help.yml` instead.

#### MessageQueueBundle
* The `DefaultTransportFactory` and related configuration option `oro_message_queue.transport.defaut` was removed. Check `config/config.yml` in your application.

#### NavigationBundle
* The possibility to define `menu_config`, `navigation_elements` and `titles` sections via NavigationBundle configuration has been removed. Use `Resources/config/oro/navigation.yml` instead.
* The class `Oro\Bundle\NavigationBundle\Config\MenuConfiguration` has been removed. Use `Oro\Bundle\NavigationBundle\Configuration\ConfigurationProvider` instead.

#### LayoutBundle
* The `themes` section has been removed from LayoutBundle configuration. Use `Resources/views/layouts/{folder}/theme.yml` instead.

#### LocaleBundle
* The `name_format` section has been removed from LocaleBundle configuration. Use `Resources/config/oro/name_format.yml` instead.
* The `address_format` section has been removed from LocaleBundle configuration. Use `Resources/config/oro/address_format.yml` instead.
* The `locale_data` section has been removed from LocaleBundle configuration. Use `Resources/config/oro/locale_data.yml` instead.

#### QueryDesignerBundle
* The `oro_query_designer.query_designer.manager.link` service has been removed. Use `oro_query_designer.query_designer.manager` service instead.

#### SearchBundle
* The `datasource` attribute for `oro_search.extension.search_filter.filter` tag has been removed as it is redundant.
* The `entities_config` section has been removed from SearchBundle configuration. Use `Resources/config/oro/search.yml` instead.
* Not used event `Oro\Bundle\SearchBundle\Event\BeforeMapObjectEvent` has been removed.
* Deprecated DIC parameter `oro_search.entities_config` has been removed. Use `oro_search.provider.search_mapping` service instead of it.

#### SecurityBundle
* The command `security:configurable-permission:load` has been removed.

#### SidebarBundle
* The `sidebar_widgets` section has been removed from SidebarBundle configuration. Use `Resources/public/sidebar_widgets/{folder}/widget.yml` instead.
* The class `Oro\Bundle\SidebarBundle\Model\WidgetDefinitionRegistry` has been renamed to `Oro\Bundle\SidebarBundle\Configuration\WidgetDefinitionProvider`.
* The service `oro_sidebar.widget_definition.registry` has been renamed to `oro_sidebar.widget_definition_provider`.

#### UIBundle
* The `placeholders` and `placeholder_items` sections have been removed from UIBundle configuration. Use `Resources/config/oro/placeholders.yml` instead.
* Deprecated option `show_pin_button_on_start_page` has been removed from UIBundle configuration.

## 4.0.0-beta (2019-03-28)
[Show detailed list of changes](incompatibilities-4-0-beta.md)

### Changed
#### EmailBundle
* In `Oro\Bundle\EmailBundle\Controller\EmailController::checkSmtpConnectionAction`
 (`oro_email_check_smtp_connection` route)
 action the request method was changed to POST.
* In `Oro\Bundle\EmailBundle\Controller\EmailController::purgeEmailsAttachmentsAction`
 (`oro_email_purge_emails_attachments` route)
 action the request method was changed to POST.
* In `Oro\Bundle\EmailBundle\Controller\EmailController::linkAction`
 (`oro_email_attachment_link` route)
 action the request method was changed to POST.
* In `Oro\Bundle\EmailBundle\Controller\EmailController::userEmailsSyncAction`
 (`oro_email_user_sync_emails` route)
 action the request method was changed to POST.
* In `Oro\Bundle\EmailBundle\Controller\EmailController::toggleSeenAction`
 (`oro_email_toggle_seen` route)
 action the request method was changed to POST.
* In `Oro\Bundle\EmailBundle\Controller\EmailController::markSeenAction`
 (`oro_email_mark_seen` route)
 action the request method was changed to POST.
* In `Oro\Bundle\EmailBundle\Controller\EmailController::markAllEmailsAsSeenAction`
 (`oro_email_mark_all_as_seen` route)
 action the request method was changed to POST.

#### EmbeddedFormBundle
* In `Oro\Bundle\EmbeddedFormBundle\Controller\EmbeddedFormController::deleteAction`
 (`oro_embedded_form_delete` route)
 action the request method was changed to DELETE.
* In `Oro\Bundle\EmbeddedFormBundle\Controller\EmbeddedFormController::defaultDataAction`
 (`oro_embedded_form_default_data` route)
 action the request method was changed to POST.

#### EntityBundle
* In `Oro\Bundle\EntityBundle\Controller\EntitiesController::deleteAction`
 (`oro_entity_delete` route)
 action the request method was changed to DELETE.

#### EntityConfigBundle
* In `Oro\Bundle\EntityConfigBundle\Controller\AttributeController::removeAction`
 (`oro_attribute_remove` route)
 action the request method was changed to DELETE.
* In `Oro\Bundle\EntityConfigBundle\Controller\AttributeController::unremoveAction`
 (`oro_attribute_unremove` route)
 action the request method was changed to POST.
* In `Oro\Bundle\EntityConfigBundle\Controller\AttributeFamilyController::deleteAction`
 (`oro_attribute_family_delete` route)
 action the request method was changed to DELETE.

#### EntityExtendBundle
* In `Oro\Bundle\EntityExtendBundle\Controller\ConfigEntityGridController::removeAction`
 (`oro_entityextend_entity_remove` route)
 action the request method was changed to DELETE.
* In `Oro\Bundle\EntityExtendBundle\Controller\ConfigEntityGridController::unremoveAction`
 (`oro_entityextend_field_unremove` route)
 action the request method was changed to POST.

#### ImportExportBundle
* In `Oro\Bundle\ImportExportBundle\Controller\ImportExportController::importValidateAction`
 (`oro_importexport_import_validate` route)
 action the request method was changed to POST.
* In `Oro\Bundle\ImportExportBundle\Controller\ImportExportController::importProcessAction`
 (`oro_importexport_import_process` route)
 action the request method was changed to POST.
* In `Oro\Bundle\ImportExportBundle\Controller\ImportExportController::instantExportAction`
 (`oro_importexport_export_instant` route)
 action the request method was changed to POST.
* Introduced concept of import/export owner. Applied approach with role-based owner-based permissions to the export and import functionality.
* Option `--email` has become required for `oro:import:file` command.
* Removed Message Queue Topics and related Processors. All messages with this topics will be rejected:
    * `oro.importexport.send_import_error_notification`
    * `oro.importexport.import_http_preparing`
    * `oro.importexport.import_http_validation_preparing`
    * `oro.importexport.pre_cli_import`, should be used `oro.importexport.pre_import` instead.
    * `oro.importexport.cli_import` , should be used `oro.importexport.import` instead.

#### IntegrationBundle
* In `Oro\Bundle\IntegrationBundle\Controller\IntegrationController::scheduleAction`
 (`oro_integration_schedule` route)
 action the request method was changed to POST.

#### MessageQueueBundle
* In `Oro\Bundle\MessageQueueBundle\Controller\Api\Rest\JobController::interruptRootJobAction`
 (`/api/rest/{version}/message-queue/job/interrupt/{id}` path)
 action the request method was changed to POST.

#### UserBundle
 * API processor `oro_user.api.create.save_entity` was renamed to `oro_user.api.create.save_user`.

#### WorkflowBundle
* In `Oro\Bundle\WorkflowBundle\Controller\Api\Rest\ProcessController::activateAction`
 (`/api/rest/{version}/process/activate/{processDefinition}` path)
 action the request method was changed to POST.
* In `Oro\Bundle\WorkflowBundle\Controller\Api\Rest\ProcessController::deactivateAction`
 (`/api/rest/{version}/process/deactivate/{processDefinition}` path)
 action the request method was changed to POST.
* In `Oro\Bundle\WorkflowBundle\Controller\Api\Rest\WorkflowController::startAction`
 (`/api/rest/{version}/workflow/start/{workflowName}/{transitionName}` path)
 action the request method was changed to POST.
* In `Oro\Bundle\WorkflowBundle\Controller\Api\Rest\WorkflowController::transitAction`
 (`/api/rest/{version}/workflow/transit/{workflowName}/{transitionName}` path)
 action the request method was changed to POST.
* In `Oro\Bundle\WorkflowBundle\Controller\Api\Rest\WorkflowController::activateAction`
 (`/api/rest/{version}/workflow/activate/{workflowName}/{transitionName}` path)
 action the request method was changed to POST.
* In `Oro\Bundle\WorkflowBundle\Controller\Api\Rest\WorkflowController::deactivateAction`
 (`/api/rest/{version}/workflow/deactivate/{workflowName}/{transitionName}` path)
 action the request method was changed to POST.

### Deprecated
#### ImportExportBundle
* Message Queue Topic `oro.importexport.pre_http_import` is deprecated in favor of `oro.importexport.pre_import`.
* Message Queue Topic `oro.importexport.http_import` is deprecated in favor of `oro.importexport.import`.

### Removed
#### EmbeddedFormBundle
* Layout context parameter `embedded_form_custom_layout` has been removed. Use layout updates instead.

#### UIBundle
* Plugin `jquery.mCustomScrollbar` has been removed. Use [styled-scroll-bar](./src/Oro/Bundle/UIBundle/Resources/public/js/app/plugins/styled-scroll-bar.js)

#### SecurityBundle
* Twig function `resource_granted` has been removed. Use `is_granted` from Symfony instead.

## 3.1.4
[Show detailed list of changes](incompatibilities-3-1-4.md)

### Removed
#### InstallerBundle
* Commands `oro:platform:upgrade20:db-configs` and `oro:platform:upgrade20` were removed because they are no longer used in version 3.x. Related logic was also removed. Use `oro:platform:update` instead.
* Service `oro_installer.namespace_migration` and the logic that used it were removed.

#### WorkflowBundle
* Command `oro:workflow:definitions:upgrade20` was removed because it was used for 2.x version update only.

## 3.1.3 (2019-02-19)
[Show detailed list of changes](incompatibilities-3-1-3.md)

## 3.1.2 (2019-02-05)
[Show detailed list of changes](incompatibilities-3-1-2.md)

## 3.1.0 (2019-01-30)
[Show detailed list of changes](incompatibilities-3-1.md)

### Added
#### AssetBundle
* `AssetBundle` replaces the deprecated `AsseticBundle` to build assets using Webpack.
It currently supports only styles assets. JS assets are still managed by [OroRequireJsBundle](https://doc.oroinc.com/3.1/backend/bundles/platform/RequireJSBundle/).

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
#### InstallerBundle
* Environment variable `ORO_PHP_PATH` is no longer supported for specifying path to PHP executable.

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

#### LocaleBundle
* Removed loading data about currency code and currency symbols from bundle's file `./Resources/config/oro/currency_data.yml`. Now app gets this data from the Intl component by `IntlNumberFormatter`.
If you want to override some symbols, you can decorate `Oro\Bundle\LocaleBundle\Formatter\NumberFormatter::formatCurrency()` method.

## 3.1.0-rc (2018-11-30)
[Show detailed list of changes](incompatibilities-3-1-rc.md)

### Added
#### ApiBundle
* Enable filters for to-many associations. The following operators are implemented: `=` (`eq`), `!=` (`neq`), `*` (`exists`), `!*` (`neq_or_null`), `~` (`contains`) and `!~` (`not_contains`).
* Added [documentation about filters](https://doc.oroinc.com/3.1/backend/api/filters/#api-filters).
* Added data flow diagrams for public actions. See [Actions](https://doc.oroinc.com/3.1/backend/api/actions/#web-api-actions).
* Added `rest_api_prefix` and `rest_api_pattern` configuration options and `oro_api.rest.prefix` and `oro_api.rest.pattern` DIC parameters to be able to reconfigure REST API base path.
* Added trigger `disposeLayout` on DOM element in `layout`
#### DatagridBundle
* Added [Datagrid Settings](https://github.com/oroinc/platform/blob/3.1/src/Oro/Bundle/DataGridBundle/Resources/doc/frontend/datagrid_settings.md) functionality for flexible managing of filters and grid columns

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
* Removed the `loadBeforeAction` and `addToReuse` static methods of `BaseController` in JS. Global Views and Components can now be defined in the HTML over data attributes, the same way as an ordinary [Page Component](https://github.com/oroinc/platform/blob/3.1/src/Oro/Bundle/UIBundle/Resources/doc/reference/page-component.md).
#### SecurityBundle
* Removed `oro_security.acl_helper.process_select.after` event, create [Access Rule](https://github.com/oroinc/platform/blob/3.1/src/Oro/Bundle/SecurityBundle/Resources/doc/access-rules.md) instead.
* Removed `Oro\Bundle\SecurityBundle\ORM\Walker\AclWalker`, `Oro\Bundle\SecurityBundle\ORM\Walker\Condition\AclConditionInterface`, `Oro\Bundle\SecurityBundle\ORM\Walker\Condition\AclCondition`, `Oro\Bundle\SecurityBundle\ORM\Walker\Condition\JoinAclCondition`, `Oro\Bundle\SecurityBundle\ORM\Walker\Condition\JoinAssociationCondition`, `Oro\Bundle\SecurityBundle\ORM\Walker\Condition\AclConditionStorage`, `Oro\Bundle\SecurityBundle\ORM\Walker\Condition\SubRequestAclConditionStorage` and `Oro\Bundle\SecurityBundle\ORM\Walker\AclConditionalFactorBuilder` classes because now ACL restrictions applies with Access Rules by `Oro\Bundle\SecurityBundle\ORM\Walker\AccessRuleWalker`.
* Removed `Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper::applyAclToCriteria` method. Please use `apply` method with Doctrine Query or Query builder instead.
#### DatagridBundle
* Removed all logic related with column manager. The logic of column manager was transformed and expanded in [Datagrid Settings](https://github.com/oroinc/platform/blob/3.1/src/Oro/Bundle/DataGridBundle/Resources/doc/frontend/datagrid_settings.md)
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
* Added the following operators for ComparisonFilter: `*` (`exists`), `!*` (`neq_or_null`), `~` (`contains`), `!~` (`not_contains`), `^` (`starts_with`), `!^` (`not_starts_with`), `$` (`ends_with`), `!$` (`not_ends_with`). For details see [how_to.md](https://doc.oroinc.com/3.1/backend/api/how-to/#advanced-operators-for-string-filter).
* Added the `case_insensitive` and `value_transformer` options for ComparisonFilter. See [how_to.md](https://doc.oroinc.com/3.1/backend/api/how-to/#enable-case-insensitive-string-filter) for more details.

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
* Added a possibility to enable custom API. See [how_to.md](https://doc.oroinc.com/3.1/backend/api/how-to/#enable-custom-api) for more information.

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
* Removed twig filter `oro_html_tag_trim`; use `oro_html_escape` instead. See [documentation](https://doc.oroinc.com/3.1/backend/bundles/platform/UIBundle/twig-filters/#oro-html-escape).
* Removed twig filter `oro_html_purify`; use `oro_html_strip_tags` instead. See [documentation](https://doc.oroinc.com/3.1/backend/bundles/platform/UIBundle/twig-filters/#oro-html-strip-tags).

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
* Twig filter `oro_tag_filter` was renamed to `oro_html_strip_tags`. See [documentation](https://doc.oroinc.com/3.1/backend/bundles/platform/UIBundle/twig-filters/#oro-html-strip-tags).

#### UserBundle
* The `oro_rest_api_get_user_profile` route was removed; use the `oro_rest_api_user_profile` route instead.
* The `Oro\Bundle\UserBundle\Api\Routing\UserProfileRestRouteOptionsResolver` and the `Oro\Bundle\UserBundle\Api\ApiDoc\UserProfileRestRouteOptionsResolver` route option resolvers were removed in favor of [routing.yml](https://doc.oroinc.com/3.1/backend/api/how-to/#add-a-custom-route).

## 2.6.0 (2018-01-31)
[Show detailed list of changes](incompatibilities-2-6.md)

### Added
#### ConfigBundle
* Added the configuration search provider functionality (see [documentation](https://github.com/oroinc/platform/blob/2.6/src/Oro/Bundle/ConfigBundle/Resources/doc/system_configuration.md#search-type-provider))
    * Service should be registered as a service with the `oro_config.configuration_search_provider` tag.
    * Class should implement `Oro\Bundle\ConfigBundle\Provider\SearchProviderInterface` interface.
#### EntityBundle
* Added the `oro_entity.structure.options` event (see [documentation](https://github.com/oroinc/platform/blob/2.6/src/Oro/Bundle/EntityBundle/Resources/doc/events.md#entity-structure-options-event))
* Added the `Oro\Bundle\EntityBundle\Provider\EntityStructureDataProvider`provider to retrieve data of entities structure (see [documentation](https://github.com/oroinc/platform/blob/2.6/src/Oro/Bundle/EntityBundle/Resources/doc/entity_structure_data_provider.md))
* Added JS `EntityModel`[[?]](https://github.com/oroinc/platform/tree/2.6.0/src/Oro/Bundle/EntityBundle/Resources/public/js/app/models/entity-model.js) (see [documentation](https://github.com/oroinc/platform/blob/2.6/src/Oro/Bundle/EntityBundle/Resources/doc/client-side/entity-model.md))
* Added JS `EntityStructureDataProvider`[[?]](https://github.com/oroinc/platform/tree/2.6.0/src/Oro/Bundle/EntityBundle/Resources/public/js/app/services/entity-structure-data-provider.js) (see [documentation](https://github.com/oroinc/platform/blob/2.6/src/Oro/Bundle/EntityBundle/Resources/doc/client-side/entity-structure-data-provider.md))
* Added `FieldChoiceView`[[?]](https://github.com/oroinc/platform/tree/2.6.0/src/Oro/Bundle/EntityBundle/Resources/public/js/app/views/field-choice-view.js) Backbone view, as replacement for jQuery widget `oroentity.fieldChoice`.
#### EntityExtendBundle
* The `Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper::convertName` method was renamed to `convertEnumNameToCode`, visibility of this method was changed from `public` to `private` and it will throw an exception when the `iconv` function fails on converting the input string, instead of hashing the input string.
#### QueryDesignerBundle
* Added `FunctionChoiceView`[[?]](https://github.com/oroinc/platform/tree/2.6.0/src/Oro/Bundle/QueryDesignerBundle/Resources/public/js/app/views/function-choice-view.js) Backbone view, as replacement for jQuery widget `oroquerydesigner.functionChoice`.
#### SegmentBundle
* Added `SegmentChoiceView`[[?]](https://github.com/oroinc/platform/tree/2.6.0/src/Oro/Bundle/SegmentBundle/Resources/public/js/app/views/segment-choice-view.js) Backbone view, as replacement for jQuery widget `orosegment.segmentChoice`.
#### UIBundle
* Added JS `Registry`[[?]](https://github.com/oroinc/platform/tree/2.6.0/src/Oro/Bundle/UIBundle/Resources/public/js/app/services/registry/registry.js) (see [documentation](https://github.com/oroinc/platform/blob/2.6/src/Oro/Bundle/UIBundle/Resources/doc/reference/client-side/registry.md))
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
   * the element path is mostly turned off by default. It is turned on only in places where the status bar was enabled before. (edited) 

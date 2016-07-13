UPGRADE FROM 1.9 to 1.10
========================

####TestFrameworkBundle
- All tests run with debug true, hence in case of exception you will see a detailed information about it. Previously it was like on prod.
- All connections, tests internally share one pdo connection to database.
- Oro\Bundle\TestFrameworkBundle\Test\WebTestCase::cleanUpConnections method was removed.
- Oro\Bundle\TestFrameworkBundle\Test\Client::startTransaction method was removed, use one from Oro\Bundle\TestFrameworkBundle\Test\WebTestCase class.
- Oro\Bundle\TestFrameworkBundle\Test\Client::rollbackTransaction method was removed, use one from Oro\Bundle\TestFrameworkBundle\Test\WebTestCase class.
- `\Oro\Bundle\TestFrameworkBundle\Fixtures\LoadUserData` is deprecated, use `\Oro\Bundle\TestFrameworkBundle\Migrations\Data\ORM\LoadUserData` instead
- No need to load fixtures after test environment setup using `doctrine:fixture:load`

####EntityBundle
- The implementation of `Oro\Bundle\EntityBundle\ORM\EntityAliasResolver` was changed. Now the loaded entity aliases is saved into a cache that gives significant performance gain. Also, from now, you can implement `Oro\Bundle\EntityBundle\Provider\EntityClassProviderInterface` to create aliases for any entities not only for ORM entities.

####EntityConfigBundle
- Entity config class metadata now allows any `route*` options, that can be used for CRUD routes configuration - as well as already existing `routeName`, `routeView` and `routeCreate` options.
- Added `Oro\Bundle\EntityConfigBundle\Migration\RemoveManyToOneRelationQuery` as an automated way to remove ManyToOne relation

####DashboardBundle
- Class `Oro\Bundle\DashboardBundle\Provider\Converters\FilterDateTimeRangeConverter` was renamed to `Oro\Bundle\DashboardBundle\Provider\Converters\FilterDateRangeConverter`. Service was not renamed.
- Added new class `Oro\Bundle\DashboardBundle\Provider\Converters\FilterDateTimeRangeConverter`.
- `oro_type_widget_user_multiselect` form type was renamed to `oro_type_widget_user_select` and moved to UserBundle.

####DataGridBundle
- Events `Oro\Bundle\DataGridBundle\Event\OrmResultBefore` second constructor argument `$query` type changed from `Doctrine\ORM\Query` to `Doctrine\ORM\AbstractQuery`.
- Event `Oro\Bundle\DataGridBundle\Event\OrmResultAfter` third constructor argument `$query` type changed from `Doctrine\ORM\Query` to `Doctrine\ORM\AbstractQuery`.

####EntityBundle
- The constructor of the `Oro\Bundle\EntityBundle\ORM\EntityAliasResolver` class was changed. Before: `__construct(ManagerRegistry $doctrine, $debug)`. After: `__construct(DoctrineHelper $doctrineHelper, ManagerBagInterface $managerBag, $debug)`.
- The constructor of the `Oro\Bundle\EntityBundle\Provider\AllEntityHierarchyProvider` class was changed. Before: `__construct(DoctrineHelper $doctrineHelper, ConfigProvider $extendConfigProvider, EntityManagerBag $entityManagerBag)`. After: `__construct(DoctrineHelper $doctrineHelper, ConfigProvider $extendConfigProvider, ManagerBagInterface $managerBag)`.
- Method `getAllShortMetadata` was added to `Oro\Bundle\EntityBundle\ORM\DoctrineHelper`. Using of this method instead of the `getAllMetadata` method can give significant performance gain.

####ImportExportBundle
- ACL resource (capability) `oro_importexport` was removed. Please, use `oro_importexport_import` or `oro_importexport_export` instead.
- Added parameter `NewEntitiesHelper $newEntitiesHelper` to constructor of `Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy`

####SecurityBundle
- **IMPORTANT**: The behaviour of the [Access Decision Manager](http://symfony.com/doc/current/components/security/authorization.html#access-decision-manager) was changed. Now the `allowIfAllAbstainDecisions` flag is set to `true` by default. It means that an access to a resource is denied as soon as there is one voter denying access. The goal of this change is to grant access when all voters abstain.
- `Oro\Bundle\SecurityBundle\Acl\Extension\EntityMaskBuilder` - removed all constants for masks and their groups.
- `Oro\Bundle\SecurityBundle\Acl\Extension\EntityMaskBuilder` - now allow custom Permissions (see [permissions.md](./src/Oro/Bundle/SecurityBundle/Resources/doc/permissions.md)
- `Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension` - now allow custom Permissions (see [permissions.md](./src/Oro/Bundle/SecurityBundle/Resources/doc/permissions.md)
- `Oro\Bundle\SecurityBundle\Acl\Extension\MaskBuilder` - added new public methods: `hasMask(string $name)`, `getMask(string $name)`.
- `Oro\Bundle\SecurityBundle\Acl\Voter\AclVoter` - added new public method - `setPermissionManager(PermissionManager $permissionManager)`.
- Constructor for `Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension` changed. New arguments: `PermissionManager $permissionManager, AclGroupProviderInterface $groupProvider`
- Constructor for `Oro\Bundle\SecurityBundle\Acl\Extension\EntityMaskBuilder` changed. New arguments: `int $identity, array $permissions`
- Added command for loading permissions configuration `Oro\Bundle\SecurityBundle\Command\LoadPermissionConfigurationCommand` (`security:permission:configuration:load`) - this command added to install and update platform scripts.
- Added migration `Oro\Bundle\SecurityBundle\Migrations\Schema\LoadBasePermissionsQuery` for loading to DB base permissions ('VIEW', 'CREATE', 'EDIT', 'DELETE', 'ASSIGN', 'SHARE').
- Added migration `Oro\Bundle\SecurityBundle\Migrations\Schema\v1_1\UpdateAclEntriesMigrationQuery` for updating ACL Entries to use custom Permissions.
- Added `acl_permission` twig extension - allows get `Permission` by `AclPermission`.
- Added third parameter `$byCurrentGroup` to `Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionInterface::getPermissions` for getting permissions only for current application group name. Updated same method in `Oro\Bundle\SecurityBundle\Acl\Extension\ActionAclExtension` and `Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension`.
- For php version from 7.0.0 to 7.0.5 we replaced `Symfony\Component\Security\Acl\Domain\Entry` on `Oro\Bundle\SecurityBundle\Acl\Domain\Entry` to avoid [bug](https://bugs.php.net/bug.php?id=71940) with unserialization of an object reference

####WorkflowBundle
- Class `Oro\Bundle\WorkflowBundle\Exception\ActionException` marked as deprecated. Use `Oro\Component\Action\Exception\ActionException` instead.
- Class `Oro\Bundle\WorkflowBundle\Exception\AssemblerException` marked as deprecated. Use `Oro\Component\Action\Exception\AssemblerException` instead.
- Class `Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException` marked as deprecated. Use `Oro\Component\Action\Exception\InvalidParameterException` instead.
- Class `Oro\Bundle\WorkflowBundle\Model\AbstractAssembler` marked as deprecated. Use `Oro\Component\Action\Model\AbstractAssembler` instead.
- Class `Oro\Bundle\WorkflowBundle\Model\AbstractStorage` marked as deprecated. Use `Oro\Component\Action\Model\AbstractStorage` instead.
- Class `Oro\Bundle\WorkflowBundle\Model\Action\AbstractAction` marked as deprecated. Use `Oro\Component\Action\Action\AbstractAction` instead.
- Class `Oro\Bundle\WorkflowBundle\Model\Action\AbstractDateAction` marked as deprecated. Use `Oro\Component\Action\Action\AbstractDateAction` instead.
- Class `Oro\Bundle\WorkflowBundle\Model\Action\ActionAssembler` marked as deprecated. Use `Oro\Component\Action\Action\ActionAssembler` instead.
- Class `Oro\Bundle\WorkflowBundle\Model\Action\ActionFactory` marked as deprecated. Use `Oro\Component\Action\Action\ActionFactory` instead.
- Class `Oro\Bundle\WorkflowBundle\Model\Action\ActionInterface` marked as deprecated. Use `Oro\Component\Action\Action\ActionInterface` instead.
- Class `Oro\Bundle\WorkflowBundle\Model\Action\AssignActiveUser` marked as deprecated. Use `Oro\Component\Action\Action\AssignActiveUser` instead.
- Class `Oro\Bundle\WorkflowBundle\Model\Action\AssignConstantValue` marked as deprecated. Use `Oro\Component\Action\Action\AssignConstantValue` instead.
- Class `Oro\Bundle\WorkflowBundle\Model\Action\AssignValue` marked as deprecated. Use `Oro\Component\Action\Action\AssignValue` instead.
- Class `Oro\Bundle\WorkflowBundle\Model\Action\CallMethod` marked as deprecated. Use `Oro\Component\Action\Action\CallMethod` instead.
- Class `Oro\Bundle\WorkflowBundle\Model\Action\Configurable` marked as deprecated. Use `Oro\Component\Action\Action\Configurable` instead.
- Class `Oro\Bundle\WorkflowBundle\Model\Action\CopyTagging` marked as deprecated. Use `Oro\Bundle\TagBundle\Workflow\Action\CopyTagging` instead.
- Class `Oro\Bundle\WorkflowBundle\Model\Action\CreateDate` marked as deprecated. Use `Oro\Component\Action\Action\CreateDate` instead.
- Class `Oro\Bundle\WorkflowBundle\Model\Action\CreateDateTime` marked as deprecated. Use `Oro\Component\Action\Action\CreateDateTime` instead.
- Class `Oro\Bundle\WorkflowBundle\Model\Action\CreateEntity` marked as deprecated. Use `Oro\Component\Action\Action\CreateEntity` instead.
- Class `Oro\Bundle\WorkflowBundle\Model\Action\CreateObject` marked as deprecated. Use `Oro\Component\Action\Action\CreateObject` instead.
- Class `Oro\Bundle\WorkflowBundle\Model\Action\EventDispatcherAwareActionInterface` marked as deprecated. Use `Oro\Component\Action\Action\EventDispatcherAwareActionInterface` instead.
- Class `Oro\Bundle\WorkflowBundle\Model\Action\FlashMessage` marked as deprecated. Use `Oro\Component\Action\Action\FlashMessage` instead.
- Class `Oro\Bundle\WorkflowBundle\Model\Action\FormatName` marked as deprecated. Use `Oro\Bundle\ActionBundle\Action\FormatName` instead.
- Class `Oro\Bundle\WorkflowBundle\Model\Action\FormatString` marked as deprecated. Use `Oro\Component\Action\Action\FormatString` instead.
- Class `Oro\Bundle\WorkflowBundle\Model\Action\Redirect` marked as deprecated. Use `Oro\Component\Action\Action\Redirect` instead.
- Class `Oro\Bundle\WorkflowBundle\Model\Action\RemoveEntity` marked as deprecated. Use `Oro\Component\Action\Action\RemoveEntity` instead.
- Class `Oro\Bundle\WorkflowBundle\Model\Action\RequestEntity` marked as deprecated. Use `Oro\Component\Action\Action\RequestEntity` instead.
- Class `Oro\Bundle\WorkflowBundle\Model\Action\TranslateAction` marked as deprecated. Use `Oro\Component\Action\Action\TranslateAction` instead.
- Class `Oro\Bundle\WorkflowBundle\Model\Action\Traverse` marked as deprecated. Use `Oro\Component\Action\Action\Traverse` instead.
- Class `Oro\Bundle\WorkflowBundle\Model\Action\TreeExecutor` marked as deprecated. Use `Oro\Component\Action\Action\TreeExecutor` instead.
- Class `Oro\Bundle\WorkflowBundle\Model\Action\UnsetValue` marked as deprecated. Use `Oro\Component\Action\Action\UnsetValue` instead.
- Class `Oro\Bundle\WorkflowBundle\Model\Attribute` marked as deprecated. Use `Oro\Component\Action\Model\Attribute` instead.
- Class `Oro\Bundle\WorkflowBundle\Model\AttributeGuesser` marked as deprecated. Use `Oro\Component\Action\Model\AttributeGuesser` instead.
- Class `Oro\Bundle\WorkflowBundle\Model\AttributeManager` marked as deprecated. Use `Oro\Component\Action\Model\AttributeManager` instead.
- Class `Oro\Bundle\WorkflowBundle\Model\Condition\AbstractCondition` marked as deprecated. Use `Oro\Component\Action\Condition\AbstractCondition` instead.
- Class `Oro\Bundle\WorkflowBundle\Model\Condition\Configurable` marked as deprecated. Use `Oro\Component\Action\Condition\Configurable` instead.
- Class `Oro\Bundle\WorkflowBundle\Model\ConfigurationPass\ReplacePropertyPath` marked as deprecated. Use `Oro\Bundle\ActionBundle\Model\ConfigurationPass\ReplacePropertyPath` instead.
- Class `Oro\Bundle\WorkflowBundle\Model\ContextAccessor` marked as deprecated. Use `Oro\Component\Action\Model\ContextAccessor` instead.
- Class `Oro\Bundle\WorkflowBundle\Model\Event\ExecuteActionEvent` marked as deprecated. Use `Oro\Component\Action\Event\ExecuteActionEvent` instead.
- Class `Oro\Bundle\WorkflowBundle\Model\Event\ExecuteActionEvents` marked as deprecated. Use `Oro\Component\Action\Event\ExecuteActionEvents` instead.
- Constant `Oro\Bundle\WorkflowBundle\Event\ExecuteActionEvents::HANDLE_BEFORE` is deprecated. Use `Oro\Component\Action\Event\ExecuteActionEvents::HANDLE_BEFORE` instead.
- Constant `Oro\Bundle\WorkflowBundle\Event\ExecuteActionEvents::HANDLE_AFTER` is deprecated. Use `Oro\Component\Action\Event\ExecuteActionEvents::HANDLE_AFTER` instead.
- Service `oro_workflow.action_assembler` is deprecated. Use `oro_action.action_assembler` instead.
- Service `oro_workflow.attribute_guesser` is deprecated. Use `oro_action.attribute_guesser` instead.
- Service `oro_workflow.context_accessor` is deprecated. Use `oro_action.context_accessor` instead.
- Service `oro_workflow.action_factory` is deprecated. Use `oro_action.action_factory` instead.
- Service `oro_workflow.configuration_pass.replace_property_path` is deprecated. Use `oro_action.configuration_pass.replace_property_path` instead.
- The constructor of the `Oro\Bundle\WorkflowBundle\Handler\TransitionHandler` class was changed. Third argument `LoggerInterface` (@logger service) was added.
- Added error logging in `Oro\Bundle\WorkflowBundle\Handler\TransitionHandler` at handle method.
- Added parameter `RestrictionAssembler $restrictionAssembler` to constructor of `Oro\Bundle\WorkflowBundle\Model\WorkflowAssembler`
- Added parameter `RestrictionManager $restrictionManager` to constructor of `Oro\Bundle\WorkflowBundle\Model\Workflow`
- Added parameter `WorkflowPermissionRegistry $permissionRegistry` to constructor of `Oro\Bundle\WorkflowBundle\Acl\Voter\WorkflowEntityVoter`
- Added tags: `oro_workflow.changes.listener` and `oro_workflow.changes.subscriber` for `\Oro\Bundle\WorkflowBundle\Event\WorkflowEvents` event constants and separate dispatcher service `oro_workflow.changes.event.dispatcher`.
- Added class `Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurator` with corresponded service `oro_workflow.process.configurator` for single point of workflow processes configurations by configuration sets.
- Added class `Oro\Bundle\WorkflowBundle\Configuration\ProcessTriggersConfigurator`
    **IMPORTANT**: Configuration must provide full list of triggers for mentioned process definition. If list under definition key comes empty - triggers would be removed. See more in `\Oro\Bundle\WorkflowBundle\Configuration\ProcessTriggersConfigurator::configureTriggers` doc-block.
    **IMPORTANT**: Changing of process cron triggers configuration will not keep all old cron triggers in database. E.g. old triggers would be removed and new created.
- Added class `Oro\Bundle\WorkflowBundle\Handler\WorkflowDefinitionHandler` (`oro_workflow.handler.workflow_definition` service) for single point of `WorkflowDefinition` entity management.
    All manipulations with `Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition` entity persistence should be provided through the handler.
- Class `Oro\Bundle\WorkflowBundle\Model\WorkflowManager` construction signature was changed: additional (fifth) argument `Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher` was added.

####CronBundle
- Added action `@create_job` for instance of `JMS\JobQueueBundle\Entity\Job` creation and persistence through actions (Class `Oro\Bundle\CronBundle\Action\CreateJobAction`).

####SearchBundle
- `Oro\Bundle\SearchBundle\DependencyInjection\OroSearchExtension::setEntitiesConfigParameter` deprecated since 1.9. Will be removed after 1.11. Please use oro_search.provider.search_mapping service for mapping config instead.
- `Oro\Bundle\SearchBundle\DependencyInjection\OroSearchExtension::mergeConfig` deprecated since 1.9. Will be removed after 1.11.
- `Oro\Bundle\SearchBundle\EventListener\UpdateSchemaDoctrineListener` is no longer requires `Oro\Bundle\SearchBundle\Engine\FulltextIndexManager` as an first argument

####FormBundle
- 'Oro\Bundle\FormBundle\Form\Extension\RandomIdExtension' was renamed to 'Oro\Bundle\FormBundle\Form\Extension\AdditionalAttrExtension'
- 'oro_form.extension.random_id' service was renamed to 'oro_form.extension.additional_attr'
- Form field identifier - 'data-name' attribute generation added to 'AdditionalAttrExtension'
- Method `Oro\Bundle\FormBundle\Model\UpdateHandler::handleUpdate` marked as deprecated. Use `Oro\Bundle\FormBundle\Model\UpdateHandler::update` instead.
- In previous version client of methods `Oro\Bundle\UIBundle\Route\Router::redirectAfterSave` and `Oro\Bundle\FormBundle\Model\UpdateHandler::handleUpdate`
was responsible to pass redirect data in arguments, for example:
```
    return $this->get('oro_ui.router')->redirectAfterSave(
        ['route' => 'oro_calendar_event_update', 'parameters' => ['id' => $entity->getId()]],
        ['route' => 'oro_calendar_event_view', 'parameters' => ['id' => $entity->getId()]]
    );
```
```
    return $this->get('oro_form.model.update_handler')->handleUpdate(
        $trackingWebsite,
        $this->createForm($this->getFormType(), $trackingWebsite),
        function (TrackingWebsite $entity) {
            return [
                'route' => 'oro_tracking_website_update',
                'parameters' => ['id' => $entity->getId()]
            ];
        },
        function (TrackingWebsite $entity) {
            return [
                'route' => 'oro_tracking_website_view',
                'parameters' => ['id' => $entity->getId()]
            ];
        },
        $this->getTranslator()->trans('oro.tracking.trackingwebsite.saved_message')
    );
```
These routes were used to make redirect when user clicked on one of 2 buttons on UI rendered using 2 Twig macroses: saveAndCloseButton and saveAndStayButton.
Now controller's action is not responsible to configure redirect data at all.
```
    return $this->get('oro_ui.router')->redirect($entity);
```
```
    return $this->get('oro_form.model.update_handler')->update(
        $trackingWebsite,
        $this->createForm($this->getFormType(), $trackingWebsite),
        $this->getTranslator()->trans('oro.tracking.trackingwebsite.saved_message')
    );
```
Redirect data is now configured directly in the button rendered in Twig template using macroses: saveAndCloseButton, saveAndStayButton, saveAndNewButton and saveActionButton.
See other related information in section `OroUIBundle` of this document.

####TranslationBundle
- Added translation strategies to dynamically handle translation fallbacks
- Refactored `Oro/Bundle/TranslationBundle/Translation/Translator` to support translation strategies

####DataGridBundle
- Moved and renamed class `Oro\Bundle\DataGridBundle\Common\Object` to `Oro\Component\Config\Common\ConfigObject`
- Changed priority in next extensions:
    * Oro\Bundle\DataGridBundle\Extension\Sorter\OrmSorterExtension from -250 to -260
    * Oro\Bundle\DataGridBundle\Extension\Sorter\PostgresqlGridModifier from -251 to -261

####ConfigExpression
- The class Oro\Component\ConfigExpression\Condition\False was renamed to FalseCondition
- The class Oro\Component\ConfigExpression\Condition\True was renamed to TrueCondition

####UIBundle
- Added [lightgallery](http://sachinchoolur.github.io/lightGallery/) plugin by Sachin N.

Gallery view for a group of `<a>` elements can be triggered by adding 'data-gallery' attribute with unique gallery id.

```
<a href="sample1.jpg" data-gallery="unique-id"></a>
<a href="sample2.jpg" data-gallery="unique-id"></a>
```

- Method `Oro\Bundle\UIBundle\Route\Router::redirectAfterSave` marked as deprecated. Use method `Oro\Bundle\UIBundle\Route\Router::redirect` instead.
- Use of Twig macroses `saveAndCloseButton` and `saveAndStayButton` was changed. In old version client was responsible to pass just a label of button.
In this new version client responsible to specify route and parameters which will be used to make redirect if form will successfully saved.
Before:
```
    {% set html = UI.saveAndCloseButton() %}
    {% if form.vars.value.id or resource_granted('oro_user_user_update') %}
        {% set html = html ~ UI.saveAndStayButton() %}
    {% endif %}
    {{ UI.dropdownSaveButton({'html': html}) }}
{% endblock navButtons %}
```
Now:
```
    {% if resource_granted('oro_user_create') %}
        {% set html = html ~ UI.saveAndNewButton({
            'route': 'oro_user_create'
        }) %}
    {% endif %}
    {% if form.vars.value.id or resource_granted('oro_user_user_update') %}
        {% set html = html ~ UI.saveAndStayButton({
            'route': 'oro_user_update',
            'params': {'id': '$id'}
        }) %}
    {% endif %}
    {{ UI.dropdownSaveButton({'html': html}) }}
{% endblock navButtons %}
```
- New macros `saveAndNewButton` was added and used on most of pages with forms. By clicking on this button user redirects to page with form where a new record could be created.


####EmailBundle
- Constructor for `Oro\Bundle\EmailBundle\Manager\EmailAttachmentManager` was changed. New arguments: `Router $router`
- Constructor for `Oro\Bundle\EmailBundle\Tools\EmailAttachmentTransformer` was changed. New arguments: `AttachmentManager $manager, EmailAttachmentManager $emailAttachmentManager`
- The constructor of the `Oro\Bundle\EmailBundle\Mailer\Processor` class was changed. Before: `__construct(DoctrineHelper $doctrineHelper, DirectMailer $mailer, EmailAddressHelper $emailAddressHelper, EmailEntityBuilder $emailEntityBuilder, EmailOwnerProvider $emailOwnerProvider, EmailActivityManager $emailActivityManager, ServiceLink $serviceLink, EventDispatcherInterface $eventDispatcher, Mcrypt $encryptor, EmailOriginHelper $emailOriginHelper)`. After: `__construct(DoctrineHelper $doctrineHelper, DirectMailer $mailer, EmailAddressHelper $emailAddressHelper, EmailEntityBuilder $emailEntityBuilder, EmailActivityManager $emailActivityManager, EventDispatcherInterface $eventDispatcher, Mcrypt $encryptor, EmailOriginHelper $emailOriginHelper)`.
- `Oro\Bundle\EmailBundle\Mailer\Processor::getEmailOrigin` marked as deprecated. Use method `Oro\Bundle\EmailBundle\Tools\EmailOriginHelper::getEmailOrigin` instead.
- Additional you should use origin as second parameter for `Oro\Bundle\EmailBundle\Mailer\Processor::process` if you want use specific transport different from system.
- The constructor of the `Oro\Bundle\EmailBundle\Manager\EmailNotificationManager` class was changed.
    Before: `__construct(EntityManager $entityManager, HtmlTagHelper $htmlTagHelper, Router $router, EmailCacheManager $emailCacheManager, ConfigManager $configManager)`.
    After: `__construct(EntityManager $entityManager, HtmlTagHelper $htmlTagHelper, Router $router, ConfigManager $configManager)`.

####PlatformBundle
- The method `prepend()` of `Oro\Bundle\PlatformBundle\DependencyInjection\OroPlatformExtension` class was changed. The main aim is to change ordering of configuration load from `Resources\config\oro\app.yml` files. At now the bundles that are loaded later can override configuration of bundles loaded before.

####AttachmentBundle:
- Fixed `Oro\Bundle\AttachmentBundle\Guesser\MsMimeTypeGuesser` to avoid PHP notices on file upload in some cases
- Added translation strategies to dynamically handle translation fallbacks

####CalendarBundle
- The method `formatCalendarDateRange` of `src/Oro/src/Oro/Bundle/CalendarBundle/Twig/DateFormatExtension.php` class was changed. Argument $dateTimeFormat was deleted, because it has no sense. `calendar_date_range` extension method in twig templates should be called without this param.
- Added method `formatCalendarDateRangeUser` of `src/Oro/src/Oro/Bundle/CalendarBundle/Twig/DateFormatUserExtension.php`. Method `calendar_date_range_user` get additional param 'user' and return sate range according to user organization localization settings.

####LayoutBundle:
- Added possibility to create layout block types using only DI configuration, for details please check out documentation at
 [Creating new block types](./src/Oro/Bundle/LayoutBundle/Resources/doc/example.md) section.
- BlockType classes replaced with DI configuration for listed block types: `root`, `head`, `body`, `fieldset`, `list`, `listitem`, `text`, `button` and `button_group`.
Corresponding block type classes was removed.
- Renamed `setDefaultOptions` to `configureOptions` method at `Oro\Component\Layout\BlockTypeInterface\BlockTypeInterface` and `Oro\Component\Layout\BlockTypeInterface\BlockTypeExtensionInterface`.
- Defined image types configuration section for theme config
- Added `oro_layout.provider.image_type` service to get image types available across all themes
- The class Oro\Bundle\LayoutBundle\Layout\Block\Type\FormType was renamed to FormFieldsType
- New `Oro\Bundle\LayoutBundle\Layout\Block\Type\FormType` block type was added. This block type creates three child blocks in buildBlock method: `FormStartType`, `FormType`, `FormEndType`.

####EmbeddedFormBundle:
- Layout block types was replaced with DI only configuration for `embed_form_success` and `embed_form_legacy_form` block types.
Classes `Oro/Bundle/EmbeddedFormBundle/Layout/Block/Type/EmbedFormSuccessType` and
`Oro/Bundle/EmbeddedFormBundle/Layout/Block/Type/EmbedFormType` was removed.

####ActionBundle:
- Layout block types was replaced with DI only configuration for `abstract_configurable` block,
class `Oro/Bundle/ActionBundle/Layout/Block/Type/ActionCombinedButtonsType` was removed.

#### LocaleBundle:
- Added entity `Oro\Bundle\LocaleBundle\Entity\Localization` ([`see documentation`](./src/Oro/Bundle/LocaleBundle/Resources/doc/reference/entities.md#localization)).
- Added entity `Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue` ([`see documentation`](./src/Oro/Bundle/LocaleBundle/Resources/doc/reference/entities.md#localizedfallbackvalue)) for translating strings in different localizations.
- Added trait `Oro\Bundle\LocaleBundle\Entity\FallbackTrait` for retrieve translated value for the needed localization.
- Added Formatter `Oro\Bundle\LocaleBundle\Formatter\LanguageCodeFormatter`  for displaying full title of the language by code.
- Added Formatter `Oro\Bundle\LocaleBundle\Formatter\FormattingCodeFormatter`  for displaying full title of the formatting by code.
- Added `oro_format_datetime_user` twig extension - allows get formatted date and calendar date range by user organization localization settings. Deprecated since 1.11. Will be removed after 1.13.

####Layout Component:
- `\Oro\Component\Layout\Loader\Generator\ConfigLayoutUpdateGeneratorExtensionInterface::prepare()` signature was changed from `prepare(array $source, VisitorCollection $visitorCollection);` to `prepare(Oro\Component\Layout\Loader\Generator\GeneratorData $data, VisitorCollection $visitorCollection);`
- `@addTree` layout update action is `\Oro\Bundle\LayoutBundle\Layout\Extension\Generator\AddTreeGeneratorExtension` now
- Layout update `@setFormTheme` and `@setBlockTheme` actions can accept relative paths now

####NotificationBundle:
- The constructor of the `Oro\Bundle\NotificationBundle\Provider\Mailer\DbSpool` class was changed. Before: `__construct(EntityManager $em, EntityPool $entityPool, $entityClass)`. After: `__construct(EntityManager $em, EntityPool $entityPool, $entityClass, EventDispatcherInterface $eventDispatcher)`.

####Oro\Component\Config
- `Oro\Component\Config\CumulativeResourceInfo::__construct` signature changed from
`public function __construct($bundleClass, $name, $path, $data)` to `public function __construct($bundleClass, $name, $path, array $data = [])`

####UserBundle:
- The constructor of the `Oro\Bundle\UserBundle\Form\Type\UserType` class was changed. Removed argument: `ConfigManager $userConfigManager`.
- The constructor of the `Oro\Bundle\UserBundle\Form\Handler\UserHandler` class was changed. Removed argument: `SecurityFacade $securityFacade = null`.
- Added new configuration group user_configuration used to define which personal configurations can be updated by the user (Languages, Locale etc.)
- Added 2 new capabilites: Update User Profile and Access personal configuration to control access to "My Profile" and  "My Configuration" updates
- Added new permission "Configure" which controls access to entity configuration settings. Currently is applied to User entity only.

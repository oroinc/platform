UPGRADE FROM 1.7 to 1.8
=======================

####PropertyAccess Component
- Removed `Oro\Component\PropertyAccess\PropertyPath` and `Oro\Component\PropertyAccess\PropertyPathInterface`, `Symfony\Component\PropertyAccess\PropertyPath` and `Symfony\Component\PropertyAccess\PropertyPathInterface` should be used instead
- Removed `Oro\Component\PropertyAccess\Exception` namespace, `Symfony\Component\PropertyAccess\Exception` is used

####EmailBundle
- The format of object returned by GET /api/rest/{version}/emails and GET /api/rest/{version}/emails resources was changed. Not a email body is returned as "body" and "bodyType" properties rather than "emailBody" object. Possible values for "bodyType" are "text" and "html". Possible values for the "importance" property are "low", "normal" and "high" rather than -1, 0 and 1. The "recipients" property was removed and three new properties were added instead: "to", "cc" and "bcc". The format of "folders" collection was changed as well, now each folder can have the following properties: "origin", "fullName", "name" and "type". Possible values for "type" property are "inbox", "sent", "trash", "drafts", "spam" and "other".   

####EntityBundle
- Entity aliases are introduced. You can use `php app/console oro:entity-alias:debug` CLI command to see all aliases. In most cases aliases are generated automatically, but you can use `entity_aliases` and `entity_alias_exclusions` section in the `Resources/config/oro/entity.yml` of your bundle to define your rules.
- Methods `encodeClassName` and `decodeClassName` of `Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper` are deprecated. Use `getUrlSafeClassName` and `resolveEntityClass` instead. Also `Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper` can be used for same purposes.
- The entity name resolver service was introduced to allow configuring an entity name formatting more flexible. Now `Oro\Bundle\EntityBundle\Provider\EntityNameResolver` is used instead of `Oro\Bundle\LocaleBundle\Formatter\NameFormatter` and `Oro\Bundle\LocaleBundle\DQL\DQLNameFormatter`. The list of affected services:

| Service ID | Class Name |
|------------|------------|
| oro_activity_list.manager | Oro\Bundle\ActivityListBundle\Entity\Manager\ActivityListManager |
| oro_calendar.calendar_event_manager | Oro\Bundle\CalendarBundle\Manager\CalendarEventManager |
| oro_calendar.calendar_provider.user | Oro\Bundle\CalendarBundle\Provider\UserCalendarProvider |
| oro_calendar.autocomplete.user_calendar_handler | Oro\Bundle\CalendarBundle\Autocomplete\UserCalendarHandler |
| oro_comment.comment.api_manager | Oro\Bundle\CommentBundle\Entity\Manager\CommentApiManager |
| oro_email.email.model.builder.helper | Oro\Bundle\EmailBundle\Builder\Helper\EmailModelBuilderHelper |
| oro_email.emailtemplate.variable_provider.user | Oro\Bundle\EmailBundle\Provider\LoggedUserVariablesProvider |
| oro_email.datagrid_query_factory | Oro\Bundle\EmailBundle\Datagrid\EmailQueryFactory |
| oro_email.workflow.action.send_email | Oro\Bundle\EmailBundle\Workflow\Action\SendEmail |
| oro_email.workflow.action.send_email_template | Oro\Bundle\EmailBundle\Workflow\Action\SendEmailTemplate |
| oro_email.activity_list.provider | Oro\Bundle\EmailBundle\Provider\EmailActivityListProvider |
| oro_entity_merge.listener.render.localized_value_render | Oro\Bundle\EntityMergeBundle\EventListener\Render\LocalizedValueRenderListener |
| oro_form.autocomplete.full_name.search_handler | Oro\Bundle\FormBundle\Autocomplete\FullNameSearchHandler |
| oro_note.manager | Oro\Bundle\NoteBundle\Entity\Manager\NoteManager |
| oro_reminder.model.email_notification | Oro\Bundle\ReminderBundle\Model\Email\EmailNotification |
| oro_user.autocomplete.user.search_acl_handler.abstract | Oro\Bundle\UserBundle\Autocomplete\UserAclHandler |
| oro_workflow.action.format_name | Oro\Bundle\WorkflowBundle\Model\Action\FormatName |
| orocrm_account.form.type.account | OroCRM\Bundle\AccountBundle\Form\Type\AccountType |
| orocrm_account.form.type.account.api | OroCRM\Bundle\AccountBundle\Form\Type\AccountApiType |
| orocrm_case.view_factory | OroCRM\Bundle\CaseBundle\Model\ViewFactory |

####EntityConfigBundle
- The DI container tag `oro_service_method` and the class `Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceMethod` are deprecated and will be removed soon.
- IMPORTANT: if you use the service method links in your `entity_config.yml` they should be replaced with the direct service method call. For example `my_service_method_link` should be replaced with `@my_service->method`.
- Removed the method `initConfig` of the class `Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer`.
- `Oro\Bundle\ConfigBundle\Config\UserScopeManager` is no longer depends on `security.context`. It is retreived from `service.container` directly inside

####ImportExportBundle
 - `Oro\Bundle\ImportExportBundle\Context\ContextInterface` added $incrementBy integer parameter for methods: incrementReadCount, incrementAddCount, incrementUpdateCount, incrementReplaceCount, incrementDeleteCount, incrementErrorEntriesCount

####WorkflowBundle
 Migrate conditions logic to ConfigExpression component:
 - Removed `Oro\Bundle\WorkflowBundle\Model\Condition\ConditionInterface`, `Oro\Component\ConfigExpression\ExpressionInterface` should be used instead
 - Removed `Oro\Bundle\WorkflowBundle\Model\Condition\ConditionFactory`, `Oro\Component\ConfigExpression\ExpressionFactory` should be used instead
 - Removed `Oro\Bundle\WorkflowBundle\Model\Condition\ConditionAssembler`, `Oro\Component\ConfigExpression\ExpressionAssembler` should be used instead
 - Removed all conditions in `Oro\Bundle\WorkflowBundle\Model\Condition` namespace, corresponding conditions from ConfigExpression component (`Oro\Component\ConfigExpression\Condition` namespace) should be used instead

####FormBundle
 - `Oro\Bundle\FormBundle\Form\Extension\RandomIdExtension` by default adds unique suffix to id attribute of each form type
 - `Oro\Bundle\FormBundle\Model\UpdateHandler` triggers events that can be used to modify data and interrupt processing, also this handler has new constructor argument used to inject EventDispatcher
 - `Oro\Bundle\FormBundle\Form\Type\OroJquerySelect2HiddenType` removed support for `extra_config` and `extra_modules` options, use `component` option instead (the value reflects what js-module will be used as Select2Component)
 - `Oro\Bundle\FormBundle\Form\Type\EnumFilterType` second constructor argument was changed from instance of `Doctrine\Common\Persistence\ManagerRegistry` to `Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider` 

####SyncBundle
Removed parameters `websocket_host` and `websocket_port` from `parameters.yml`. Instead the following websocket configuration is used:
``` yaml
    websocket_bind_address:  0.0.0.0
    websocket_bind_port:     8080
    websocket_frontend_host: "*"
    websocket_frontend_port: 8080
    websocket_backend_host:  "*"
    websocket_backend_port:  8080
```
- `websocket_bind_port` and `websocket_bind_address` specify port and address to which the Clank server binds on startup and waits for incoming requests. By default (0.0.0.0), it listens to all addresses on the machine
- `websocket_backend_port` and `websocket_backend_host` specify port and address to which the application should connect (PHP). By default ("*"), it connects to 127.0.0.1 address.
- `websocket_frontend_port` and `websocket_frontend_host` specify port and address to which the browser should connect (JS). By default ("*"), it connects to host specified in the browser.

####SoapBundle
- Removed `EntitySerializerManagerInterface`. The serialization methods in `ApiEntityManager` class should be used instead.

####UiBundle
 - Macros `scrollData` in `Oro/Bundle/UIBundle/Resources/views/macros.html.twig` triggers event `oro_ui.scroll_data.before.<pageIdentifier>` before data rendering

####LocaleBundle
- Deprecated method {{localeSettings.getTimeZoneShift()}} (calendar-view.js, formatter/datetime.js, datepicker/datetimepicker-view-mixin.js)
- Deprecated method {{dateTimeFormatter.applyTimeZoneCorrection()}} (calendar-view.js, jquery-ui-datepicker-l10n.js)
- Deprecated method {{calendarView.options.timezone}} and {{calendarView.applyTzCorrection()}}
- Deprecated method {{datetimepickerViewMixin.timezoneShift}}

####UserBundle
- `Oro\Bundle\UserBundle\Security\AdvancedApiUserInterface` is no longer extends `Symfony\Component\Security\Core\User\UserInterface`. 
- `Oro\Bundle\UserBundle\Entity\User` is based on `Oro\Bundle\UserBundle\Entity\AbstractUser` and implements `Symfony\Component\Security\Core\User\UserInterface` using `Oro\Bundle\UserBundle\Entity\UserInterface` directly
- `Oro\Bundle\UserBundle\Entity\PasswordRecoveryInterface` introduced to cover all required data for password recovery
- `Oro\Bundle\UserBundle\Entity\UserInterface` method `public function addRole(RoleInterface $role)` signature changed to use `Symfony\Component\Security\Core\Role\RoleInterface`
- `Oro\Bundle\UserBundle\Mailer\Processor` is now based on `Oro\Bundle\UserBundle\Mailer\BaseProcessor`
- `Oro\Bundle\UserBundle\Mailer\Processor` - first argument `$user` of `sendChangePasswordEmail`, `sendResetPasswordEmail` and `sendResetPasswordAsAdminEmail` methods must implement `Oro\Bundle\UserBundle\Entity\UserInterface`
- First argument `Doctrine\Common\Persistence\ObjectManager $objectManager` and fourth argument `Oro\Bundle\UserBundle\Entity\UserManager $userManager` of `Oro\Bundle\UserBundle\Mailer\Processor` constructor (which now is located in `Oro\Bundle\UserBundle\Mailer\BaseProcessor`) replaced by `Doctrine\Common\Persistence\ManagerRegistry $managerRegistry` and `Oro\Bundle\EmailBundle\Tools\EmailHolderHelper $emailHolderHelper` accordingly
- `Oro\Bundle\UserBundle\Form\Handler\AclRoleHandler` is now accepts Manager Registry instead of Entity Manager, added method `setManagerRegistry`, method `setEntityManager` marked as deprecated 

####SecurityBundle
- `Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface` was introduced and based on access levels, considered to use in security layer instead of direct `Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata` usage
- `Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata`
    * `isOrganizationOwned` deprecated, use `isGlobalLevelOwned` instead
    * `isBusinessUnitOwned` deprecated, use `isLocalLevelOwned` instead
    * `isUserOwned` deprecated, use `isBasicLevelOwned` instead
    * `getOrganizationColumnName` deprecated, use `getGlobalOwnerColumnName` instead
    * `getOrganizationFieldName` deprecated, use `getGlobalOwnerFieldName` instead
- `Oro\Bundle\SecurityBundle\ORM\Walker\OwnershipConditionDataBuilder` method signature changed to use `Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface` instead of `Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata`
    * `protected function buildConstraintIfAccessIsGranted($targetEntityClassName, $accessLevel, OwnershipMetadataInterface $metadata)`
    * `protected function getOrganizationId(OwnershipMetadataInterface $metadata = null)` 
    * `protected function getCondition($idOrIds, OwnershipMetadataInterface $metadata, $columnName = null, $ignoreOwner = false)`
    * `protected function getColumnName(OwnershipMetadataInterface $metadata, $columnName = null)`
- `Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface` was introduced and based on access levels, considered to use in security layer instead of direct `Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider` usage
- `Oro\Bundle\SecurityBundle\Owner\Metadata\ChainMetadataProvider` - chain for ownership metadata providers which implements new `Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface`
- `Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider`
    * is based on `Oro\Bundle\SecurityBundle\Owner\Metadata\AbstractMetadataProvider` and implements `Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface`
    * added public method `setSecurityFacade(SecurityFacade $securityFacade)`
    * `getOrganizationClass` deprecated, use `getGlobalLevelClass` instead
    * `getBusinessUnitClass` deprecated, use `getLocalLevelClass` instead
    * `getUserClass` deprecated, use `getBasicLevelClass` instead
- `Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider` added into `Oro\Bundle\SecurityBundle\Owner\Metadata\ChainMetadataProvider` chain using tag `oro_security.owner.metadata_provider`
- `Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension`
    * fourth constructor argument `$metadataProvider` now must implement `Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface`
    * `fixMaxAccessLevel` deprecated, use `MetadataProviderInterface::getMaxAccessLevel` instead
- Class methods and constructors deprecated, please inject `@service_container` and appropriate methods instead
    * `Oro\Bundle\SecurityBundle\EventListener\ConsoleContextListener` defined using `oro_security.listener.console_context_listener` service
    * `Oro\Bundle\SecurityBundle\Owner\EntityOwnershipDecisionMaker` defined using `oro_security.owner.decision_maker` service
    * `Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider` defined using `oro_security.owner.ownership_metadata_provider` service
- ACL parameter `group_name` is now used to separate permissions in application scopes 
- Constructor was changed from implementation `public function __construct(OwnershipMetadataProvider $provider)` to interface `public function __construct(MetadataProviderInterface $provider)`
    * `Oro\Bundle\SecurityBundle\Cache\OwnershipMetadataCacheClearer` 
    * `Oro\Bundle\SecurityBundle\Cache\OwnershipMetadataCacheWarmer`
    * `Oro\Bundle\SecurityBundle\EventListener\OwnershipConfigSubscriber`
    * `Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor`
    * `Oro\Bundle\SecurityBundle\ORM\Walker\OwnershipConditionDataBuilder`
- Services rely on `oro_security.owner.metadata_provider.chain` instead of implementation `oro_security.owner.ownership_metadata_provider`
    * `oro_security.owner.ownership_metadata_provider.cache.warmer`
    * `oro_security.owner.ownership_metadata_provider.cache.clearer`
    * `oro_security.owner.ownership_config_subscriber`
    * `oro_security.owner.entity_owner_accessor`
    * `oro_security.orm.ownership_sql_walker_builder`
- Constructor was changed from implementation `public function __construct(OwnerTreeProvider $treeProvider)` to interface `public function __construct(OwnerTreeProviderInterface $treeProvider)`
    * `Oro\Bundle\SecurityBundle\Cache\OwnerTreeCacheWarmer`
    * `Oro\Bundle\SecurityBundle\Cache\OwnerTreeCacheCleaner`
    * `Oro\Bundle\SecurityBundle\ORM\Walker\OwnershipConditionDataBuilder`
- Services rely on `oro_security.ownership_tree_provider.chain` instead of implementation `oro_security.ownership_tree_provider`
    * `oro_security.ownership_tree.cache.cleaner`
    * `oro_security.ownership_tree.cache.warmer`
    * `oro_security.orm.ownership_sql_walker_builder`

####AddressBundle
- `Oro\Bundle\AddressBundle\Form\EventListener\FixAddressesPrimaryAndTypesSubscriber` marked deprecated. Use `Oro\Bundle\AddressBundle\Form\EventListener\FixAddressesPrimarySubscriber` and `Oro\Bundle\AddressBundle\Form\EventListener\FixAddressesTypesSubscriber` instead.

####DataGridBundle
- `Oro\Bundle\DataGridBundle\Datasource\ResultRecord` now has method `addData` that allows to add additional information to record

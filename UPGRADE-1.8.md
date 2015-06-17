UPGRADE FROM 1.7 to 1.8
=======================

####PropertyAccess Component
- Removed `Oro\Component\PropertyAccess\PropertyPath` and `Oro\Component\PropertyAccess\PropertyPathInterface`, `Symfony\Component\PropertyAccess\PropertyPath` and `Symfony\Component\PropertyAccess\PropertyPathInterface` should be used instead
- Removed `Oro\Component\PropertyAccess\Exception` namespace, `Symfony\Component\PropertyAccess\Exception` is used

####EmailBundle
- The format of object returned by GET /api/rest/{version}/emails and GET /api/rest/{version}/emails resources was changed. Not a email body is returned as "body" and "bodyType" properties rather than "emailBody" object. Possible values for "bodyType" are "text" and "html". Possible values for the "importance" property are "low", "normal" and "high" rather than -1, 0 and 1. The "recipients" property was removed and three new properties were added instead: "to", "cc" and "bcc". The format of "folders" collection was changed as well, now each folder can have the following properties: "origin", "fullName", "name" and "type". Possible values for "type" property are "inbox", "sent", "trash", "drafts", "spam" and "other".   

####EntityConfigBundle
- The DI container tag `oro_service_method` and the class `Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceMethod` are deprecated and will be removed soon.
- IMPORTANT: if you use the service method links in your `entity_config.yml` they should be replaced with the direct service method call. For example `my_service_method_link` should be replaced with `@my_service->method`.
- Removed the method `initConfig` of the class `Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer`.

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
- `Oro\Bundle\UserBundle\Mailer\Processor` now is based on `Oro\Bundle\UserBundle\Mailer\BaseProcessor`
- `Oro\Bundle\UserBundle\Mailer\Processor` - first argument `$user` of `sendChangePasswordEmail`, `sendResetPasswordEmail` and `sendResetPasswordAsAdminEmail` methods must implement `Oro\Bundle\UserBundle\Entity\UserInterface`
- First argument `Doctrine\Common\Persistence\ObjectManager $objectManager` and fourth argument `Oro\Bundle\UserBundle\Entity\UserManager $userManager` of `Oro\Bundle\UserBundle\Mailer\Processor` constructor (which now is located in `Oro\Bundle\UserBundle\Mailer\BaseProcessor`) replaced by `Doctrine\Common\Persistence\ManagerRegistry $managerRegistry` and `Oro\Bundle\EmailBundle\Tools\EmailHolderHelper $emailHolderHelper` accordingly

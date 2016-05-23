UPGRADE FROM 1.9 to 1.10
========================

####EntityBundle:
- The implementation of `Oro\Bundle\EntityBundle\ORM\EntityAliasResolver` was changed. Now the loaded entity aliases is saved into a cache that gives significant performance gain. Also, from now, you can implement `Oro\Bundle\EntityBundle\Provider\EntityClassProviderInterface` to create aliases for any entities not only for ORM entities.

####EntityConfigBundle:
- Entity config class metadata now allows any `route*` options, that can be used for CRUD routes configuration - as well as already existing `routeName`, `routeView` and `routeCreate` options.

####DashboardBundle:
- Class `Oro\Bundle\DashboardBundle\Provider\Converters\FilterDateTimeRangeConverter` was renamed to `Oro\Bundle\DashboardBundle\Provider\Converters\FilterDateRangeConverter`. Service was not renamed.
- Added new class `Oro\Bundle\DashboardBundle\Provider\Converters\FilterDateTimeRangeConverter`.

####DataGridBundle:
- Events `Oro\Bundle\DataGridBundle\Event\OrmResultBefore` second constructor argument `$query` type changed from `Doctrine\ORM\Query` to `Doctrine\ORM\AbstractQuery`.
- Event `Oro\Bundle\DataGridBundle\Event\OrmResultAfter` third constructor argument `$query` type changed from `Doctrine\ORM\Query` to `Doctrine\ORM\AbstractQuery`.

####EntityBundle:
- The constructor of the `Oro\Bundle\EntityBundle\ORM\EntityAliasResolver` class was changed. Before: `__construct(ManagerRegistry $doctrine, $debug)`. After: `__construct(DoctrineHelper $doctrineHelper, ManagerBagInterface $managerBag, $debug)`.
- The constructor of the `Oro\Bundle\EntityBundle\Provider\AllEntityHierarchyProvider` class was changed. Before: `__construct(DoctrineHelper $doctrineHelper, ConfigProvider $extendConfigProvider, EntityManagerBag $entityManagerBag)`. After: `__construct(DoctrineHelper $doctrineHelper, ConfigProvider $extendConfigProvider, ManagerBagInterface $managerBag)`.
- Method `getAllShortMetadata` was added to `Oro\Bundle\EntityBundle\ORM\DoctrineHelper`. Using of this method instead of the `getAllMetadata` method can give significant performance gain.

####SecurityBundle
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
- `Oro\Bundle\WorkflowBundle\Event\ExecuteActionEvents::HANDLE_BEFORE` is deprecated. Use `Oro\Component\Action\Event\ExecuteActionEvents::HANDLE_BEFORE` instead.
- `Oro\Bundle\WorkflowBundle\Event\ExecuteActionEvents::HANDLE_AFTER` is deprecated. Use `Oro\Component\Action\Event\ExecuteActionEvents::HANDLE_AFTER` instead.
- Service `oro_workflow.action_assembler` is deprecated. Use `oro_action.action_assembler` instead.
- Service `oro_workflow.attribute_guesser` is deprecated. Use `oro_action.attribute_guesser` instead.
- Service `oro_workflow.context_accessor` is deprecated. Use `oro_action.context_accessor` instead.
- Service `oro_workflow.action_factory` is deprecated. Use `oro_action.action_factory` instead.
- Service `oro_workflow.configuration_pass.replace_property_path` is deprecated. Use `oro_action.configuration_pass.replace_property_path` instead.
- The constructor of the `Oro\Bundle\WorkflowBundle\Handler\TransitionHandler` class was changed. Third argument `LoggerInterface` (@logger service) was added.
- Added error logging in `Oro\Bundle\WorkflowBundle\Handler\TransitionHandler` at handle method.

####SearchBundle
- `Oro\Bundle\SearchBundle\DependencyInjection\OroSearchExtension::setEntitiesConfigParameter` deprecated since 1.9. Will be removed after 1.11. Please use oro_search.provider.search_mapping service for mapping config instead.
- `Oro\Bundle\SearchBundle\DependencyInjection\OroSearchExtension::mergeConfig` deprecated since 1.9. Will be removed after 1.11.
- `Oro\Bundle\SearchBundle\EventListener\UpdateSchemaDoctrineListener` is no longer requires `Oro\Bundle\SearchBundle\Engine\FulltextIndexManager` as an first argument

####FormBundle:
- 'Oro\Bundle\FormBundle\Form\Extension\RandomIdExtension' was renamed to 'Oro\Bundle\FormBundle\Form\Extension\AdditionalAttrExtension'
- 'oro_form.extension.random_id' service was renamed to 'oro_form.extension.additional_attr'
- Form field identifier - 'data-name' attribute generation added to 'AdditionalAttrExtension'

####TranslationBundle:
- Added translation strategies to dynamically handle translation fallbacks
- Refactored `Oro/Bundle/TranslationBundle/Translation/Translator` to support translation strategies

####DataGridBundle
- The class Oro\Bundle\DataGridBundle\Common\Object was renamed to DataObject
- Changed priority in next extensions:
    * Oro\Bundle\DataGridBundle\Extension\Sorter\OrmSorterExtension from -250 to -260
    * Oro\Bundle\DataGridBundle\Extension\Sorter\PostgresqlGridModifier from -251 to -261

####ConfigExpression
- The class Oro\Component\ConfigExpression\Condition\False was renamed to FalseCondition
- The class Oro\Component\ConfigExpression\Condition\True was renamed to TrueCondition

####UiBundle
- Added [lightgallery](http://sachinchoolur.github.io/lightGallery/) plugin by Sachin N.

Gallery view for a group of `<a>` elements can be triggered by adding 'data-gallery' attribute with unique gallery id.

```
<a href="sample1.jpg" data-gallery="unique-id"></a>
<a href="sample2.jpg" data-gallery="unique-id"></a>
```

####EmailBundle
- Constructor for `Oro\Bundle\EmailBundle\Manager\EmailAttachmentManager` was changed. New arguments: `Router $router`
- Constructor for `Oro\Bundle\EmailBundle\Tools\EmailAttachmentTransformer` was changed. New arguments: `AttachmentManager $manager, EmailAttachmentManager $emailAttachmentManager`
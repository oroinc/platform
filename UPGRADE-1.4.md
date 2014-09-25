UPGRADE FROM 1.3 to 1.4
=======================


####ChartBundle has been updated:
- Added `multiline_chart` support

####ConfigBundle has been updated:
- Added new configuration block `api_tree` into yaml file `system_configuration.yml`
- Account email relation is changed

####DashboardBundle:
- In `Entity\Repository\DashboardRepository` method `findDefaultDashboard` currently required mandatory parameter `Organization`
- `Model\Manger` now use `Organization` in `find Dashboard` methods (`findUserActiveDashboard`, `findDefaultDashboard`)

####DataGridBundle:
- `Extension\Pager\PagerInterface` no longer has `getLinks` method for implementation
- `datagrid-builder.js` has been removed and the replacement was added grid-views-builder.js
- Added column and column option guessers

####EmailBundle:
- `Builder\EmailEntityBatchInterface` currently has mandatory method `getChanges` for implementation
- `Entity\Email`:
    - Added new method `hasFolder`
    - Method `removeFolder` now returns `$this` instead of `false`
- `Entity\EmailFolder`:
    - All constants have been moved to the `Model\FolderType`
    - Added new method `removeEmail`
- `Entity\EmailOrigin`:
    - Added attribute `syncCount` with default value 0
    - Method `getIsActive` has been renamed into `isActive`
- `Model\EmailHeader` has been added
- `Sync\KnownEmailAddressChecker` now has method `preLoadEmailAddresses`, which can performs pre-loading of the given email addresses

####EmbeddedFormBundle:
- New events `oro_embedded_form.form_submit.after` and `oro_embedded_form.form_submit.before` have been added
- `Entity\EmbeddedForm` now is extendable

####EntityBundle:
- Configuration block `exclusions` has been added into `oro_entity` configuration block.
- Configuration block `virtual_fields`, entity virtual fields definitions, has been added into `oro_entity` configuration block.

####EntityConfigBundle:
- Command `oro:entity-config:init` has been removed
- `Config\ConfigManager` added new method `getConfigs` whereby you can get configuration data for all configurable entities, or or all configurable fields of the given
- `Entity\Repository\OptionSetRelationRepository` has been deprecated
- `Entity\Repository\OptionSetRepository` has been deprecated
- `Entity\OptionSet` has been deprecated
- `Entity\OptionSetRelation` has been deprecated
- `EventListener\OptionSetListener` has been deprecated
- `oro:entity-config:debug` command has been changed to get a different kind of configuration data as well as add/remove/update configuration of entities.
- Added form types which can handle 'immutable' behaviour.

####EntityExtendBundle:
- Support of `enum` and `multi-enum` types have been added
    - `enum` (named `Select` on UI) only one option may be selected
    - `multiEnum` (named `Multi-Select` on UI) - several options may be selected
- Added `EnumExtension` twig extension for `enum` type
- `EntityConfig\ExtendScope` constants `STATE_UPDATED` and `STATE_DELETED` have been deprecated

####FilterBundle:
- `Datasource\FilterDatasourceAdapterInterface` now has mandatory method `getFieldByAlias`
- New interface `Datasource\ManyRelationBuilderInterface` has been added

####FormBundle:
- Added new interface `Entity\PriorityItem`
- `Utils\FormUtils` new static method `appendClass` has been added

####ImapBundle:
- `Connector\ImapConnector` now has `getCapability` method to get capabilities of IMAP server
- `Connector\ImapMessageIterator` and `Manager\ImapEmailIterator` add new methods `setBatchSize` which determine how many messages can be loaded at once and `setBatchCallback`, sets a callback function which is called when a batch is loaded
- `Entity\Repository\ImapEmailFolderRepository` has been added
- `Entity\Repository\ImapEmailRepository` has been added
- `__toString()` method has been added to the `Entity\ImapEmailOrigin`
- Class `Mail\Storage\Folder` now can guess folder by type based on it is flags by `guessFolderType()`
- In `Mail\Storage\Imap` has been added new method `getMessages` which can help you to get messages with headers and body
- `Manager\DTO\Email` now is extended by `Oro\Bundle\EmailBundle\Model\EmailHeader`
- In `Manager\ImapEmailManager` new method `hasCapability` has been added

####InstallerBundle:
- New option `symlink` has been added into `Command\InstallCommand` and `Command\PlatformUpdateCommand`

####IntegrationBundle:
- New attribute `editMode` and three constants has been added into `Entity\Channel` which determine it status `EDIT_MODE_ALLOW`, `EDIT_MODE_RESTRICTED` and `EDIT_MODE_DISALLOW`. `EDIT_MODE_ALLOW` is by default.
- Class `Form\Type\IntegrationSelectType` with command `oro_integration_select` has been added
- Method `getAvailableIntegrationTypesDetailedChoiceList` has been renamed to `getAvailableIntegrationTypesDetailedData` in `Manager\TypesRegistry`

####NavigationBundle:
-  Into `Entity\NavigationHistoryItem` new attributes have been added: `organization`, `route`, `routeParameters`, `entityId`

####OrganizationBundle:
- New command `oro:organization:update` has been added
- Added `Controller\OrganizationController`, `Manager\OrganizationManager`, `Entity\Repository\OrganizationRepository`
- `Entity\Organization` now is extendable and implement  \Serializable
- `Entity\Repository\BusinessUnitRepository` new method `getOrganizationBusinessUnitsTree` has been added
- Added twig extension `oro_get_login_organizations` to show organization
- Each entity with ownership type `User` or `Business Unit` must have an extra field where will be stored organization.
- User can create API keys per organization
- In installer, instead of `application name` and `short application name` was added parameter `organization name` for default organization.
- User is able to edit details (name, description) of the default organization (System > User Management > Organizations).
- Want more? Read release note.

####QueryDesignerBundle:
- In `QueryDesigner\JoinIdentifierHelper` new methods `isUnidirectionalJoinWithCondition` and `getUnidirectionalJoinEntityName` have been

####SearchBundle:
- Class `Command\AddFulltextIndexesCommand` that was defined `oro:search:create-index` command has been removed
- Commands `oro:search:index` and `oro:search:reindex`currently have a new argument `class`
- In `Engine\Orm\BaseDriver` new method `truncateIndex` has been added
- Interface `Engine\EngineInterface` has been added
- Two methods have been added `truncateIndex`, `getItemsForEntities` into `Entity\Repository\SearchIndexRepository`
- Deprecated method `getLinks` in `Extension\Pager\IndexerPager` has been removed

####SecurityBundle:
- New method `setConfigProvider` has been added into `Acl\Voter\AclVoter`
- New method `setClass` has been added into `Annotation\Acl`
- `Authentication\Provider\UsernamePasswordOrganizationAuthenticationProvider` has been added
- `Authentication\Token\OrganizationContextTokenInterface` has been added
- `Authentication\Token\UsernamePasswordOrganizationToken` has been added
- Added ACL check for API requests
- ACL filters data by organizations has been added

####SoapBundle:
- New argument `$filters` in `Controller\Api\Rest\RestApiReadInterface` method `handleGetListRequest` has been added
- New argument `$criteria` in `Controller\Api\Soap\SoapApiReadInterface` method `handleGetListRequest`  has been added after argument `$limit`
- New events `Event\FindAfter` with name `oro_api.request.find.after` and `Event\GetListBefore` with name `oro_api.request.get_list.before` has been added

####UIBundle:
- `Page Component` has been added. It is an invisible component that takes responsibility of the controller for certain functionality.
- In `Tools\ArrayUtils` new method `arrayMergeRecursiveDistinct` has been added

####UserBundle:
- `Entity\Repository\UserApiRepository` has been added
- `Entity\Role` attribute `owner` has been removed
- `Entity\RoleSoap` attribute `owner` has been removed
- `Entity\UserManager` method `getApi` has been added
- `Security\AdvancedApiUserInterface` has been changed method name from `getApiKey` to `getApiKeys`
- `Security\WsseAuthProvider` new method authenticate has been added
- `Security\WsseToken` class has been created

#WorkflowBundle:
- `Acl\Voter\WorkflowEntityVoter` now extends from `Acl\Voter\AbstractEntityVoter`

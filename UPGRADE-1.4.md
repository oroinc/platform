UPGRADE FROM 1.3 to 1.4
=======================

####DashboardBundle:
- In `Entity\Repository\DashboardRepository` method `findDefaultDashboard` currently required mandatory parameter `Organization`
- `Model\Manger` now use `Organization` in `find Dashboard` methods (`findUserActiveDashboard`, `findDefaultDashboard`)

####DataGridBundle:
- `Extension\Pager\PagerInterface` no longer has `getLinks` method for implementation
- `Orm/OrmDatasource\OrmDatasource`:
    - now implement and `ParameterBinderAwareInterface`
    - methods `getParameterBinder` and `bindParameters` have been added
- `EventListener\BaseOrmRelationDatagridListener` has been deprecated

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
- `Sync\KnownEmailAddressChecker` now has method `preLoadEmailAddresses`, which can performs pre-loading of the given email addresses

####EmbeddedFormBundle:
- New events `oro_embedded_form.form_submit.after` and `oro_embedded_form.form_submit.before` have been added
- `Entity\EmbeddedForm` now is extendable

####EntityConfigBundle:
- Command `oro:entity-config:init` has been removed
- `Config\ConfigManager` added new method `getConfigs` whereby you can get configuration data for all configurable entities, or or all configurable fields of the given
- `Entity\Repository\OptionSetRelationRepository` has been deprecated
- `Entity\Repository\OptionSetRepository` has been deprecated
- `Entity\OptionSet` has been deprecated
- `Entity\OptionSetRelation` has been deprecated
- `EventListener\OptionSetListener` has been deprecated
- `oro:entity-config:debug` command has been changed to get a different kind of configuration data as well as add/remove/update configuration of entities.

####EntityExtendBundle:
- `EntityConfig\ExtendScope` constants `STATE_UPDATED` and `STATE_DELETED` have been deprecated

####FilterBundle:
- `Datasource\FilterDatasourceAdapterInterface` now has mandatory method `getFieldByAlias`

####FormBundle:
- `Utils\FormUtils` new static method `appendClass` has been added

####ImapBundle:
- `Connector\ImapConnector` now has `getCapability` method to get capabilities of IMAP server
- `Connector\ImapMessageIterator` and `Manager\ImapEmailIterator` add new methods `setBatchSize` which determine how many messages can be loaded at once and `setBatchCallback`, sets a callback function which is called when a batch is loaded
- Class `Mail\Storage\Folder` now can guess folder by type based on it is flags by `guessFolderType()`
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
- `Entity\Organization` now is extendable and implement  `\Serializable`
- In `Entity\Repository\BusinessUnitRepository` new method `getOrganizationBusinessUnitsTree` has been added

####QueryDesignerBundle:
- In `QueryDesigner\JoinIdentifierHelper` new methods `isUnidirectionalJoinWithCondition` and `getUnidirectionalJoinEntityName` have been added

####SearchBundle:
- Class `Command\AddFulltextIndexesCommand` that was defined `oro:search:create-index` command has been removed
- Commands `oro:search:index` and `oro:search:reindex`currently have a new argument `class`
- Two methods have been added `truncateIndex`, `getItemsForEntities` into `Entity\Repository\SearchIndexRepository`
- Deprecated method `getLinks` in `Extension\Pager\IndexerPager` has been removed

####SecurityBundle:
- New method `setConfigProvider` has been added into `Acl\Voter\AclVoter`
- New method `setClass` has been added into `Annotation\Acl`

####SoapBundle:
- New argument `$filters` in `Controller\Api\Rest\RestApiReadInterface` method `handleGetListRequest` has been added
- New argument `$criteria` in `Controller\Api\Soap\SoapApiReadInterface` method `handleGetListRequest`  has been added after argument `$limit`
- New events `Event\FindAfter` with name `oro_api.request.find.after` and `Event\GetListBefore` with name `oro_api.request.get_list.before` has been added

####TranslationBunle:
- Added debug translator that highlights translated and not translated strings on UI, see "Configuration" section of
`TranslationBundle` documentation for more details

####UIBundle:
- In `Tools\ArrayUtils` new method `arrayMergeRecursiveDistinct` has been added

####UserBundle:
- `Entity\Role` attribute `owner` has been removed
- `Entity\RoleSoap` attribute `owner` has been removed
- `Entity\UserManager` method `getApi` has been added
- `Security\AdvancedApiUserInterface` has been changed method name from `getApiKey` to `getApiKeys`
- `Security\WsseAuthProvider` new method `authenticate` has been added

####WorkflowBundle:
- `Acl\Voter\WorkflowEntityVoter` now extends from `Acl\Voter\AbstractEntityVoter`
- Workflow transitions might have custom templates, see transition options `dialog_template` and `page_template`

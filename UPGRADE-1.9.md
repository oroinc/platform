UPGRADE FROM 1.8 to 1.9
=======================

####ActivityListBundle
- `Oro\Bundle\ActivityListBundle\Entity\ActivityList::setEditor` deprecated since 1.8.0. Will be removed in 1.10.0. Use `Oro\Bundle\ActivityListBundle\Entity\ActivityList::setUpdatedBy` instead. 
- `Oro\Bundle\ActivityListBundle\Entity\ActivityList::getEditor` deprecated since 1.8.0. Will be removed in 1.10.0. Use `Oro\Bundle\ActivityListBundle\Entity\ActivityList::getUpdatedBy` instead.
- `Oro\Bundle\ActivityListBundle\Model\ActivityListDateProviderInterface::getDate` removed. Use `Oro\Bundle\ActivityListBundle\Model\ActivityListDateProviderInterface::getCreatedAt` and `Oro\Bundle\ActivityListBundle\Model\ActivityListDateProviderInterface::getUpdatedAt` instead
- `Oro\Bundle\ActivityListBundle\Model\ActivityListDateProviderInterface::isDateUpdatable` removed. It is not needed.
- `Oro\Bundle\ActivityListBundle\Model\ActivityListProviderInterface::getOwner` added.

####ConfigBundle
- An implementation of scope managers has been changed to be simpler and performant. This can bring a `backward compatibility break` if you have own scope managers. See [add_new_config_scope.md](./src/Oro/Bundle/ConfigBundle/Resources/doc/add_new_config_scope.md) and the next items for more detailed info.
- Method `loadStoredSettings` of `Oro\Bundle\ConfigBundle\Config\AbstractScopeManager` is `protected` now.
- Constructor for `Oro\Bundle\ConfigBundle\Config\AbstractScopeManager` changed. New arguments: `ManagerRegistry $doctrine, CacheProvider $cache`.
- Removed methods `loadSettings`, `getByEntity` of `Oro\Bundle\ConfigBundle\Entity\Repository\ConfigRepository`.
- Removed method `loadStoredSettings` of `Oro\Bundle\ConfigBundle\Config\ConfigManager`.
- Removed class `Oro\Bundle\ConfigBundle\Manager\UserConfigManager` and service `oro_config.user_config_manager`. Use `oro_config.user` service instead.

####EntityBundle
- Methods `getSingleRootAlias`, `getPageOffset`, `applyJoins` and `normalizeCriteria` of `Oro\Bundle\EntityBundle\ORM\DoctrineHelper` marked as deprecated. Use corresponding methods of `Oro\Bundle\EntityBundle\ORM\QueryUtils` instead.


####EntityConfigBundle
- Removed `optionSet` field type deprecated since v1.4. Existing options sets are converted to `Select` or `Multi-Select` automatically during the Platform update.
- `Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface` marked as deprecated. Use `Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider` instead.
- Renamed `Oro\Bundle\EntityConfigBundle\Entity\AbstractConfigModel` to `Oro\Bundle\EntityConfigBundle\Entity\ConfigModel`.
- Constants `MODE_DEFAULT`, `MODE_HIDDEN` and `MODE_READONLY` of `Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager` marked as deprecated. Use the same constants of `Oro\Bundle\EntityConfigBundle\Entity\ConfigModel` instead. Also `isDefault()`, `isHidden()` and `isReadOnly()` methods of `Oro\Bundle\EntityConfigBundle\Entity\ConfigModel` can be used.
- Method `clearCache` of `Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider` marked as deprecated. Use the method `clearCache` of `Oro\Bundle\EntityConfigBundle\Config\ConfigManager` instead. The ConfigManager can be retrieved using the `getConfigManager()` of the ConfigProvider.
- Method `persist` of `Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider` marked as deprecated. Use the method `persist` of `Oro\Bundle\EntityConfigBundle\Config\ConfigManager` instead. The ConfigManager can be retrieved using the `getConfigManager()` of the ConfigProvider.
- Method `merge` of `Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider` marked as deprecated. Use the method `merge` of `Oro\Bundle\EntityConfigBundle\Config\ConfigManager` instead. The ConfigManager can be retrieved using the `getConfigManager()` of the ConfigProvider.
- Method `flush` of `Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider` marked as deprecated. Use the method `flush` of `Oro\Bundle\EntityConfigBundle\Config\ConfigManager` instead. The ConfigManager can be retrieved using the `getConfigManager()` of the ConfigProvider.
- Event `Oro\Bundle\EntityConfigBundle\Event\Events::NEW_ENTITY_CONFIG` (`entity_config.new.entity.config`) marked as deprecated. Use `Oro\Bundle\EntityConfigBundle\Event\Events::CREATE_ENTITY` (`oro.entity_config.entity.create`) instead.
- Event `Oro\Bundle\EntityConfigBundle\Event\Events::UPDATE_ENTITY_CONFIG` (`entity_config.update.entity.config`) marked as deprecated. Use `Oro\Bundle\EntityConfigBundle\Event\Events::UPDATE_ENTITY` (`oro.entity_config.entity.update`) instead.
- Event `Oro\Bundle\EntityConfigBundle\Event\Events::NEW_FIELD_CONFIG` (`entity_config.new.field.config`) marked as deprecated. Use `Oro\Bundle\EntityConfigBundle\Event\Events::CREATE_FIELD` (`oro.entity_config.field.create`) instead.
- Event `Oro\Bundle\EntityConfigBundle\Event\Events::UPDATE_FIELD_CONFIG` (`entity_config.update.field.config`) marked as deprecated. Use `Oro\Bundle\EntityConfigBundle\Event\Events::UPDATE_FIELD` (`oro.entity_config.field.update`) instead.
- Event name `Oro\Bundle\EntityConfigBundle\Event\Events::RENAME_FIELD` is renamed from `entity_config.rename.field` to `oro.entity_config.field.rename`. Old event marked as deprecated. Use `Oro\Bundle\EntityConfigBundle\Event\Events::RENAME_FIELD` (`oro.entity_config.field.rename`) instead.
- Event `Oro\Bundle\EntityConfigBundle\Event\Events::PRE_PERSIST_CONFIG` (`entity_config.persist.config`) marked as deprecated. Use `Oro\Bundle\EntityConfigBundle\Event\Events::PRE_FLUSH` (`oro.entity_config.pre_flush`) instead.
- Event `Oro\Bundle\EntityConfigBundle\Event\Events::POST_FLUSH_CONFIG` (`entity_config.flush.config`) marked as deprecated. Use `Oro\Bundle\EntityConfigBundle\Event\Events::POST_FLUSH` (`oro.entity_config.post_flush`) instead.
- New `Oro\Bundle\EntityConfigBundle\Migration\RemoveEnumFieldQuery` added. It using for remove outdated enum field data for entity.

####SecurityBundle
- `Oro\Bundle\SecurityBundle\Owner\OwnerTreeInterface` is changed. New method `buildTree` added (due to performance issues). It should be called once after all `addDeepEntity` calls. See [OwnerTreeProvider](./src/Oro/Bundle/SecurityBundle/Owner/OwnerTreeProvider.php) method `fillTree`. Implementation example [OwnerTree](./src/Oro/Bundle/SecurityBundle/Owner/OwnerTree.php).
- Bundle now contains part of Symfony security configuration (ACL configuration and access decision manager strategy) 

####EmbeddedFormBundle
 - Bundle now contains configuration of security firewall `embedded_form` 

####PlatformBundle
 - Bundle now has priority `-200` and it is loaded right after main Symfony bundles

####SoapBundle
 - Bundle now contains configuration of security firewall `wsse_secured` 

####TrackingBundle
 - Bundle now contains configuration of security firewall `tracking_data` 

####UiBundle
 - Added possibility to group tabs in dropdown for tabs panel. Added options to tabPanel function. Example: `{{ tabPanel(tabs, {useDropdown: true}) }}`
 - Added possibility to set content for specific tab. Example: `{{ tabPanel([{label: 'Tab', content: 'Tab content'}]) }}`

####UserBundle
 - Bundle now contains configuration of security providers (`chain_provider`, `oro_user`, `in_memory`), encoders and security firewalls (`login`, `reset_password`, `main`)
 - Bundle DI extension `OroUserExtension` has been updated to make sure that `main` security firewall is always the last in list

####WorkflowBundle
 - Constructor of `Oro\Bundle\WorkflowBundle\Model\Process` changed. New argument: `ConditionFactory $conditionFactory`
 - Constructor of `Oro\Bundle\WorkflowBundle\Model\ProcessFactory` changed. New argument: `ConditionFactory $conditionFactory`
 - Added new process definition option `pre_conditions`
 - Class `Oro\Bundle\WorkflowBundle\Model\WorkflowManager` now has method `massTransit` to perform several transitions in one transaction, can be used to improve workflow performance

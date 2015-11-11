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

####EntityExtendBundle
- Added parameters `Oro\Bundle\EntityExtendBundle\Provider\FieldTypeProvider` to constructor of `Oro\Bundle\EntityExtendBundle\Form\Type\FieldType`

####ImportExportBundle
- `Oro\Bundle\ImportExportBundle\Writer\EntityDetachFixer`: the first argument of constructor `Doctrine\ORM\EntityManager $entityManager` replaced by `Doctrine\Common\Persistence\ManagerRegistry $registry`
- `Oro\Bundle\ImportExportBundle\Writer\EntityWriter`: the first argument of constructor `Doctrine\ORM\EntityManager $entityManager` replaced by `Doctrine\Common\Persistence\ManagerRegistry $registry`
- `Oro\Bundle\ImportExportBundle\Writer\DoctrineClearWriter`: the first argument of constructor `Doctrine\ORM\EntityManager $entityManager` replaced by `Doctrine\Common\Persistence\ManagerRegistry $registry`
- `Oro\Bundle\ImportExportBundle\Writer\DummyWriter`: the first argument of constructor `Doctrine\ORM\EntityManager $entityManager` replaced by `Doctrine\Common\Persistence\ManagerRegistry $registry`

####FormBundle
- Add new form type: `oro_autocomplete`. See [text_autocomplete_form_type.md](./src/Oro/Bundle/FormBundle/Resources/doc/reference/text_autocomplete_form_type.md) for more detailed info.

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
 - Route `oro_workflow_api_rest_process_activate` marked as deprecated. Use the route `oro_api_process_activate` instead.
 - Route `oro_workflow_api_rest_process_deactivate` marked as deprecated. Use the route `oro_api_process_deactivate` instead.
 - Route `oro_workflow_api_rest_workflowdefinition_get` marked as deprecated. Use the route `oro_api_workflow_definition_get` instead.
 - Route `oro_workflow_api_rest_workflowdefinition_post` marked as deprecated. Use the route `oro_api_workflow_definition_post` instead.
 - Route `oro_workflow_api_rest_workflowdefinition_put` marked as deprecated. Use the route `oro_api_workflow_definition_put` instead.
 - Route `oro_workflow_api_rest_workflowdefinition_delete` marked as deprecated. Use the route `oro_api_workflow_definition_delete` instead.
 - Route `oro_workflow_api_rest_entity_get` marked as deprecated. Use the route `oro_api_workflow_entity_get` instead.
 - Route `oro_workflow_api_rest_workflow_get` marked as deprecated. Use the route `oro_api_workflow_get` instead.
 - Route `oro_workflow_api_rest_workflow_delete` marked as deprecated. Use the route `oro_api_workflow_delete` instead.
 - Route `oro_workflow_api_rest_workflow_activate` marked as deprecated. Use the route `oro_api_workflow_activate` instead.
 - Route `oro_workflow_api_rest_workflow_deactivate` marked as deprecated. Use the route `oro_api_workflow_deactivate` instead.
 - Route `oro_workflow_api_rest_workflow_start` marked as deprecated. Use the route `oro_api_workflow_start` instead.
 - Route `oro_workflow_api_rest_workflow_transit` marked as deprecated. Use the route `oro_api_workflow_transit` instead.

####EmailBundle
 - Method `setFolder` of `Oro\Bundle\EmailBundle\Entity\EmailUser` marked as deprecated. Use the method `addFolder` instead.

####ImportExportBundle:
- Added `Oro\Bundle\ImportExportBundle\Formatter\ExcelDateTimeTypeFormatter` as default formatter for the date, time and datetime types in `Oro\Bundle\ImportExportBundle\Serializer\Normalizer\DateTimeNormalizer`. This types exported/imported depends on the application locale and timezone and recognized as dates in Microsoft Excel.

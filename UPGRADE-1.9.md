UPGRADE FROM 1.8 to 1.9
=======================

####ActivityListBundle
- `Oro\Bundle\ActivityListBundle\Entity\ActivityList::setEditor` deprecated since 1.8.0. Will be removed in 1.10.0. Use `Oro\Bundle\ActivityListBundle\Entity\ActivityList::setUpdatedBy` instead. 
- `Oro\Bundle\ActivityListBundle\Entity\ActivityList::getEditor` deprecated since 1.8.0. Will be removed in 1.10.0. Use `Oro\Bundle\ActivityListBundle\Entity\ActivityList::getUpdatedBy` instead.
- `Oro\Bundle\ActivityListBundle\Model\ActivityListDateProviderInterface::getDate` removed. Use `Oro\Bundle\ActivityListBundle\Model\ActivityListDateProviderInterface::getCreatedAt` and `Oro\Bundle\ActivityListBundle\Model\ActivityListDateProviderInterface::getUpdatedAt` instead
- `Oro\Bundle\ActivityListBundle\Model\ActivityListDateProviderInterface::isDateUpdatable` removed. It is not needed.
- `Oro\Bundle\ActivityListBundle\Model\ActivityListProviderInterface::getOwner` added.

####EntityConfigBundle
- Removed `optionSet` field type deprecated since v1.4. Existing options sets are converted to `Select` or `Multi-Select` automatically during the Platform update.
- `Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface` marked as deprecated. Use `Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider` instead.
- Method `clearCache` of `Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider` marked as deprecated. Use the method `clearCache` of `Oro\Bundle\EntityConfigBundle\Config\ConfigManager` instead. The ConfigManager can be retrieved using the the `getConfigManager()` of the ConfigProvider.
- Method `persist` of `Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider` marked as deprecated. Use the method `persist` of `Oro\Bundle\EntityConfigBundle\Config\ConfigManager` instead. The ConfigManager can be retrieved using the the `getConfigManager()` of the ConfigProvider.
- Method `merge` of `Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider` marked as deprecated. Use the method `merge` of `Oro\Bundle\EntityConfigBundle\Config\ConfigManager` instead. The ConfigManager can be retrieved using the the `getConfigManager()` of the ConfigProvider.
- Method `flush` of `Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider` marked as deprecated. Use the method `flush` of `Oro\Bundle\EntityConfigBundle\Config\ConfigManager` instead. The ConfigManager can be retrieved using the the `getConfigManager()` of the ConfigProvider.
- Event `Oro\Bundle\EntityConfigBundle\Event\Events::NEW_ENTITY_CONFIG` (`entity_config.new.entity.config`) marked as deprecated. Use `Oro\Bundle\EntityConfigBundle\Event\Events::CREATE_ENTITY` (`oro.entity_config.entity.create`) instead.
- Event `Oro\Bundle\EntityConfigBundle\Event\Events::UPDATE_ENTITY_CONFIG` (`entity_config.update.entity.config`) marked as deprecated. Use `Oro\Bundle\EntityConfigBundle\Event\Events::UPDATE_ENTITY` (`oro.entity_config.entity.update`) instead.
- Event `Oro\Bundle\EntityConfigBundle\Event\Events::NEW_FIELD_CONFIG` (`entity_config.new.field.config`) marked as deprecated. Use `Oro\Bundle\EntityConfigBundle\Event\Events::CREATE_FIELD` (`oro.entity_config.field.create`) instead.
- Event `Oro\Bundle\EntityConfigBundle\Event\Events::UPDATE_FIELD_CONFIG` (`entity_config.update.field.config`) marked as deprecated. Use `Oro\Bundle\EntityConfigBundle\Event\Events::UPDATE_FIELD` (`oro.entity_config.field.update`) instead.
- Event name for `Oro\Bundle\EntityConfigBundle\Event\Events::RENAME_FIELD` changed from `entity_config.rename.field` to `oro.entity_config.field.rename`. Old event marked as deprecated. Use `Oro\Bundle\EntityConfigBundle\Event\Events::RENAME_FIELD` (`oro.entity_config.field.rename`) instead.
- Event `Oro\Bundle\EntityConfigBundle\Event\Events::PRE_PERSIST_CONFIG` (`entity_config.persist.config`) marked as deprecated. Use `Oro\Bundle\EntityConfigBundle\Event\Events::PRE_FLUSH` (`oro.entity_config.pre_flush`) instead.
- Event `Oro\Bundle\EntityConfigBundle\Event\Events::POST_FLUSH_CONFIG` (`entity_config.flush.config`) marked as deprecated. Use `Oro\Bundle\EntityConfigBundle\Event\Events::POST_FLUSH` (`oro.entity_config.post_flush`) instead.

<?php

namespace Oro\Bundle\EntityMergeBundle\EventListener\Metadata;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface;

use Oro\Bundle\EntityMergeBundle\Event\EntityMetadataEvent;
use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;

class EntityConfigListener
{
    const MERGE_SCOPE = 'merge';
    const ENTITY_SCOPE = 'entity';

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @param EntityMetadataEvent $event
     */
    public function onCreateMetadata(EntityMetadataEvent $event)
    {
        $entityMetadata = $event->getEntityMetadata();

        $mergeConfig = $this->configManager->getProvider(self::MERGE_SCOPE);

        $this->applyEntityMetadataConfig($entityMetadata, $mergeConfig);
        $this->applyFieldMetadataConfig($entityMetadata, $mergeConfig);
    }

    protected function applyEntityMetadataConfig(EntityMetadata $entityMetadata, ConfigProviderInterface $mergeConfig)
    {
        $className = $entityMetadata->getClassName();

        if ($mergeConfig->hasConfig($className)) {
            $entityMetadata->merge($mergeConfig->getConfig($className)->all());
        }
    }

    protected function applyFieldMetadataConfig(EntityMetadata $entityMetadata, ConfigProviderInterface $mergeConfig)
    {
        foreach ($entityMetadata->getFieldsMetadata() as $fieldMetadata) {
            $className = $fieldMetadata->getSourceClassName();
            $fieldName = $fieldMetadata->getSourceFieldName();

            // Match simple field
            if ($mergeConfig->hasConfig($className, $fieldName)) {
                $fieldMetadata->merge($mergeConfig->getConfig($className, $fieldName)->all());
            }
        }
    }

    /**
     * @return ConfigProviderInterface
     */
    protected function getEntityConfigProvider()
    {
        return $this->configManager->getProvider(self::ENTITY_SCOPE);
    }
}

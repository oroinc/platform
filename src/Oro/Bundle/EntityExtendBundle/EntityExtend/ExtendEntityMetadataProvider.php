<?php

namespace Oro\Bundle\EntityExtendBundle\EntityExtend;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

/**
 * Provide metadata for extend entity.
 */
class ExtendEntityMetadataProvider implements ExtendEntityMetadataProviderInterface
{
    protected const SCOPE = 'extend';
    protected array $entityFieldsCache = [];
    protected array $entityManageable = [];

    public function __construct(
        private ConfigManager  $configManager,
        private DoctrineHelper $doctrineHelper,
    ) {
    }

    private function isManageableClass(string $class): bool
    {
        return $this->doctrineHelper->isManageableEntityClass($class);
    }

    public function getExtendEntityMetadata(string $class): ConfigInterface
    {
        return $this->configManager->getEntityConfig(self::SCOPE, $this->obtainClassName($class));
    }

    public function getExtendEntityFieldsMetadata(string $class): array
    {
        $class = $this->obtainClassName($class);
        if (isset($this->entityFieldsCache[$class])) {
            return $this->entityFieldsCache[$class];
        }
        $configs = $this->configManager->getConfigs('extend', $class, true);
        $result = [];
        /** @var  $config */
        foreach ($configs as $config) {
            $configValues = $config->getValues();
            if (empty($configValues['is_extend'])) {
                continue;
            }
            $fieldName = $config->getId()->getFieldName();
            $result[$fieldName] = [
                'fieldName' => $fieldName,
                'fieldType' => $config->getId()->getFieldType(),
                'is_extend' => $configValues['is_extend'],
                'is_serialized' => $configValues['is_serialized'],
            ];
            if (isset($configValues['default'])) {
                $result[$fieldName]['default'] = $configValues['default'];
            }
        }

        return $this->entityFieldsCache[$class] = $result;
    }

    private function obtainClassName(string $class): string
    {
        if (isset($this->entityManageable[$class])) {
            return $this->entityManageable[$class];
        }
        if (!$this->isManageableClass($class)) {
            $parentClass = get_parent_class($class);
            if ($parentClass && $this->isManageableClass($parentClass)) {
                return $this->entityManageable[$class] = $parentClass;
            }
        }

        return $this->entityManageable[$class] = $class;
    }
}

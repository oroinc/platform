<?php

namespace Oro\Bundle\EntityConfigBundle\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager as EntityConfigManager;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;

/**
 * Excludes fields that are not required for export.
 */
class ExportQueryProvider
{
    private EntityConfigManager $entityConfigManager;

    public function __construct(EntityConfigManager $entityConfigManager)
    {
        $this->entityConfigManager = $entityConfigManager;
    }

    public function isAssociationExportable(ClassMetadata $metadata, string $fieldName): bool
    {
        if (!$this->isExportable($metadata->getName(), $fieldName)) {
            return false;
        }

        if (!$metadata->isAssociationWithSingleJoinColumn($fieldName)) {
            return false;
        }

        $targetEntity = $metadata->getAssociationMapping($fieldName)['targetEntity'] ?? null;

        return $this->isTargetEntityExportable($targetEntity);
    }

    /**
     * Reduces the number of fields to select thus allowing to export entities with a large number of fields.
     */
    private function isTargetEntityExportable(?string $entityName): bool
    {
        if (is_a($entityName, AbstractEnumValue::class, true)) {
            return false;
        }

        if (is_a($entityName, EntityFieldFallbackValue::class, true)) {
            return false;
        }

        return true;
    }

    private function isExportable(string $entityName, string $fieldName): bool
    {
        // In batch export to join an associate field to prevent entity be loaded in lazy mode and detached accidentally
        if ($this->entityConfigManager->hasConfig($entityName, $fieldName)) {
            $config = $this->entityConfigManager->getFieldConfig('importexport', $entityName, $fieldName);
            if ($config->has('excluded')) {
                return !$config->get('excluded');
            }
        }

        return false;
    }
}

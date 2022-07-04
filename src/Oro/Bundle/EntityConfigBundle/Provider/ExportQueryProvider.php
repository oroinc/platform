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
        // Excluded fields are not used for export, so there is no need to include them to query.
        if ($this->getFieldConfig($entityName, $fieldName, 'excluded')) {
            return false;
        }

        // To export field with the parameter 'full = false' or 'null' only identifier field is enough,
        // it is not necessary to join fields in query.
        if (!$this->getFieldConfig($entityName, $fieldName, 'full')) {
            return false;
        }

        return true;
    }

    private function getFieldConfig(string $entityName, string $fieldName, string $code): bool
    {
        if ($this->entityConfigManager->hasConfig($entityName, $fieldName)) {
            $config = $this->entityConfigManager->getFieldConfig('importexport', $entityName, $fieldName);
            if ($config->has($code)) {
                return $config->get($code);
            }
        }

        return false;
    }
}

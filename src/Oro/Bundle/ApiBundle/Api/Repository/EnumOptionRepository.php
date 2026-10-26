<?php

declare(strict_types=1);

namespace Oro\Bundle\ApiBundle\Api\Repository;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Provides enum options and the configurations of fields that use them.
 */
class EnumOptionRepository
{
    public function __construct(
        private readonly ConfigManager $configManager
    ) {
    }

    /**
     * @return ConfigInterface[] Field configs
     */
    public function getConfigs(string $enumCode): array
    {
        $enumFieldConfigs = [];
        $fieldConfigs = $this->configManager->findFieldConfigsByIndexedValue('enum', 'enum_code', $enumCode);
        foreach ($fieldConfigs as $fieldConfig) {
            /** @var FieldConfigId $fieldConfigId */
            $fieldConfigId = $fieldConfig->getId();
            if (!ExtendHelper::isEnumerableType($fieldConfigId->getFieldType())) {
                continue;
            }

            if (ExtendHelper::isFieldAccessible($this->getExtendFieldConfig($fieldConfigId))) {
                $enumFieldConfigs[] = $fieldConfig;
            }
        }

        return $enumFieldConfigs;
    }

    /**
     * @param ConfigInterface[] $fieldConfigs
     */
    public function isSystemEnum(array $fieldConfigs): bool
    {
        foreach ($fieldConfigs as $fieldConfig) {
            /** @var FieldConfigId $fieldConfigId */
            $fieldConfigId = $fieldConfig->getId();
            if ($this->getExtendFieldConfig($fieldConfigId)->is('owner', ExtendScope::OWNER_SYSTEM)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param ConfigInterface[] $fieldConfigs
     */
    public function isImmutableEnum(array $fieldConfigs): bool
    {
        foreach ($fieldConfigs as $fieldConfig) {
            $immutable = $fieldConfig->get('immutable');
            if (true === $immutable || (\is_array($immutable) && \in_array('add', $immutable, true))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Creates a non-persisted option from an identifier in the "enum_code.option_id" format.
     */
    public function createOptionFromId(string $id): ?EnumOption
    {
        $enumCode = ExtendHelper::extractEnumCode($id);
        $internalId = ExtendHelper::getEnumInternalId($id);
        if (!$this->isValidIdentifier($id, $enumCode, $internalId)) {
            return null;
        }

        return new EnumOption($enumCode, $internalId, $internalId);
    }

    private function isValidIdentifier(string $id, string $enumCode, string $internalId): bool
    {
        if (1 !== substr_count($id, ExtendHelper::ENUM_OPTION_SEPARATOR)) {
            return false;
        }

        if (!$enumCode || !$internalId) {
            return false;
        }

        return
            \strlen($enumCode) <= ExtendDbIdentifierNameGenerator::MAX_ENUM_CODE_SIZE
            && \strlen($internalId) <= ExtendHelper::MAX_ENUM_INTERNAL_ID_LENGTH;
    }

    private function getExtendFieldConfig(FieldConfigId $fieldConfigId): ConfigInterface
    {
        return $this->configManager->getFieldConfig(
            'extend',
            $fieldConfigId->getClassName(),
            $fieldConfigId->getFieldName()
        );
    }
}

<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds associations with enum option entities.
 */
class AddEnumOptionAssociations implements ProcessorInterface
{
    private DoctrineHelper $doctrineHelper;
    private ConfigManager $configManager;

    public function __construct(DoctrineHelper $doctrineHelper, ConfigManager $configManager)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->configManager = $configManager;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ConfigContext $context */

        $entityClass = $context->getClassName();
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            // only manageable entities are supported
            return;
        }
        if (!$this->configManager->hasConfig($entityClass)) {
            // only configurable entities are supported
            return;
        }

        $skipNotConfiguredCustomFields =
            $context->getRequestedExclusionPolicy() === ConfigUtil::EXCLUSION_POLICY_CUSTOM_FIELDS
            && $this->isExtendSystemEntity($entityClass);
        $this->addEnumOptionFields($context->getResult(), $entityClass, $skipNotConfiguredCustomFields);
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function addEnumOptionFields(
        EntityDefinitionConfig $definition,
        string $entityClass,
        bool $skipNotConfiguredCustomFields
    ): void {
        $fieldConfigs = $this->configManager->getConfigs('extend', $entityClass);
        foreach ($fieldConfigs as $fieldConfig) {
            /** @var FieldConfigId $fieldId */
            $fieldId = $fieldConfig->getId();
            $fieldType = $fieldId->getFieldType();
            if (!ExtendHelper::isEnumerableType($fieldType) || !ExtendHelper::isFieldAccessible($fieldConfig)) {
                continue;
            }

            $fieldName = $fieldId->getFieldName();
            $field = $definition->findField($fieldName, true);
            if (null !== $field) {
                $enumCode = $this->getEnumCode($entityClass, $fieldName);
                if ($enumCode) {
                    if (!$field->hasDataType()) {
                        $this->configureEnumField($field, $fieldType, $enumCode);
                    }
                } elseif (!$field->getDataType()) {
                    $field->setDataType($fieldType);
                }
            } elseif (!$skipNotConfiguredCustomFields || !$this->isCustomField($fieldConfig)) {
                $enumCode = $this->getEnumCode($entityClass, $fieldName);
                if ($enumCode) {
                    $field = $definition->addField($fieldName);
                    $this->configureEnumField($field, $fieldType, $enumCode);
                }
            }
        }
    }

    private function isExtendSystemEntity(string $entityClass): bool
    {
        $entityConfig = $this->configManager->getEntityConfig('extend', $entityClass);

        return
            $entityConfig->is('is_extend')
            && !$entityConfig->is('owner', ExtendScope::OWNER_CUSTOM);
    }

    private function isCustomField(ConfigInterface $fieldConfig): bool
    {
        return
            $fieldConfig->is('is_extend')
            && $fieldConfig->is('owner', ExtendScope::OWNER_CUSTOM);
    }

    private function getEnumCode(string $entityClass, string $fieldName): ?string
    {
        return $this->configManager->getFieldConfig('enum', $entityClass, $fieldName)->get('enum_code');
    }

    private function configureEnumField(EntityDefinitionFieldConfig $field, string $fieldType, string $enumCode): void
    {
        $field->setDataType(DataType::STRING);
        $field->setTargetClass(ExtendHelper::getOutdatedEnumOptionClassName($enumCode));
        $field->setTargetType(ConfigUtil::getAssociationTargetType(ExtendHelper::isMultiEnumType($fieldType)));
    }
}

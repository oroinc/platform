<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Excludes custom fields (fields with "is_extend" = true and "owner" = "Custom"
 * in "extend" scope in entity configuration).
 * The not manageable entities, custom entities and entities
 * with "exclusion_policy" not equal to "custom_fields" are skipped.
 */
class ExcludeCustomFields implements ProcessorInterface
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var ConfigManager */
    private $configManager;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ConfigManager  $configManager
     */
    public function __construct(DoctrineHelper $doctrineHelper, ConfigManager $configManager)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        $definition = $context->getResult();
        if ($definition->getExclusionPolicy() !== ConfigUtil::EXCLUSION_POLICY_CUSTOM_FIELDS) {
            // exclusion of custom fields was not requested
            return;
        }

        $entityClass = $context->getClassName();
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            // only manageable entities are supported
            return;
        }
        if (!$this->configManager->hasConfig($entityClass)) {
            // only configurable entities are supported
            return;
        }

        $entityConfig = $this->configManager->getEntityConfig('extend', $entityClass);
        if ($entityConfig->is('is_extend') && !$entityConfig->is('owner', ExtendScope::OWNER_CUSTOM)) {
            $this->excludeCustomFields($definition, $entityClass);
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $entityClass
     */
    private function excludeCustomFields(EntityDefinitionConfig $definition, $entityClass)
    {
        $fieldNamesToBeExcluded = [];
        $fieldConfigs = $this->configManager->getConfigs('extend', $entityClass, true);
        foreach ($fieldConfigs as $fieldConfig) {
            if ($fieldConfig->is('is_extend')
                && $fieldConfig->is('owner', ExtendScope::OWNER_CUSTOM)
                && ExtendHelper::isFieldAccessible($fieldConfig)
            ) {
                /** @var FieldConfigId $fieldId */
                $fieldId = $fieldConfig->getId();
                $propertyPath = $fieldId->getFieldName();
                if (null === $definition->findFieldNameByPropertyPath($propertyPath)) {
                    $fieldNamesToBeExcluded[] = $propertyPath;
                }
            }
        }
        foreach ($fieldNamesToBeExcluded as $fieldName) {
            $definition->getOrAddField($fieldName)->setExcluded();
        }
    }
}

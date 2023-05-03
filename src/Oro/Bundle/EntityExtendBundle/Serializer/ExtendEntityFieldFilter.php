<?php

namespace Oro\Bundle\EntityExtendBundle\Serializer;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Component\EntitySerializer\EntityFieldFilterInterface;

/**
 * The service that is used to check whether a specific entity field
 * is applicable to process by the entity serializer component.
 */
class ExtendEntityFieldFilter implements EntityFieldFilterInterface
{
    private ConfigManager $configManager;
    private bool $allowExtendedFields;

    public function __construct(ConfigManager $configManager, bool $allowExtendedFields = false)
    {
        $this->configManager = $configManager;
        $this->allowExtendedFields = $allowExtendedFields;
    }

    /**
     * {@inheritDoc}
     */
    public function isApplicableField(string $className, string $fieldName): bool
    {
        if (null === $this->configManager->getConfigModelId($className, $fieldName)) {
            // non configurable entities are supported  as well
            return true;
        }

        if (true === $this->configManager->isHiddenModel($className, $fieldName)) {
            // exclude hidden fields
            return false;
        }

        $extendConfig = $this->configManager->getFieldConfig('extend', $className, $fieldName);

        if (!$this->allowExtendedFields && $extendConfig->is('is_extend')) {
            // exclude extended fields if it is requested
            return false;
        }

        if (!ExtendHelper::isFieldAccessible($extendConfig)) {
            return false;
        }

        if ($extendConfig->has('target_entity')
            && !ExtendHelper::isEntityAccessible(
                $this->configManager->getEntityConfig('extend', $extendConfig->get('target_entity'))
            )
        ) {
            return false;
        }

        return true;
    }
}

<?php

namespace Oro\Bundle\EntityExtendBundle\Serializer;

use Oro\Component\EntitySerializer\EntityFieldFilterInterface;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class ExtendEntityFieldFilter implements EntityFieldFilterInterface
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var bool */
    protected $allowExtendedFields;

    /**
     * @param ConfigManager $configManager
     * @param bool          $allowExtendedFields
     */
    public function __construct(ConfigManager $configManager, $allowExtendedFields = false)
    {
        $this->configManager       = $configManager;
        $this->allowExtendedFields = $allowExtendedFields;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicableField($className, $fieldName)
    {
        if (null === $this->configManager->getConfigModelId($className, $fieldName)) {
            // this serializer works with non configurable entities as well
            return true;
        }

        if (true === $this->configManager->isHiddenModel($className, $fieldName)) {
            // exclude hidden fields
            return false;
        }

        $extendConfigProvider = $this->configManager->getProvider('extend');
        $extendConfig         = $extendConfigProvider->getConfig($className, $fieldName);

        if (!$this->allowExtendedFields && $extendConfig->is('is_extend')) {
            // exclude extended fields if it is requested
            return false;
        }

        if (!ExtendHelper::isFieldAccessible($extendConfig)) {
            return false;
        }

        if ($extendConfig->has('target_entity')
            && !ExtendHelper::isEntityAccessible($extendConfigProvider->getConfig($extendConfig->get('target_entity')))
        ) {
            return false;
        }

        return true;
    }
}

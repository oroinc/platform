<?php

namespace Oro\Bundle\EntityExtendBundle\Grid;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Component\PhpUtils\ArrayUtil;

class FieldsHelper
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var FeatureChecker */
    protected $featureChecker;

    /**
     * @param ConfigManager $configManager
     * @param FeatureChecker $featureChecker
     */
    public function __construct(
        ConfigManager $configManager,
        FeatureChecker $featureChecker
    ) {
        $this->configManager = $configManager;
        $this->featureChecker = $featureChecker;
    }

    /**
     * @param string $entityClassName
     *
     * @return FieldConfigId[]
     */
    public function getFields($entityClassName)
    {
        if (!$this->configManager->hasConfig($entityClassName)) {
            return [];
        }

        $entityConfigProvider   = $this->configManager->getProvider('entity');
        $extendConfigProvider   = $this->configManager->getProvider('extend');
        $viewConfigProvider     = $this->configManager->getProvider('view');
        $datagridConfigProvider = $this->configManager->getProvider('datagrid');

        $fields = [];
        $fieldIds = $entityConfigProvider->getIds($entityClassName);

        /** @var FieldConfigId $fieldId */
        foreach ($fieldIds as $fieldId) {
            $extendConfig = $extendConfigProvider->getConfigById($fieldId);
            $fieldConfig = $datagridConfigProvider->getConfigById($fieldId);

            if ($this->isApplicableField($extendConfig, $fieldConfig)) {
                $viewConfig = $viewConfigProvider->getConfig($entityClassName, $fieldId->getFieldName());
                $fields[] = [
                    'id'       => $fieldId,
                    'priority' => $viewConfig->get('priority', false, 0)
                ];
            }
        }

        ArrayUtil::sortBy($fields, true);

        return array_map(
            function ($field) {
                return $field['id'];
            },
            $fields
        );
    }

    /**
     * @param ConfigInterface $extendConfig
     * @param ConfigInterface $fieldConfig
     *
     * @return bool
     */
    protected function isApplicableField(ConfigInterface $extendConfig, ConfigInterface $fieldConfig)
    {
        if ($extendConfig->has('target_entity')
            && !$this->featureChecker->isResourceEnabled($extendConfig->get('target_entity'), 'entities')
        ) {
            return false;
        }

        return $extendConfig->is('owner', ExtendScope::OWNER_CUSTOM)
            && ExtendHelper::isFieldAccessible($extendConfig)
            && $fieldConfig->is('is_visible');
    }
}

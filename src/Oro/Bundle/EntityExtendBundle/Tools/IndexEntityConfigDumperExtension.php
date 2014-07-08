<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

class IndexEntityConfigDumperExtension extends AbstractEntityConfigDumperExtension
{
    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($actionType)
    {
        if ($actionType === ExtendConfigDumper::ACTION_PRE_UPDATE) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function preUpdate(array &$extendConfigs)
    {
        $targetEntityConfigs = $this->configManager->getProvider('extend')->getConfigs();
        foreach ($targetEntityConfigs as $targetEntityConfig) {
            if ($this->isExtend($targetEntityConfig)) {
                $indices = $targetEntityConfig->has('index')
                    ? $targetEntityConfig->get('index')
                    : [];
                if ($this->updateIndices($indices, $targetEntityConfig->getId()->getClassName())) {
                    if (empty($indices)) {
                        $targetEntityConfig->remove('index');
                    } else {
                        $targetEntityConfig->set('index', $indices);
                    }
                    $this->configManager->persist($targetEntityConfig);
                }
            }
        }
    }

    /**
     * @param array  $indices
     * @param string $targetEntityClass
     *
     * @return bool
     */
    protected function updateIndices(array &$indices, $targetEntityClass)
    {
        $hasChanges   = false;
        $fieldConfigs = $this->configManager->getProvider('extend')->getConfigs($targetEntityClass);
        foreach ($fieldConfigs as $fieldConfig) {
            if ($this->isExtend($fieldConfig)) {
                $className = $fieldConfig->getId()->getClassName();
                $fieldName = $fieldConfig->getId()->getFieldName();
                if ($this->isIndexRequired($className, $fieldName)) {
                    if (!isset($indices[$fieldName]) || !$indices[$fieldName]) {
                        $indices[$fieldName] = true;
                        $hasChanges          = true;
                    }
                } elseif (isset($indices[$fieldName]) || array_key_exists($fieldName, $indices)) {
                    unset($indices[$fieldName]);
                    $hasChanges = true;
                }
            }
        }

        return $hasChanges;
    }

    /**
     * @param ConfigInterface $extendConfig
     *
     * @return bool
     */
    protected function isExtend($extendConfig)
    {
        return $extendConfig->is('is_extend') || $extendConfig->is('extend');
    }

    /**
     * Determines whether the index for the given field is needed or not
     *
     * @param string $className
     * @param string $fieldName
     *
     * @return bool
     */
    protected function isIndexRequired($className, $fieldName)
    {
        $result = false;

        $datagridConfigProvider = $this->configManager->getProvider('datagrid');
        if ($datagridConfigProvider->hasConfig($className, $fieldName)) {
            $datagridConfig = $datagridConfigProvider->getConfig($className, $fieldName);
            if ($datagridConfig->get('is_visible')) {
                $result = true;
            }
        }

        return $result;
    }
}

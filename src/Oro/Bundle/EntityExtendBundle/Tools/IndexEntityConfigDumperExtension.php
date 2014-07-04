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
            if (!$this->isExtendEntity($targetEntityConfig)) {
                continue;
            }
            $index = $targetEntityConfig->has('index')
                ? $targetEntityConfig->get('index')
                : [];
            $hasChanges = false;
            $fieldConfigs = $this->configManager->getProvider('datagrid')
                ->getConfigs($targetEntityConfig->getId()->getClassName());
            foreach ($fieldConfigs as $fieldConfig) {
                $fieldName = $fieldConfig->getId()->getFieldName();
                $isVisible = $fieldConfig->get('is_visible');
                if (isset($index[$fieldName])) {
                    if ($index[$fieldName] != $isVisible) {
                        if ($isVisible) {
                            $index[$fieldName] = true;
                        } else {
                            unset($index[$fieldName]);
                        }
                        $hasChanges = true;
                    }
                } elseif ($isVisible) {
                    $index[$fieldName] = true;
                    $hasChanges = true;
                }
            }
            if ($hasChanges) {
                $targetEntityConfig->set('index', $index);
                $this->configManager->persist($targetEntityConfig);
            }
        }
    }
    /**
     * @param ConfigInterface $extendEntityConfig
     * @return bool
     */
    protected function isExtendEntity($extendEntityConfig)
    {
        return $extendEntityConfig->is('is_extend') || $extendEntityConfig->is('extend');
    }
}

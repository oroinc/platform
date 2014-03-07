<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

class ExtendConfigProcessor
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @param array $configs
     */
    public function processConfigs(array $configs)
    {
        if (!empty($configs)) {
            foreach ($configs as $className => $entityConfigs) {
                $this->processEntityConfigs($className, $entityConfigs);
            }

            $this->configManager->flush();
        }
    }

    /**
     * @param string $className
     * @param array  $configs
     */
    protected function processEntityConfigs($className, array $configs)
    {
        if ($this->configManager->hasConfig($className)) {
            $this->updateEntityModel(
                $className,
                isset($configs['configs']) ? $configs['configs'] : []
            );
        } else {
            $this->createEntityModel(
                $className,
                isset($configs['mode']) ? $configs['mode'] : ConfigModelManager::MODE_DEFAULT,
                isset($configs['configs']) ? $configs['configs'] : []
            );
        }

        if (isset($configs['fields'])) {
            $isExtendEntity = $this->configManager->getProvider('extend')->getConfig($className)->is('is_extend');
            foreach ($configs['fields'] as $fieldName => $fieldConfigs) {
                $this->processFieldConfigs($className, $fieldName, $fieldConfigs, $isExtendEntity);
            }
        }
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @param array  $configs
     * @param bool   $isExtendEntity
     */
    protected function processFieldConfigs($className, $fieldName, array $configs, $isExtendEntity)
    {
        if ($this->configManager->hasConfig($className, $fieldName)) {
            $this->updateFieldModel(
                $className,
                $fieldName,
                isset($configs['configs']) ? $configs['configs'] : [],
                isset($configs['type']) ? $configs['type'] : null
            );
        } else {
            $this->createFieldModel(
                $className,
                $fieldName,
                $configs['type'],
                isset($configs['mode']) ? $configs['mode'] : ConfigModelManager::MODE_DEFAULT,
                isset($configs['configs']) ? $configs['configs'] : [],
                $isExtendEntity
            );
        }
    }

    /**
     * @param string $className
     * @param string $mode
     * @param array  $configs
     * @throws \LogicException
     */
    protected function createEntityModel($className, $mode, array $configs)
    {
        if (!$this->isCustomEntity($className)) {
            throw new \LogicException(sprintf('Class "%s" is not configurable.', $className));
        }

        $this->configManager->createConfigEntityModel($className, $mode);

        $this->updateConfigs($configs, $className);

        $extendConfigProvider = $this->configManager->getProvider('extend');
        $extendConfig         = $extendConfigProvider->getConfig($className);
        $extendConfig->set('state', ExtendScope::STATE_NEW);
        $this->configManager->persist($extendConfig);
    }

    /**
     * @param string $className
     * @param array  $configs
     */
    protected function updateEntityModel($className, array $configs)
    {
        $haveChanges = $this->updateConfigs($configs, $className);

        if ($haveChanges) {
            $extendConfigProvider = $this->configManager->getProvider('extend');
            $extendConfig         = $extendConfigProvider->getConfig($className);
            if (!$extendConfig->is('state', ExtendScope::STATE_ACTIVE)) {
                $extendConfig->set('state', ExtendScope::STATE_UPDATED);
            }
            $this->configManager->persist($extendConfig);
        }
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @param string $fieldType
     * @param string $mode
     * @param array  $configs
     * @param bool   $isExtendEntity
     * @throws \LogicException
     */
    protected function createFieldModel($className, $fieldName, $fieldType, $mode, array $configs, $isExtendEntity)
    {
        if (!$isExtendEntity && isset($configs['extend']['extend']) && $configs['extend']['extend']) {
            throw new \LogicException(
                sprintf('An extend field "%s" cannot be added to non extend entity "%s".', $fieldName, $className)
            );
        }

        $this->configManager->createConfigFieldModel($className, $fieldName, $fieldType, $mode);

        $this->updateConfigs($configs, $className, $fieldName);

        $extendConfigProvider = $this->configManager->getProvider('extend');
        $extendConfig         = $extendConfigProvider->getConfig($className, $fieldName);
        $extendConfig->set('state', ExtendScope::STATE_NEW);
        $this->configManager->persist($extendConfig);
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @param array  $configs
     * @param string $fieldType
     * @throws \InvalidArgumentException
     */
    protected function updateFieldModel($className, $fieldName, array $configs, $fieldType = null)
    {
        $haveChanges = $this->updateConfigs($configs, $className, $fieldName);

        if ($haveChanges) {
            $extendConfigProvider = $this->configManager->getProvider('extend');
            $extendConfig         = $extendConfigProvider->getConfig($className, $fieldName);
            if (!$extendConfig->is('state', ExtendScope::STATE_ACTIVE)) {
                $extendConfig->set('state', ExtendScope::STATE_UPDATED);
            }
            $this->configManager->persist($extendConfig);
        }

        if ($fieldType) {
            $this->configManager->changeFieldType($className, $fieldName, $fieldType);
        }
    }

    /**
     * @param array       $configs
     * @param string      $className
     * @param string|null $fieldName
     * @return bool TRUE is any changes were made
     */
    protected function updateConfigs(array $configs, $className, $fieldName = null)
    {
        $result = false;

        foreach ($configs as $scope => $values) {
            $config      = $this->configManager->getProvider($scope)->getConfig($className, $fieldName);
            $haveChanges = false;
            foreach ($values as $key => $value) {
                $config->set($key, $value);
                $haveChanges = true;
            }
            if ($haveChanges) {
                $this->configManager->persist($config);
                $result = true;
            }
        }

        return $result;
    }

    /**
     * Checks if an entity is a custom one
     * The custom entity is an entity which has no PHP class in any bundle. The definition of such entity is
     * created automatically in Symfony cache
     *
     * @param string $className
     * @return bool
     */
    protected function isCustomEntity($className)
    {
        return strpos($className, ExtendConfigDumper::ENTITY) === 0;
    }
}

<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Psr\Log\LoggerInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ExtendConfigProcessor
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @param array                $configs
     * @param LoggerInterface|null $logger
     * @param bool                 $dryRun
     * @throws \Exception
     */
    public function processConfigs(array $configs, LoggerInterface $logger = null, $dryRun = false)
    {
        $this->logger = $logger;
        try {
            if (!empty($configs)) {
                $this->filterConfigs($configs);
                if (!empty($configs)) {
                    foreach ($configs as $className => $entityConfigs) {
                        $this->processEntityConfigs($className, $entityConfigs);
                    }

                    if ($dryRun) {
                        $this->configManager->clear();
                    } else {
                        $this->configManager->flush();
                    }
                }
            }
        } catch (\Exception $ex) {
            $this->logger = null;
            throw $ex;
        }
    }

    /**
     * Removes some configs.
     *  - removes configs for non configurable entities if requested a change of field type only
     *
     * @param array $configs
     */
    protected function filterConfigs(array &$configs)
    {
        // removes configs for non configurable entities if requested a change of field type only
        foreach ($configs as $className => $entityConfigs) {
            if (!ExtendHelper::isCustomEntity($className)) {
                if (!$this->configManager->hasConfig($className)) {
                    $fieldsCanBeRemoved = false;
                    if (isset($entityConfigs['fields'])) {
                        $fieldsCanBeRemoved = true;
                        foreach ($entityConfigs['fields'] as $fieldConfigs) {
                            if (!(isset($fieldConfigs['type']) && count($fieldConfigs) === 1)) {
                                $fieldsCanBeRemoved = false;
                                break;
                            }
                        }
                        if ($fieldsCanBeRemoved) {
                            unset($configs[$className]['fields']);
                        }
                    }
                    if ($fieldsCanBeRemoved && empty($configs[$className])) {
                        unset($configs[$className]);
                    }
                }
            }
        }
    }

    /**
     * @param string $className
     * @param array  $configs
     */
    protected function processEntityConfigs($className, array $configs)
    {
        if ($this->configManager->hasConfig($className)) {
            if (isset($configs['configs'])) {
                $this->updateEntityModel($className, $configs['configs']);
            }
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
            if (isset($configs['configs'])) {
                $this->updateFieldModel($className, $fieldName, $configs['configs']);
            }
            if (isset($configs['type'])) {
                $this->changeFieldType($className, $fieldName, $configs['type']);
            }
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
        if (!ExtendHelper::isCustomEntity($className)) {
            throw new \LogicException(sprintf('Class "%s" is not configurable.', $className));
        }

        if ($this->logger) {
            $this->logger->notice(
                sprintf('Create entity "%s".', $className),
                ['configs' => $configs]
            );
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
     * @throws \LogicException
     */
    protected function updateEntityModel($className, array $configs)
    {
        if ($this->logger) {
            $this->logger->notice(
                sprintf('Update entity "%s".', $className),
                ['configs' => $configs]
            );
        }

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

        if ($this->logger) {
            $this->logger->notice(
                sprintf(
                    'Create field "%s". Type: %s. Mode: %s. Entity: %s.',
                    $fieldName,
                    $fieldType,
                    $mode,
                    $className
                ),
                ['configs' => $configs]
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
     */
    protected function updateFieldModel($className, $fieldName, array $configs)
    {
        if ($this->logger) {
            $this->logger->notice(
                sprintf('Update field "%s". Entity: %s.', $fieldName, $className),
                ['configs' => $configs]
            );
        }

        $haveChanges = $this->updateConfigs($configs, $className, $fieldName);

        if ($haveChanges) {
            $extendConfigProvider = $this->configManager->getProvider('extend');
            $extendConfig         = $extendConfigProvider->getConfig($className, $fieldName);
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
     */
    protected function changeFieldType($className, $fieldName, $fieldType)
    {
        if ($this->configManager->getConfigFieldModel($className, $fieldName)->getType() !== $fieldType) {
            if ($this->logger) {
                $this->logger->notice(
                    sprintf('Update a type of field "%s" to "%s". Entity: %s.', $fieldName, $fieldType, $className)
                );
            }
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
}

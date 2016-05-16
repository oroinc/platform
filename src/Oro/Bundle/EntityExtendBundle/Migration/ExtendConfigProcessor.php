<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ExtendConfigProcessor
{
    const APPEND_CONFIGS = '_append_configs';
    const RENAME_CONFIGS = '_rename_configs';

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var array
     */
    protected $appendConfigs;

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
     * @param bool                 $dryRun Log modifications without apply them
     * @throws \Exception
     */
    public function processConfigs(array $configs, LoggerInterface $logger = null, $dryRun = false)
    {
        $this->logger = $logger ? : new NullLogger();
        if ($configs) {
            try {
                $this->appendConfigs = $this->getAndRemoveElement($configs, self::APPEND_CONFIGS, []);

                $renameConfigs = $this->getAndRemoveElement($configs, self::RENAME_CONFIGS, []);

                $this->filterConfigs($configs);

                $hasChanges = false;
                if ($configs) {
                    foreach ($configs as $className => $entityConfigs) {
                        $this->processEntityConfigs($className, $entityConfigs);
                    }
                    $hasChanges = true;
                }
                if ($this->renameFields($renameConfigs)) {
                    $hasChanges = true;
                }

                if ($hasChanges) {
                    if ($dryRun) {
                        $this->configManager->clear();
                    } else {
                        $this->configManager->flush();
                        $this->configManager->clearCache();
                    }
                }
            } catch (\Exception $ex) {
                $this->logger = null;
                throw $ex;
            }
        }
    }

    /**
     * Removes some configs.
     *  - removes configs for non configurable entities if requested only doctrine related changes,
     *    for example: field type, length, precision or scale
     *
     * @param array $configs
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function filterConfigs(array &$configs)
    {
        // removes configs for non configurable entities if requested only doctrine related changes
        $doctrineFieldAttributes = ['length', 'precision', 'scale'];
        foreach ($configs as $className => $entityConfigs) {
            if (!ExtendHelper::isCustomEntity($className)) {
                if (!$this->configManager->hasConfig($className)) {
                    $fieldsCanBeRemoved = false;
                    if (isset($entityConfigs['fields'])) {
                        $fieldsCanBeRemoved = true;
                        foreach ($entityConfigs['fields'] as $fieldConfigs) {
                            // check doctrine related attributes
                            if (isset($fieldConfigs['configs']['extend'])) {
                                $doctrineAttrModificationCount = 0;
                                foreach ($doctrineFieldAttributes as $attrName) {
                                    if (isset($fieldConfigs['configs']['extend'][$attrName])
                                        || (array_key_exists($attrName, $fieldConfigs['configs']['extend']))
                                    ) {
                                        $doctrineAttrModificationCount++;
                                    }
                                }
                                if ($doctrineAttrModificationCount !== count($fieldConfigs['configs']['extend'])) {
                                    $fieldsCanBeRemoved = false;
                                    break;
                                }
                                // check type attribute
                                if (isset($fieldConfigs['type']) && count($fieldConfigs) !== 2) {
                                    $fieldsCanBeRemoved = false;
                                    break;
                                }
                            } else {
                                // check type attribute
                                if (isset($fieldConfigs['type']) && count($fieldConfigs) !== 1) {
                                    $fieldsCanBeRemoved = false;
                                    break;
                                }
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
        if (isset($configs['configs'])) {
            if ($this->configManager->hasConfig($className)) {
                $this->updateEntityModel($className, $configs['configs']);
                if (isset($configs['mode'])) {
                    $this->changeEntityMode($className, $configs['mode']);
                }
            } else {
                $this->createEntityModel(
                    $className,
                    isset($configs['mode']) ? $configs['mode'] : ConfigModel::MODE_DEFAULT,
                    $configs['configs']
                );
            }
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
            $needUpdateState = false;
            if (isset($configs['configs']) && $this->updateFieldModel($className, $fieldName, $configs['configs'])) {
                $needUpdateState = true;
            }
            if (isset($configs['type']) && $this->changeFieldType($className, $fieldName, $configs['type'])) {
                $needUpdateState = true;
            }
            if (isset($configs['mode'])) {
                $this->changeFieldMode($className, $fieldName, $configs['mode']);
            }
            if ($needUpdateState) {
                $extendConfigProvider = $this->configManager->getProvider('extend');
                $extendConfig         = $extendConfigProvider->getConfig($className, $fieldName);
                if (!$extendConfig->is('state', ExtendScope::STATE_UPDATE)) {
                    $extendConfig->set('state', ExtendScope::STATE_UPDATE);
                    $this->configManager->persist($extendConfig);
                }
            }
        } else {
            $this->createFieldModel(
                $className,
                $fieldName,
                $configs['type'],
                isset($configs['mode']) ? $configs['mode'] : ConfigModel::MODE_DEFAULT,
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
            throw new \LogicException(
                sprintf('A new model can be created for custom entity only. Class: %s.', $className)
            );
        }

        $this->logger->info(
            sprintf('Create entity "%s".', $className),
            ['configs' => $configs]
        );

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
        $this->logger->info(
            sprintf('Update entity "%s".', $className),
            ['configs' => $configs]
        );

        $hasChanges = $this->updateConfigs($configs, $className);

        if ($hasChanges) {
            $extendConfigProvider = $this->configManager->getProvider('extend');
            $extendConfig         = $extendConfigProvider->getConfig($className);
            if (!$extendConfig->is('state', ExtendScope::STATE_UPDATE)) {
                $extendConfig->set('state', ExtendScope::STATE_UPDATE);
                $this->configManager->persist($extendConfig);
            }
        }
    }

    /**
     * @param string $className
     * @param string $mode      Can be the value of one of ConfigModel::MODE_* constants
     */
    protected function changeEntityMode($className, $mode)
    {
        if ($this->configManager->getConfigEntityModel($className)->getMode() !== $mode) {
            $this->logger->info(
                sprintf('Update a mode to "%s". Entity: %s.', $mode, $className)
            );
            $this->configManager->changeEntityMode($className, $mode);
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
        if (!$isExtendEntity && isset($configs['extend']['is_extend']) && $configs['extend']['is_extend']) {
            throw new \LogicException(
                sprintf('An extend field "%s" cannot be added to non extend entity "%s".', $fieldName, $className)
            );
        }

        $this->logger->info(
            sprintf(
                'Create field "%s". Type: %s. Mode: %s. Entity: %s.',
                $fieldName,
                $fieldType,
                $mode,
                $className
            ),
            ['configs' => $configs]
        );

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
     *
     * @return bool TRUE if a config was changed; otherwise, FALSE
     */
    protected function updateFieldModel($className, $fieldName, array $configs)
    {
        $this->logger->info(
            sprintf('Update field "%s". Entity: %s.', $fieldName, $className),
            ['configs' => $configs]
        );

        return $this->updateConfigs($configs, $className, $fieldName);
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @param string $fieldType
     *
     * @return bool TRUE if the type was changed; otherwise, FALSE
     */
    protected function changeFieldType($className, $fieldName, $fieldType)
    {
        if ($this->configManager->getConfigFieldModel($className, $fieldName)->getType() !== $fieldType) {
            $this->logger->info(
                sprintf('Update a type of field "%s" to "%s". Entity: %s.', $fieldName, $fieldType, $className)
            );

            return $this->configManager->changeFieldType($className, $fieldName, $fieldType);
        }

        return false;
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @param string $mode      Can be the value of one of ConfigModel::MODE_* constants
     *
     * @return bool TRUE if the mode was changed; otherwise, FALSE
     */
    protected function changeFieldMode($className, $fieldName, $mode)
    {
        if ($this->configManager->getConfigFieldModel($className, $fieldName)->getMode() !== $mode) {
            $this->logger->info(
                sprintf('Update a mode of field "%s" to "%s". Entity: %s.', $fieldName, $mode, $className)
            );

            return $this->configManager->changeFieldMode($className, $fieldName, $mode);
        }

        return false;
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
            $config     = $this->configManager->getProvider($scope)->getConfig($className, $fieldName);
            $hasChanges = false;
            foreach ($values as $key => $value) {
                $path       = explode('.', $key);
                $pathLength = count($path);
                if ($pathLength > 1) {
                    $code        = array_shift($path);
                    $existingVal = (array)$config->get($code);
                    $current     = &$existingVal;
                    foreach ($path as $name) {
                        if (!array_key_exists($name, $current)) {
                            $current[$name] = [];
                        }
                        $current = &$current[$name];
                    }
                    $current = $this->isAppend($scope, $key, $className, $fieldName)
                        ? array_merge($current, (array)$value)
                        : $value;
                    $config->set($code, $existingVal);
                } elseif ($this->isAppend($scope, $key, $className, $fieldName)) {
                    $config->set($key, array_merge((array)$config->get($key), (array)$value));
                } else {
                    $config->set($key, $value);
                }
                $hasChanges = true;
            }
            if ($hasChanges) {
                $this->configManager->persist($config);
                $result = true;
            }
        }

        return $result;
    }

    /**
     * Renames configurable fields
     *
     * @param array $renameConfigs
     * @return bool TRUE if at least one field was renamed; otherwise, FALSE
     */
    protected function renameFields($renameConfigs)
    {
        $hasChanges = false;
        foreach ($renameConfigs as $className => $renameConfig) {
            foreach ($renameConfig as $fieldName => $newFieldName) {
                if ($this->configManager->hasConfig($className, $fieldName)) {
                    $renamed = $this->renameField($className, $fieldName, $newFieldName);
                    if ($renamed && !$hasChanges) {
                        $hasChanges = true;
                    }
                }
            }
        }

        return $hasChanges;
    }

    /**
     * Renames configurable field
     *
     * @param string $className
     * @param string $fieldName
     * @param string $newFieldName
     * @return bool TRUE if a field was renamed; otherwise, FALSE
     */
    protected function renameField($className, $fieldName, $newFieldName)
    {
        $this->logger->info(
            sprintf('Rename field "%s" to "%s". Entity: %s.', $fieldName, $newFieldName, $className)
        );

        return $this->configManager->changeFieldName($className, $fieldName, $newFieldName);
    }

    /**
     * Gets a value of an element with the given key and then remove the element from array
     *
     * @param array  $arr
     * @param string $key
     * @param mixed  $defaultValue
     * @return mixed
     */
    protected function getAndRemoveElement(array &$arr, $key, $defaultValue = null)
    {
        $value = $defaultValue;
        if (isset($arr[$key])) {
            $value = $arr[$key];
            unset($arr[$key]);
        }

        return $value;
    }

    /**
     * @param string      $scope
     * @param string      $code
     * @param string      $className
     * @param string|null $fieldName
     * @return bool
     */
    protected function isAppend($scope, $code, $className, $fieldName = null)
    {
        if (empty($fieldName)) {
            return
                isset($this->appendConfigs[$className]['configs'][$scope]) &&
                in_array($code, $this->appendConfigs[$className]['configs'][$scope], true);
        } else {
            return
                isset($this->appendConfigs[$className]['fields'][$fieldName]['configs'][$scope]) &&
                in_array($code, $this->appendConfigs[$className]['fields'][$fieldName]['configs'][$scope], true);
        }
    }
}

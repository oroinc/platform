<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

class ExtendSchemaGenerator
{
    /** @var  ConfigManager */
    protected $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @param array $configs
     */
    public function parseConfigs($configs = [])
    {
        if ($configs) {
            foreach ($configs as $className => $options) {
                $this->parseEntity($className, $options);
            }

            $this->configManager->flush();
        }
    }

    /**
     * @param $className
     * @throws \InvalidArgumentException
     */
    protected function checkExtend($className)
    {
        $error = false;
        if (!$this->configManager->hasConfig($className)) {
            $error = true;
        } else {
            $config = $this->configManager->getProvider('extend')->getConfig($className);
            if (!$config->is('is_extend')) {
                $error = true;
            }
        }

        if ($error) {
            throw new \InvalidArgumentException(sprintf('Class "%s" is not extended.', $className));
        }
    }

    /**
     * @param string $className     Entity's class name
     * @param array  $options Entity's options
     * @throws \InvalidArgumentException
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function parseEntity($className, $options)
    {
        if (class_exists($className)) {
            $this->checkExtend($className);
        }

        if (!$this->configManager->hasConfig($className)) {
            $this->createEntityModel(
                $className,
                isset($options['mode']) ? $options['mode'] : ConfigModelManager::MODE_DEFAULT,
                isset($options['configs']) ? $options['configs'] : []
            );
        }

        if (isset($options['fields'])) {
            foreach ($options['fields'] as $fieldName => $fieldOptions) {
                $this->createFieldModel(
                    $className,
                    $fieldName,
                    $fieldOptions['type'],
                    isset($fieldOptions['mode']) ? $fieldOptions['mode'] : ConfigModelManager::MODE_DEFAULT,
                    $fieldOptions
                );
            }
        }
    }

    /**
     * @param string $className
     * @param string $mode
     * @param array  $configOptions
     */
    protected function createEntityModel($className, $mode, array $configOptions)
    {
        $this->configManager->createConfigEntityModel($className, $mode);

        if (class_exists($className)) {
            $doctrineMetadata = $this->configManager->getEntityManager()->getClassMetadata($className);
            foreach ($doctrineMetadata->getFieldNames() as $fieldName) {
                $type = $doctrineMetadata->getTypeOfField($fieldName);
                $this->configManager->createConfigFieldModel($doctrineMetadata->getName(), $fieldName, $type);
            }

            foreach ($doctrineMetadata->getAssociationNames() as $fieldName) {
                $type = $doctrineMetadata->isSingleValuedAssociation($fieldName) ? 'ref-one' : 'ref-many';
                $this->configManager->createConfigFieldModel($doctrineMetadata->getName(), $fieldName, $type);
            }
        }

        foreach ($configOptions as $scope => $values) {
            $config = $this->configManager->getProvider($scope)->getConfig($className);
            foreach ($values as $key => $value) {
                $config->set($key, $value);
            }
        }
    }

    protected function createFieldModel($className, $fieldName, $fieldType, $mode, array $configOptions)
    {
        if ($this->configManager->hasConfig($className, $fieldName)) {
            throw new \InvalidArgumentException(
                sprintf('Field "%s" for Entity "%s" already added', $fieldName, $className)
            );
        }

        $this->configManager->createConfigFieldModel($className, $fieldName, $fieldType, $mode);

        $extendConfigProvider = $this->configManager->getProvider('extend');
        $extendConfig = $extendConfigProvider->getConfig($className, $fieldName);
        $extendConfig->set('state', ExtendScope::STATE_NEW);
        $extendConfig->set('extend', true);
        if (isset($fieldConfig['options'])) {
            foreach ($fieldConfig['options'] as $key => $value) {
                $extendConfig->set($key, $value);
            }
        }
        $this->configManager->persist($extendConfig);

        if (isset($configOptions['configs'])) {
            foreach ($configOptions['configs'] as $scope => $values) {
                $config = $this->configManager->getProvider($scope)->getConfig($className, $fieldName);
                foreach ($values as $key => $value) {
                    $config->set($key, $value);
                }
            }
        }
    }
}

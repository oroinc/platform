<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager;

use Oro\Bundle\EntityExtendBundle\Extend\ExtendManager;

class SchemaGenerator
{
    /** @var  ConfigManager */
    protected $configManager;

    /** @var  ExtendManager */
    protected $extendManager;

    public function __construct(ConfigManager $configManager, ExtendManager $extendManager)
    {
        $this->configManager = $configManager;
        $this->extendManager = $extendManager;
    }

    /**
     * @param array $configs
     */
    public function parseConfigs($configs = [])
    {
        if ($configs) {
            foreach ($configs as $className => $entityOptions) {
                $className = class_exists($className)
                    ? $className
                    : ExtendConfigDumper::ENTITY . $className;
                $this->parseEntity($className, $entityOptions);
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
     * @param array  $entityOptions Entity's options
     * @throws \InvalidArgumentException
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function parseEntity($className, $entityOptions)
    {
        $configProvider = $this->extendManager->getConfigProvider();

        if (class_exists($className)) {
            $this->checkExtend($className);
        }

        if (!$this->configManager->hasConfig($className)) {
            $this->createEntityModel($className, $entityOptions);
            $this->setDefaultConfig($entityOptions, $className);

            $entityConfig = $configProvider->getConfig($className);

            $entityConfig->set(
                'owner',
                isset($entityOptions['owner']) ? $entityOptions['owner'] : ExtendManager::OWNER_SYSTEM
            );

            if (isset($entityOptions['is_extend'])) {
                $entityConfig->set('is_extend', $entityOptions['is_extend']);
            } else {
                $entityConfig->set('is_extend', false);
            }
        }

        if (isset($entityOptions['fields'])) {
            foreach ($entityOptions['fields'] as $fieldName => $fieldConfig) {
                if ($this->configManager->hasConfig($className, $fieldName)) {
                    throw new \InvalidArgumentException(
                        sprintf('Field "%s" for Entity "%s" already added', $fieldName, $className)
                    );
                }

                $mode = ConfigModelManager::MODE_DEFAULT;
                if (isset($fieldConfig['mode'])) {
                    $mode = $fieldConfig['mode'];
                }

                $owner = ExtendManager::OWNER_SYSTEM;
                if (isset($fieldConfig['owner'])) {
                    $owner = $fieldConfig['owner'];
                }

                $isExtend = false;
                if (isset($fieldConfig['is_extend'])) {
                    $isExtend = $fieldConfig['is_extend'];
                }

                $this->extendManager->createField(
                    $className,
                    $fieldName,
                    $fieldConfig,
                    $owner,
                    $mode
                );

                $this->setDefaultConfig($entityOptions, $className, $fieldName);

                $config = $configProvider->getConfig($className, $fieldName);
                $config->set('state', ExtendManager::STATE_NEW);
                $config->set('is_extend', $isExtend);

                $this->configManager->persist($config);
            }
        }
    }

    /**
     * @param $entityName
     * @param $entityConfig
     * @return void
     */
    protected function createEntityModel($entityName, $entityConfig)
    {
        $mode = isset($entityConfig['mode']) ? $entityConfig['mode'] : ConfigModelManager::MODE_DEFAULT;

        $this->configManager->createConfigEntityModel($entityName, $mode);

        if (class_exists($entityName)) {
            $doctrineMetadata = $this->configManager->getEntityManager()->getClassMetadata($entityName);
            foreach ($doctrineMetadata->getFieldNames() as $fieldName) {
                $type = $doctrineMetadata->getTypeOfField($fieldName);
                $this->configManager->createConfigFieldModel($doctrineMetadata->getName(), $fieldName, $type);
            }

            foreach ($doctrineMetadata->getAssociationNames() as $fieldName) {
                $type = $doctrineMetadata->isSingleValuedAssociation($fieldName) ? 'ref-one' : 'ref-many';
                $this->configManager->createConfigFieldModel($doctrineMetadata->getName(), $fieldName, $type);
            }
        }
    }

    /**
     * @param array  $options
     * @param string $entityName
     * @param string $fieldName
     */
    protected function setDefaultConfig($options, $entityName, $fieldName = null)
    {
        if ($fieldName) {
            $config = isset($options['fields'][$fieldName]['configs'])
                ? $options['fields'][$fieldName]['configs']
                : array();
        } else {
            $config = isset($options['configs']) ? $options['configs'] : array();
        }

        foreach ($config as $scope => $values) {
            $config = $this->configManager->getProvider($scope)->getConfig($entityName, $fieldName);

            foreach ($values as $key => $value) {
                $config->set($key, $value);
            }
        }
    }
}

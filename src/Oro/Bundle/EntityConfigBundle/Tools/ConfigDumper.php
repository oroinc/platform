<?php

namespace Oro\Bundle\EntityConfigBundle\Tools;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

class ConfigDumper
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    public function updateConfigs($force = false, \Closure $filter = null)
    {
        /** @var ClassMetadataInfo[] $doctrineAllMetadata */
        $doctrineAllMetadata = $this->configManager->getEntityManager()->getMetadataFactory()->getAllMetadata();

        foreach ($doctrineAllMetadata as $doctrineMetadata) {
            if (null === $filter || !$filter($doctrineMetadata)) {
                $className = $doctrineMetadata->getName();
                if ($this->isConfigurableEntity($className)) {
                    if ($this->configManager->hasConfig($className)) {
                        $this->configManager->updateConfigEntityModel($className, $force);
                    } else {
                        $this->configManager->createConfigEntityModel($className);
                    }

                    foreach ($doctrineMetadata->getFieldNames() as $fieldName) {
                        $fieldType = $doctrineMetadata->getTypeOfField($fieldName);

                        /**
                         * TODO:
                         * fix duplicates
                         * wrong $fieldName coming !!!
                         * for example: field_extend_ownership instead of extend_ownership
                         */
                        if ($this->configManager->hasConfig($className, $fieldName)) {
                            $this->configManager->updateConfigFieldModel($className, $fieldName, $force);
                        } else {
                            $this->configManager->createConfigFieldModel($className, $fieldName, $fieldType);
                        }
                    }

                    foreach ($doctrineMetadata->getAssociationNames() as $fieldName) {
                        $fieldType = $doctrineMetadata->isSingleValuedAssociation($fieldName) ? 'ref-one' : 'ref-many';
                        if ($this->configManager->hasConfig($className, $fieldName)) {
                            $this->configManager->updateConfigFieldModel($className, $fieldName, $force);
                        } else {
                            $this->configManager->createConfigFieldModel($className, $fieldName, $fieldType);
                        }
                    }
                }
            }
        }

        $this->configManager->clearConfigurableCache();

        $this->configManager->flush();
    }

    /**
     * @param string $className
     * @return bool
     */
    protected function isConfigurableEntity($className)
    {
        $classMetadata = $this->configManager->getEntityMetadata($className);
        if ($classMetadata) {
            // check if an entity is marked as configurable
            return $classMetadata->name === $className && $classMetadata->configurable;
        } else {
            // check if it is a custom entity
            return $this->configManager->hasConfig($className);
        }
    }
}

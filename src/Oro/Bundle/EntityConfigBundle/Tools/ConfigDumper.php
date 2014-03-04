<?php

namespace Oro\Bundle\EntityConfigBundle\Tools;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Common\Util\Inflector;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

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
                         * possible field duplicates fix
                         */
                        if (strpos($fieldName, ExtendConfigDumper::FIELD_PREFIX) === 0) {
                            $realClassName = $doctrineMetadata->getName();
                            $realClass = ClassUtils::getClass(new $realClassName);
                            if (! method_exists($realClass, Inflector::camelize('get_' . $fieldName))
                                && ! method_exists($realClass, Inflector::camelize('set_' . $fieldName))
                            ) {
                                $fieldName = str_replace(ExtendConfigDumper::FIELD_PREFIX, '', $fieldName);
                            }
                        }

                        /**
                         * relation's default fields
                         */
                        if (strpos($fieldName, ExtendConfigDumper::DEFAULT_PREFIX) === 0) {

                        }


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

        $this->configManager->flush();

        $this->configManager->clearConfigurableCache();
        $this->configManager->clearCacheAll();
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

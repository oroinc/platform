<?php

namespace Oro\Bundle\EntityConfigBundle\Tools;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Common\Util\Inflector;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

class ConfigDumper
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var LoggerInterface
     */
    protected $logger = null;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
        $this->logger        = new NullLogger();
    }

    /**
     * Sets a logger
     *
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function updateConfigs($force = false, \Closure $filter = null)
    {
        /** @var ClassMetadataInfo[] $doctrineAllMetadata */
        $doctrineAllMetadata = $this->configManager->getEntityManager()->getMetadataFactory()->getAllMetadata();

        if (null !== $filter) {
            $doctrineAllMetadata = $filter($doctrineAllMetadata);
        }

        foreach ($doctrineAllMetadata as $doctrineMetadata) {
            $className = $doctrineMetadata->getName();
            if ($this->isConfigurableEntity($className)) {
                if ($this->configManager->hasConfig($className)) {
                    $this->logger->info(
                        sprintf('Update config for <info>"%s"</info> entity.', $className)
                    );
                    $this->configManager->updateConfigEntityModel($className, $force);
                } else {
                    $this->logger->info(
                        sprintf('Create config for <info>"%s"</info> entity.', $className)
                    );
                    $this->configManager->createConfigEntityModel($className);
                }

                $fieldNames = $doctrineMetadata->getFieldNames();
                foreach ($fieldNames as $fieldName) {
                    $fieldType = $doctrineMetadata->getTypeOfField($fieldName);
                    $fieldName = $this->checkField($doctrineMetadata->getName(), $fieldName);
                    if ($fieldName) {
                        if ($this->configManager->hasConfig($className, $fieldName)) {
                            $this->logger->debug(
                                sprintf('  Update config for <comment>"%s"</comment> field.', $fieldName)
                            );
                            $this->configManager->updateConfigFieldModel($className, $fieldName, $force);
                        } else {
                            $this->logger->debug(
                                sprintf('  Create config for <comment>"%s"</comment> field.', $fieldName)
                            );
                            $this->configManager->createConfigFieldModel($className, $fieldName, $fieldType);
                        }
                    }
                }

                $associationNames = $doctrineMetadata->getAssociationNames();
                foreach ($associationNames as $fieldName) {
                    $fieldType = 'ref-many';
                    if ($doctrineMetadata->isSingleValuedAssociation($fieldName)) {
                        $fieldType = 'ref-one';
                    }

                    $fieldName = $this->checkField($doctrineMetadata->getName(), $fieldName);
                    if ($fieldName) {
                        if ($this->configManager->hasConfig($className, $fieldName)) {
                            $this->logger->debug(
                                sprintf('  Update config for <comment>"%s"</comment> field.', $fieldName)
                            );
                            $this->configManager->updateConfigFieldModel($className, $fieldName, $force);
                        } else {
                            $this->logger->debug(
                                sprintf('  Create config for <comment>"%s"</comment> field.', $fieldName)
                            );
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
     * @param string $fieldName
     * @return bool|mixed|string
     */
    protected function checkField($className, $fieldName)
    {
        $realClass = ClassUtils::newReflectionClass($className);
        /**
         * possible field duplicates fix
         */
        if (strpos($fieldName, ExtendConfigDumper::FIELD_PREFIX) === 0
            && !$realClass->hasMethod(Inflector::camelize('set_' . $fieldName))
        ) {
            return str_replace(ExtendConfigDumper::FIELD_PREFIX, '', $fieldName);
        }

        /**
         * relation's default fields
         */
        if (strpos($fieldName, ExtendConfigDumper::DEFAULT_PREFIX) === 0
            && !$realClass->hasMethod(Inflector::camelize('set_' . $fieldName))
        ) {
            return false;
        }

        return $fieldName;
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

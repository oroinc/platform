<?php

namespace Oro\Bundle\EntityConfigBundle\Tools;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

class ConfigLoader
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
     * Load entity configs from annotations to a database
     *
     * @param bool                 $force  Force overwrite config's option values
     * @param callable|null        $filter function (ClassMetadataInfo[] $doctrineAllMetadata)
     * @param LoggerInterface|null $logger
     * @param bool                 $dryRun Log modifications without apply them
     * @throws \Exception
     */
    public function load(
        $force = false,
        \Closure $filter = null,
        LoggerInterface $logger = null,
        $dryRun = false
    ) {
        $this->logger = $logger ? : new NullLogger();
        try {
            /** @var ClassMetadataInfo[] $doctrineAllMetadata */
            $doctrineAllMetadata = $this->configManager
                ->getEntityManager()
                ->getMetadataFactory()
                ->getAllMetadata();
            if (null !== $filter) {
                $doctrineAllMetadata = $filter($doctrineAllMetadata);
            }

            foreach ($doctrineAllMetadata as $metadata) {
                $this->loadEntityConfigs($metadata, $force);
            }

            if ($dryRun) {
                $this->configManager->clear();
            } else {
                $this->configManager->flush();
                $this->configManager->clearCache();
                $this->configManager->clearConfigurableCache();
            }
        } catch (\Exception $ex) {
            $this->logger = null;
            throw $ex;
        }
    }

    /**
     * @param ClassMetadataInfo $metadata
     * @param bool              $force
     */
    protected function loadEntityConfigs(ClassMetadataInfo $metadata, $force)
    {
        if ($this->hasEntityConfigs($metadata)) {
            $className = $metadata->getName();
            if ($this->configManager->hasConfig($className)) {
                $this->logger->notice(
                    sprintf('Update config for "%s" entity.', $className)
                );
                $this->configManager->updateConfigEntityModel($className, $force);
            } else {
                $this->logger->notice(
                    sprintf('Create config for "%s" entity.', $className)
                );
                $this->configManager->createConfigEntityModel($className);
            }

            $fieldNames = $metadata->getFieldNames();
            foreach ($fieldNames as $fieldName) {
                if ($this->hasFieldConfigs($metadata, $fieldName)) {
                    $fieldType = $metadata->getTypeOfField($fieldName);
                    $this->loadFieldConfigs($className, $fieldName, $fieldType, $force);
                }
            }

            $associationNames = $metadata->getAssociationNames();
            foreach ($associationNames as $associationName) {
                if ($this->hasAssociationConfigs($metadata, $associationName)) {
                    $associationType = $metadata->isSingleValuedAssociation($associationName)
                        ? 'ref-one'
                        : 'ref-many';
                    $this->loadFieldConfigs($className, $associationName, $associationType, $force);
                }
            }
        }
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @param string $fieldType
     * @param bool   $force
     */
    protected function loadFieldConfigs($className, $fieldName, $fieldType, $force)
    {
        if ($this->configManager->hasConfig($className, $fieldName)) {
            $this->logger->info(
                sprintf('Update config for "%s" field.', $fieldName)
            );
            $this->configManager->updateConfigFieldModel($className, $fieldName, $force);
        } else {
            $this->logger->info(
                sprintf('Create config for "%s" field.', $fieldName)
            );
            $this->configManager->createConfigFieldModel($className, $fieldName, $fieldType);
        }
    }

    /**
     * @param ClassMetadataInfo $metadata
     * @return bool
     */
    protected function hasEntityConfigs(ClassMetadataInfo $metadata)
    {
        $classMetadata = $this->configManager->getEntityMetadata($metadata->getName());

        return $classMetadata && $classMetadata->configurable;
    }

    /**
     * @param ClassMetadataInfo $metadata
     * @param string $fieldName
     * @return bool
     */
    protected function hasFieldConfigs(ClassMetadataInfo $metadata, $fieldName)
    {
        return true;
    }

    /**
     * @param ClassMetadataInfo $metadata
     * @param string $associationName
     * @return bool
     */
    protected function hasAssociationConfigs(ClassMetadataInfo $metadata, $associationName)
    {
        return true;
    }
}

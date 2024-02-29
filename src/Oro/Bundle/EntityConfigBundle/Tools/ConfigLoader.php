<?php

namespace Oro\Bundle\EntityConfigBundle\Tools;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\EntityManagerBag;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * The loader to load entity configs from annotations to a database.
 */
class ConfigLoader
{
    protected ConfigManager $configManager;
    protected EntityManagerBag $entityManagerBag;
    protected ?LoggerInterface $logger = null;

    public function __construct(ConfigManager $configManager, EntityManagerBag $entityManagerBag)
    {
        $this->configManager = $configManager;
        $this->entityManagerBag = $entityManagerBag;
    }

    /**
     * Loads entity configs from annotations to a database.
     *
     * @param bool                 $force  Force overwrite config's option values
     * @param \Closure|null        $filter function (ClassMetadata[] $doctrineAllMetadata)
     * @param LoggerInterface|null $logger A logger instance
     * @param bool                 $dryRun Log modifications without apply them
     */
    public function load(
        bool $force = false,
        \Closure $filter = null,
        LoggerInterface $logger = null,
        bool $dryRun = false
    ): void {
        $this->logger = $logger ?? new NullLogger();
        try {
            $entityManagers = $this->entityManagerBag->getEntityManagers();
            foreach ($entityManagers as $em) {
                /** @var ClassMetadata[] $doctrineAllMetadata */
                $doctrineAllMetadata = $em->getMetadataFactory()->getAllMetadata();
                if (null !== $filter) {
                    $doctrineAllMetadata = $filter($doctrineAllMetadata);
                }

                foreach ($doctrineAllMetadata as $metadata) {
                    $this->loadEntityConfigs($metadata, $force);
                }
            }
            if ($dryRun) {
                $this->configManager->clear();
            } else {
                $this->configManager->flush();
                $this->configManager->flushAllCaches();
            }
        } catch (\Exception $ex) {
            $this->logger = null;
            throw $ex;
        }
    }

    protected function loadEntityConfigs(ClassMetadata $metadata, bool $force): void
    {
        if ($this->hasEntityConfigs($metadata)) {
            $className = $metadata->getName();
            if ($this->configManager->hasConfig($className)) {
                $this->logger->info(sprintf('Update config for "%s" entity.', $className));
                $this->configManager->updateConfigEntityModel($className, $force);
            } else {
                $this->logger->info(sprintf('Create config for "%s" entity.', $className));
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

    protected function loadFieldConfigs(string $className, string $fieldName, string $fieldType, bool $force): void
    {
        if ($this->configManager->hasConfig($className, $fieldName)) {
            $this->logger->info(sprintf('Update config for "%s" field.', $fieldName));
            $this->configManager->updateConfigFieldModel($className, $fieldName, $force);
        } else {
            $this->logger->info(sprintf('Create config for "%s" field.', $fieldName));
            $this->configManager->createConfigFieldModel($className, $fieldName, $fieldType);
        }
    }

    protected function hasEntityConfigs(ClassMetadata $metadata): bool
    {
        return null !== $this->configManager->getEntityMetadata($metadata->getName());
    }

    protected function hasFieldConfigs(ClassMetadata $metadata, string $fieldName): bool
    {
        return true;
    }

    protected function hasAssociationConfigs(ClassMetadata $metadata, string $associationName): bool
    {
        return true;
    }
}

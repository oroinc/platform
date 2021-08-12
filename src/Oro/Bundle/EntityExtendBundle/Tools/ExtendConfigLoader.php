<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\EntityManagerBag;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigLoader;

/**
 * Adds extended entity specifics to the loader that loads entity configs from annotations to a database.
 */
class ExtendConfigLoader extends ConfigLoader
{
    private int $snapshotSuffixOffset;

    public function __construct(ConfigManager $configManager, EntityManagerBag $entityManagerBag)
    {
        parent::__construct($configManager, $entityManagerBag);
        $this->snapshotSuffixOffset = -\strlen(ExtendHelper::ENUM_SNAPSHOT_SUFFIX);
    }

    /**
     * {@inheritdoc}
     */
    protected function hasEntityConfigs(ClassMetadata $metadata): bool
    {
        return parent::hasEntityConfigs($metadata) && !ExtendHelper::isCustomEntity($metadata->getName());
    }

    /**
     * {@inheritdoc}
     */
    protected function hasFieldConfigs(ClassMetadata $metadata, string $fieldName): bool
    {
        if ($this->isExtendField($metadata->name, $fieldName)) {
            return false;
        }

        // check for "snapshot" field of multi-enum type
        if (substr($fieldName, $this->snapshotSuffixOffset) === ExtendHelper::ENUM_SNAPSHOT_SUFFIX) {
            $guessedName = substr($fieldName, 0, $this->snapshotSuffixOffset);
            if (!empty($guessedName) && $this->isMultiEnumField($metadata->name, $guessedName)) {
                return false;
            }
        }

        return parent::hasFieldConfigs($metadata, $fieldName);
    }

    /**
     * {@inheritdoc}
     */
    protected function hasAssociationConfigs(ClassMetadata $metadata, string $associationName): bool
    {
        if ($this->isExtendField($metadata->name, $associationName)) {
            return false;
        }

        // check for default field of oneToMany or manyToMany relation
        if (str_starts_with($associationName, ExtendConfigDumper::DEFAULT_PREFIX)) {
            $guessedName = substr($associationName, \strlen(ExtendConfigDumper::DEFAULT_PREFIX));
            if (!empty($guessedName) && $this->isExtendField($metadata->name, $guessedName)) {
                return false;
            }
        }
        // check for inverse side field of oneToMany relation
        $targetClass = $metadata->getAssociationTargetClass($associationName);
        $prefix = strtolower(ExtendHelper::getShortClassName($targetClass)) . '_';
        if (str_starts_with($associationName, $prefix)) {
            $guessedName = substr($associationName, \strlen($prefix));
            if (!empty($guessedName) && $this->isExtendField($targetClass, $guessedName)) {
                return false;
            }
        }

        return parent::hasAssociationConfigs($metadata, $associationName);
    }

    protected function isExtendField(string $className, string $fieldName): bool
    {
        if ($this->configManager->hasConfig($className, $fieldName)) {
            return $this->configManager
                ->getProvider('extend')
                ->getConfig($className, $fieldName)
                ->is('is_extend');
        }

        return false;
    }

    protected function isMultiEnumField(string $className, string $fieldName): bool
    {
        if ($this->configManager->hasConfig($className, $fieldName)) {
            /** @var FieldConfigId $fieldId */
            $fieldId = $this->configManager->getId('extend', $className, $fieldName);
            if ($fieldId->getFieldType() === 'multiEnum') {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function loadEntityConfigs(ClassMetadata $metadata, bool $force): void
    {
        parent::loadEntityConfigs($metadata, $force);

        $className = $metadata->getName();
        if ($this->hasEntityConfigs($metadata) && $this->configManager->hasConfig($className)) {
            $entityConfig = $this->configManager->getEntityConfig('extend', $className);
            $entityConfig->set('pk_columns', $metadata->getIdentifierColumnNames());
            $this->configManager->persist($entityConfig);
        }
    }
}

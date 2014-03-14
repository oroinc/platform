<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigLoader;

class ExtendConfigLoader extends ConfigLoader
{
    /**
     * {@inheritdoc}
     */
    protected function hasEntityConfigs(ClassMetadataInfo $metadata)
    {
        return parent::hasEntityConfigs($metadata) && !ExtendHelper::isCustomEntity($metadata->getName());
    }

    /**
     * {@inheritdoc}
     */
    protected function hasFieldConfigs(ClassMetadataInfo $metadata, $fieldName)
    {
        if ($this->isExtendField($metadata->getName(), $fieldName)) {
            return false;
        }

        return parent::hasFieldConfigs($metadata, $fieldName);
    }

    /**
     * {@inheritdoc}
     */
    protected function hasAssociationConfigs(ClassMetadataInfo $metadata, $associationName)
    {
        if ($this->isExtendField($metadata->getName(), $associationName)) {
            return false;
        }

        // check for default field of oneToMany or manyToMany relation
        if (strpos($associationName, ExtendConfigDumper::DEFAULT_PREFIX) === 0) {
            $guessedName = substr($associationName, strlen(ExtendConfigDumper::DEFAULT_PREFIX));
            if (!empty($guessedName) && $this->isExtendField($metadata->getName(), $guessedName)) {
                return false;
            }
        }
        // check for inverse side field of oneToMany relation
        $targetClass = $metadata->getAssociationTargetClass($associationName);
        $prefix = strtolower(ExtendHelper::getShortClassName($targetClass)) . '_';
        if (strpos($associationName, $prefix) === 0) {
            $guessedName = substr($associationName, strlen($prefix));
            if (!empty($guessedName) && $this->isExtendField($targetClass, $guessedName)) {
                return false;
            }
        }

        return parent::hasAssociationConfigs($metadata, $associationName);
    }

    /**
     * Determines whether a field is extend or not
     *
     * @param string $className
     * @param string $fieldName
     * @return bool
     */
    protected function isExtendField($className, $fieldName)
    {
        if ($this->configManager->hasConfig($className, $fieldName)) {
            return $this->configManager
                ->getProvider('extend')
                ->getConfig($className, $fieldName)
                ->is('extend');
        }

        return false;
    }
}

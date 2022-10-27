<?php

namespace Oro\Bundle\EntityConfigBundle\Attribute;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\Provider\AbstractExclusionProvider;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * The implementation of the exclusion provider that can be used to ignore:
 * * owning side association of "one-to-many" attribute
 * * target side association of "many-to-one" and "many-to-many" attributes
 */
class AttributeExclusionProvider extends AbstractExclusionProvider
{
    /** @var ConfigManager */
    private $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredRelation(ClassMetadata $metadata, $associationName)
    {
        if (!$metadata->hasAssociation($associationName)) {
            return false;
        }

        $associationMapping = $metadata->getAssociationMapping($associationName);

        return
            $this->isOwningSideOfOneToManyAttribute($metadata->name, $associationMapping)
            || $this->isTargetSideOfManyToOneAttribute($metadata->name, $associationMapping)
            || $this->isTargetSideOfManyToManyAttribute($metadata->name, $associationMapping);
    }

    private function isOwningSideOfOneToManyAttribute(string $entityClass, array $associationMapping): bool
    {
        if (!$associationMapping['isOwningSide']
            || ClassMetadata::MANY_TO_ONE !== $associationMapping['type']
            || empty($associationMapping['inversedBy'])
            || !$this->configManager->hasConfig($entityClass)
        ) {
            return false;
        }

        $targetEntityClass = $associationMapping['targetEntity'];
        $targetFieldName = $associationMapping['inversedBy'];

        $relationKey = ExtendHelper::buildRelationKey(
            $targetEntityClass,
            $targetFieldName,
            RelationType::ONE_TO_MANY,
            $entityClass
        );

        return
            $this->hasRelation($entityClass, $relationKey)
            && $this->isAttribute($targetEntityClass, $targetFieldName);
    }

    private function isTargetSideOfManyToOneAttribute(string $entityClass, array $associationMapping): bool
    {
        if ($associationMapping['isOwningSide']
            || ClassMetadata::ONE_TO_MANY !== $associationMapping['type']
            || empty($associationMapping['mappedBy'])
            || !$this->configManager->hasConfig($entityClass)
        ) {
            return false;
        }

        $targetEntityClass = $associationMapping['targetEntity'];
        $targetFieldName = $associationMapping['mappedBy'];

        $relationKey = ExtendHelper::buildRelationKey(
            $targetEntityClass,
            $targetFieldName,
            RelationType::MANY_TO_ONE,
            $entityClass
        );

        return
            $this->hasRelation($entityClass, $relationKey)
            && $this->isAttribute($targetEntityClass, $targetFieldName);
    }

    private function isTargetSideOfManyToManyAttribute(string $entityClass, array $associationMapping): bool
    {
        if ($associationMapping['isOwningSide']
            || ClassMetadata::MANY_TO_MANY !== $associationMapping['type']
            || empty($associationMapping['mappedBy'])
            || !$this->configManager->hasConfig($entityClass)
        ) {
            return false;
        }

        $targetEntityClass = $associationMapping['targetEntity'];
        $targetFieldName = $associationMapping['mappedBy'];
        $relationKey = ExtendHelper::buildRelationKey(
            $targetEntityClass,
            $targetFieldName,
            RelationType::MANY_TO_MANY,
            $entityClass
        );

        return
            $this->hasRelation($entityClass, $relationKey)
            && $this->isAttribute($targetEntityClass, $targetFieldName);
    }

    private function hasRelation(string $entityClass, string $relationKey): bool
    {
        $relations = $this->configManager->getEntityConfig('extend', $entityClass)->get('relation', false, []);

        return isset($relations[$relationKey]);
    }

    private function isAttribute(string $entityClass, string $fieldName): bool
    {
        return
            $this->configManager->hasConfig($entityClass, $fieldName)
            && $this->configManager->getFieldConfig('attribute', $entityClass, $fieldName)->is('is_attribute');
    }
}

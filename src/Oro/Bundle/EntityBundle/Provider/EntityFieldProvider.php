<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Translation\TranslatorInterface;

use Doctrine\Common\Persistence\Mapping\ClassMetadata as ClassMetadataInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityBundle\Exception\InvalidEntityException;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigHelper;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * TODO: passing parameter $applyExclusions into getFields method should be refactored
 */
class EntityFieldProvider
{
    /** @var EntityProvider */
    protected $entityProvider;

    /** @var VirtualFieldProviderInterface */
    protected $virtualFieldProvider;

    /** @var VirtualRelationProviderInterface */
    protected $virtualRelationProvider;

    /** @var ExclusionProviderInterface */
    protected $exclusionProvider;

    /** @var ConfigProvider */
    protected $entityConfigProvider;

    /** @var ConfigProvider */
    protected $extendConfigProvider;

    /** @var EntityClassResolver */
    protected $entityClassResolver;

    /** @var FieldTypeHelper */
    protected $fieldTypeHelper;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var array */
    protected $hiddenFields;

    /**
     * Constructor
     *
     * @param ConfigProvider      $entityConfigProvider
     * @param ConfigProvider      $extendConfigProvider
     * @param EntityClassResolver $entityClassResolver
     * @param FieldTypeHelper     $fieldTypeHelper
     * @param ManagerRegistry     $doctrine
     * @param TranslatorInterface $translator
     * @param array               $hiddenFields
     */
    public function __construct(
        ConfigProvider $entityConfigProvider,
        ConfigProvider $extendConfigProvider,
        EntityClassResolver $entityClassResolver,
        FieldTypeHelper $fieldTypeHelper,
        ManagerRegistry $doctrine,
        TranslatorInterface $translator,
        $hiddenFields
    ) {
        $this->entityConfigProvider = $entityConfigProvider;
        $this->extendConfigProvider = $extendConfigProvider;
        $this->entityClassResolver  = $entityClassResolver;
        $this->fieldTypeHelper      = $fieldTypeHelper;
        $this->doctrine             = $doctrine;
        $this->translator           = $translator;
        $this->hiddenFields         = $hiddenFields;
    }

    /**
     * Sets entity provider
     *
     * @param EntityProvider $entityProvider
     */
    public function setEntityProvider(EntityProvider $entityProvider)
    {
        $this->entityProvider = $entityProvider;
    }

    /**
     * Sets virtual field provider
     *
     * @param VirtualFieldProviderInterface $virtualFieldProvider
     */
    public function setVirtualFieldProvider(VirtualFieldProviderInterface $virtualFieldProvider)
    {
        $this->virtualFieldProvider = $virtualFieldProvider;
    }

    /**
     * @param VirtualRelationProviderInterface $virtualRelationProvider
     */
    public function setVirtualRelationProvider($virtualRelationProvider)
    {
        $this->virtualRelationProvider = $virtualRelationProvider;
    }

    /**
     * Sets exclusion provider
     *
     * @param ExclusionProviderInterface $exclusionProvider
     */
    public function setExclusionProvider(ExclusionProviderInterface $exclusionProvider)
    {
        $this->exclusionProvider = $exclusionProvider;
    }

    /**
     * Returns relations for the given entity
     *
     * @param string $entityName         Entity name. Can be full class name or short form: Bundle:Entity.
     * @param bool   $applyExclusions    Indicates whether exclusion logic should be applied.
     * @param bool   $withEntityDetails  Indicates whether details of related entity should be returned as well.
     * @param bool   $translate          Flag means that label, plural label should be translated
     *                                   .       'name'          - field name
     *                                   .       'type'          - field type
     *                                   .       'label'         - field label
     *                                   .       'related_entity_name' - entity full class name
     *                                   .       'relation_type'       - relation type
     *                                   If $withEntityDetails = true the following attributes are added:
     *                                   .       'related_entity_label'        - entity label
     *                                   .       'related_entity_plural_label' - entity plural label
     *                                   .       'related_entity_icon'         - an icon associated with an entity
     *
     * @return array of relations
     */
    public function getRelations(
        $entityName,
        $withEntityDetails = false,
        $applyExclusions = true,
        $translate = true
    ) {
        $className = $this->entityClassResolver->getEntityClass($entityName);
        if (!$this->entityConfigProvider->hasConfig($className)) {
            // only configurable entities are supported
            return [];
        }

        $result = [];

        $this->addRelations($result, $className, $withEntityDetails, $applyExclusions, $translate);

        return $result;
    }

    /**
     * Returns fields for the given entity
     *
     * @param string $entityName         Entity name. Can be full class name or short form: Bundle:Entity.
     * @param bool   $withRelations      Indicates whether association fields should be returned as well.
     * @param bool   $withVirtualFields  Indicates whether virtual fields should be returned as well.
     * @param bool   $withEntityDetails  Indicates whether details of related entity should be returned as well.
     * @param bool   $withUnidirectional Indicates whether Unidirectional association fields should be returned.
     * @param bool   $applyExclusions    Indicates whether exclusion logic should be applied.
     * @param bool   $translate          Flag means that label, plural label should be translated
     *
     * @return array of fields sorted by field label (relations follows fields)
     *                                   .       'name'          - field name
     *                                   .       'type'          - field type
     *                                   .       'label'         - field label
     *                                   If a field is an identifier (primary key in terms of a database)
     *                                   .       'identifier'    - true for an identifier field
     *                                   If a field represents a relation and $withRelations = true or
     *                                   a virtual field has 'filter_by_id' = true following attribute is added:
     *                                   .       'related_entity_name' - entity full class name
     *                                   If a field represents a relation and $withRelations = true
     *                                   the following attributes are added:
     *                                   .       'relation_type'       - relation type
     *                                   If a field represents a relation and $withEntityDetails = true
     *                                   the following attributes are added:
     *                                   .       'related_entity_label'        - entity label
     *                                   .       'related_entity_plural_label' - entity plural label
     *                                   .       'related_entity_icon'         - an icon associated with an entity
     */
    public function getFields(
        $entityName,
        $withRelations = false,
        $withVirtualFields = false,
        $withEntityDetails = false,
        $withUnidirectional = false,
        $applyExclusions = true,
        $translate = true
    ) {
        $className = $this->entityClassResolver->getEntityClass($entityName);
        if (!$this->entityConfigProvider->hasConfig($className)) {
            // only configurable entities are supported
            return [];
        }

        $result = [];

        $this->addFields($result, $className, $applyExclusions, $translate);

        if ($withVirtualFields) {
            $this->addVirtualFields($result, $className, $applyExclusions, $translate);
        }

        if ($withRelations) {
            $this->addRelations($result, $className, $withEntityDetails, $applyExclusions, $translate);

            if ($withVirtualFields) {
                $this->addVirtualRelations($result, $className, $withEntityDetails, $applyExclusions, $translate);
            }

            if ($withUnidirectional) {
                $this->addUnidirectionalRelations(
                    $result,
                    $className,
                    $withEntityDetails,
                    $applyExclusions,
                    $translate
                );
            }
        }
        $this->sortFields($result);

        return $result;
    }

    /**
     * Adds entity fields to $result
     *
     * @param array         $result
     * @param string        $className
     * @param bool          $applyExclusions
     * @param bool          $translate
     */
    protected function addFields(array &$result, $className, $applyExclusions, $translate)
    {
        $metadata = $this->getMetadataFor($className);

        // add regular fields
        $configs = $this->extendConfigProvider->getConfigs($className);
        foreach ($configs as $fieldConfig) {
            /** @var FieldConfigId $fieldConfigId */
            $fieldConfigId = $fieldConfig->getId();
            $fieldName = $fieldConfigId->getFieldName();

            $fieldType = $this->fieldTypeHelper->getUnderlyingType(
                $fieldConfigId->getFieldType()
            );
            if ($this->fieldTypeHelper->isRelation($fieldType)) {
                // skip because this field is relation
                continue;
            }

            if (isset($result[$fieldName])) {
                // skip because a field with this name is already added, it could be a virtual field
                continue;
            }

            if (!$this->entityConfigProvider->hasConfig($className, $fieldName)) {
                // skip non configurable field
                continue;
            }

            if ($this->isIgnoredField($metadata, $fieldName)) {
                continue;
            }

            if ($fieldConfig->is('is_deleted')) {
                continue;
            }

            if ($applyExclusions && $this->exclusionProvider->isIgnoredField($metadata, $fieldName)) {
                continue;
            }

            $this->addField(
                $result,
                $fieldName,
                $fieldConfigId->getFieldType(),
                $this->getFieldLabel($metadata, $fieldName),
                $metadata->isIdentifier($fieldName),
                $translate
            );
        }
    }

    /**
     * Adds entity virtual fields to $result
     *
     * @param array  $result
     * @param string $className
     * @param bool   $applyExclusions
     * @param bool   $translate
     */
    protected function addVirtualFields(array &$result, $className, $applyExclusions, $translate)
    {
        if (!$this->virtualFieldProvider) {
            return;
        }

        $metadata = $this->getMetadataFor($className);
        $virtualFields = $this->virtualFieldProvider->getVirtualFields($className);
        foreach ($virtualFields as $fieldName) {
            if ($applyExclusions && $this->exclusionProvider->isIgnoredField($metadata, $fieldName)) {
                continue;
            }

            $query      = $this->virtualFieldProvider->getVirtualFieldQuery($className, $fieldName);
            $fieldLabel = !empty($query['select']['label'])
                ? $query['select']['label']
                : $this->getFieldLabel($metadata, $fieldName);

            $this->addField(
                $result,
                $fieldName,
                $query['select']['return_type'],
                $fieldLabel,
                false,
                $translate
            );
            if (isset($query['select']['related_entity_name']) && $query['select']['related_entity_name']) {
                $result[$fieldName]['related_entity_name'] = $query['select']['related_entity_name'];
            } elseif (isset($query['select']['filter_by_id']) && $query['select']['filter_by_id']) {
                $result[$fieldName]['related_entity_name'] = $metadata->getAssociationTargetClass($fieldName);
            }
        }
    }

    /**
     * Adds entity virtual fields to $result
     *
     * @param array  $result
     * @param string $className
     * @param bool $withEntityDetails
     * @param bool $applyExclusions
     * @param bool $translate
     */
    protected function addVirtualRelations(array &$result, $className, $withEntityDetails, $applyExclusions, $translate)
    {
        if (!$this->virtualRelationProvider) {
            return;
        }

        $metadata = $this->getMetadataFor($className);
        $virtualRelations = $this->virtualRelationProvider->getVirtualRelations($className);
        foreach ($virtualRelations as $associationName => $virtualRelation) {
            if ($applyExclusions && $this->exclusionProvider->isIgnoredField($metadata, $associationName)) {
                continue;
            }

            $fieldType = $virtualRelation['relation_type'];
            $targetClassName = $this->entityClassResolver->getEntityClass($virtualRelation['related_entity_name']);

            $label = !empty($virtualRelation['label'])
                ? $virtualRelation['label']
                : $this->getFieldLabel($metadata, $associationName);

            $this->addRelation(
                $result,
                $associationName,
                $fieldType,
                $label,
                $this->getRelationType($fieldType),
                $targetClassName,
                $withEntityDetails,
                $translate
            );
        }
    }

    /**
     * Checks if the given field should be ignored
     *
     * @param ClassMetadataInterface $metadata
     * @param string                 $fieldName
     *
     * @return bool
     */
    protected function isIgnoredField(ClassMetadataInterface $metadata, $fieldName)
    {
        // @todo: use of $this->hiddenFields is a temporary solution (https://magecore.atlassian.net/browse/BAP-4142)
        if (isset($this->hiddenFields[$metadata->getName()][$fieldName])) {
            return true;
        }

        return false;
    }

    /**
     * Adds a field to $result
     *
     * @param array  $result
     * @param string $name
     * @param string $type
     * @param string $label
     * @param bool   $isIdentifier
     * @param bool   $translate
     */
    protected function addField(array &$result, $name, $type, $label, $isIdentifier, $translate)
    {
        $field = [
            'name'  => $name,
            'type'  => $type,
            'label' => $translate ? $this->translator->trans($label) : $label
        ];
        if ($isIdentifier) {
            $field['identifier'] = true;
        }
        $result[$name] = $field;
    }

    /**
     * Adds entity relations to $result
     *
     * @param array         $result
     * @param string        $className
     * @param bool          $withEntityDetails
     * @param bool          $applyExclusions
     * @param bool          $translate
     */
    protected function addRelations(
        array &$result,
        $className,
        $withEntityDetails,
        $applyExclusions,
        $translate
    ) {
        $metadata = $this->getMetadataFor($className);
        $associationNames = $metadata->getAssociationNames();
        foreach ($associationNames as $associationName) {
            if (isset($result[$associationName])) {
                // skip because a relation with this name is already added, it could be a virtual field
                continue;
            }
            if (!$this->entityConfigProvider->hasConfig($className, $associationName)) {
                // skip non configurable relation
                continue;
            }
            $targetClassName = $metadata->getAssociationTargetClass($associationName);
            if (!$this->entityConfigProvider->hasConfig($targetClassName)) {
                // skip if target entity is not configurable
                continue;
            }
            if ($this->isIgnoredRelation($metadata, $associationName)) {
                continue;
            }
            if ($applyExclusions && $this->exclusionProvider->isIgnoredRelation($metadata, $associationName)) {
                continue;
            }

            $fieldType = $this->getRelationFieldType($className, $associationName);

            $this->addRelation(
                $result,
                $associationName,
                $fieldType,
                $this->getFieldLabel($metadata, $associationName),
                $this->getRelationType($fieldType),
                $targetClassName,
                $withEntityDetails,
                $translate
            );
        }
    }

    /**
     * Adds remote entities relations to $className entity into $result
     *
     * @param array         $result
     * @param string        $className
     * @param bool          $withEntityDetails
     * @param bool          $applyExclusions
     * @param bool          $translate
     */
    protected function addUnidirectionalRelations(
        array &$result,
        $className,
        $withEntityDetails,
        $applyExclusions,
        $translate
    ) {
        $relations = $this->getUnidirectionalRelations($className);
        foreach ($relations as $name => $mapping) {
            $relatedClassName = $mapping['sourceEntity'];
            $fieldName        = $mapping['fieldName'];
            $metadata         = $this->getMetadataFor($relatedClassName);
            $labelType        = ($mapping['type'] & ClassMetadataInfo::TO_ONE) ? 'label' : 'plural_label';

            if (!$this->entityConfigProvider->hasConfig($relatedClassName, $fieldName)) {
                // skip non configurable relation
                continue;
            }
            if ($this->isIgnoredRelation($metadata, $fieldName)) {
                continue;
            }
            if ($applyExclusions && $this->exclusionProvider->isIgnoredRelation($metadata, $fieldName)) {
                continue;
            }

            $labelKey = $this->entityConfigProvider->getConfig($relatedClassName, $fieldName)->get('label');
            $labelTypeKey = $this->entityConfigProvider->getConfig($relatedClassName)->get($labelType);
            if ($translate) {
                $labelKey = $this->translator->trans($labelKey);
                $labelTypeKey = $this->translator->trans($labelTypeKey);
            }
            $label = sprintf('%s (%s)', $labelKey, $labelTypeKey);

            $fieldType = $this->getRelationFieldType($relatedClassName, $fieldName);

            $this->addRelation(
                $result,
                $name,
                $fieldType,
                $label,
                $this->getRelationType($fieldType),
                $relatedClassName,
                $withEntityDetails,
                $translate,
                false
            );
        }
    }

    /**
     * Return mapping data for entities that has one-way link to $className entity
     *
     * @param string $className
     *
     * @return array
     */
    protected function getUnidirectionalRelations($className)
    {
        $relations = [];

        /** @var EntityConfigId[] $entityConfigIds */
        $entityConfigIds = $this->entityConfigProvider->getIds();
        foreach ($entityConfigIds as $entityConfigId) {
            if ($this->isIgnoredEntity($entityConfigId)) {
                continue;
            }

            $metadata = $this->getMetadataFor($entityConfigId->getClassName());
            $targetMappings = $metadata->getAssociationMappings();
            if (empty($targetMappings)) {
                continue;
            }

            foreach ($targetMappings as $mapping) {
                if ($mapping['isOwningSide']
                    && empty($mapping['inversedBy'])
                    && $mapping['targetEntity'] === $className
                ) {
                    $relations[$mapping['sourceEntity'] . '::' . $mapping['fieldName']] = $mapping;
                }
            }
        }

        return $relations;
    }

    /**
     * Check if entity config is new (entity not generated yet) or was deleted
     *
     * @param EntityConfigId $entityConfigId
     *
     * @return bool
     */
    protected function isIgnoredEntity(EntityConfigId $entityConfigId)
    {
        $entityConfig = $this->extendConfigProvider->getConfigById($entityConfigId);

        if ($entityConfig->is('is_deleted') ||
            $entityConfig->in('state', [ExtendScope::STATE_NEW, ExtendScope::STATE_DELETE])
        ) {
            return true;
        }

        return false;
    }

    /**
     * Checks if the given relation should be ignored
     *
     * @param ClassMetadataInterface $metadata
     * @param string                 $associationName
     *
     * @return bool
     */
    protected function isIgnoredRelation(ClassMetadataInterface $metadata, $associationName)
    {
        // skip a relation if it was deleted
        $fieldConfig = $this->extendConfigProvider->getConfig($metadata->getName(), $associationName);
        if ($fieldConfig->is('is_deleted')) {
            return true;
        }

        return false;
    }

    /**
     * Adds a relation to $result
     *
     * @param array     $result
     * @param string    $name
     * @param string    $type
     * @param string    $label
     * @param string    $relationType
     * @param string    $relatedEntityName
     * @param bool      $withEntityDetails
     * @param bool      $translate
     * @param bool|null $translateLabel
     */
    protected function addRelation(
        array &$result,
        $name,
        $type,
        $label,
        $relationType,
        $relatedEntityName,
        $withEntityDetails,
        $translate,
        $translateLabel = null
    ) {
        if ($translateLabel === null) {
            $translateLabel = $translate;
        }
        if ($translateLabel) {
            $label = $this->translator->trans($label);
        }

        $relation = [
            'name'                => $name,
            'type'                => $type,
            'label'               => $label,
            'relation_type'       => $relationType,
            'related_entity_name' => $relatedEntityName
        ];

        if ($withEntityDetails) {
            $this->addEntityDetails($relatedEntityName, $relation, $translate);
        }

        $result[$name] = $relation;
    }

    /**
     * @param string $relatedEntityName
     * @param array  $relation
     * @param bool   $translate
     *
     * @return array
     */
    protected function addEntityDetails($relatedEntityName, array &$relation, $translate)
    {
        $entity = $this->entityProvider->getEntity($relatedEntityName, $translate);
        foreach ($entity as $key => $val) {
            if (!in_array($key, ['name'])) {
                $relation['related_entity_' . $key] = $val;
            }
        }

        return $relation;
    }

    /**
     * Gets doctrine entity manager for the given class
     *
     * @param string $className
     *
     * @return EntityManager
     * @throws InvalidEntityException
     */
    protected function getManagerForClass($className)
    {
        $manager = null;
        try {
            $manager = $this->doctrine->getManagerForClass($className);
        } catch (\ReflectionException $ex) {
            throw new InvalidEntityException(sprintf('The "%s" entity was not found.', $className));
        }

        return $manager;
    }

    /**
     * @param string $className
     *
     * @return ClassMetadataInterface|ClassMetadataInfo|ClassMetadata
     */
    protected function getMetadataFor($className)
    {
        return $this->getManagerForClass($className)->getMetadataFactory()->getMetadataFor($className);
    }

    /**
     * Gets a field label
     *
     * @param ClassMetadata $metadata
     * @param string        $fieldName
     *
     * @return string
     */
    protected function getFieldLabel(ClassMetadata $metadata, $fieldName)
    {
        $className = $metadata->getName();
        if (!$metadata->hasField($fieldName) && !$metadata->hasAssociation($fieldName)) {
            // virtual field or relation
            return ConfigHelper::getTranslationKey('entity', 'label', $className, $fieldName);
        }

        $label = $this->entityConfigProvider->hasConfig($className, $fieldName)
            ? $this->entityConfigProvider->getConfig($className, $fieldName)->get('label')
            : null;

        return !empty($label)
            ? $label
            : ConfigHelper::getTranslationKey('entity', 'label', $className, $fieldName);
    }

    /**
     * Gets a relation type
     *
     * @param string $className
     * @param string $fieldName
     *
     * @return string
     */
    protected function getRelationFieldType($className, $fieldName)
    {
        /** @var FieldConfigId $configId */
        $configId = $this->entityConfigProvider->getConfig($className, $fieldName)->getId();

        return $configId->getFieldType();
    }

    /**
     * Gets a relation type
     *
     * @param string $relationFieldType
     *
     * @return string
     */
    protected function getRelationType($relationFieldType)
    {
        return $this->fieldTypeHelper->getUnderlyingType($relationFieldType);
    }

    /**
     * Sorts fields by its label (relations follows fields)
     *
     * @param array $fields
     */
    protected function sortFields(array &$fields)
    {
        usort(
            $fields,
            function ($a, $b) {
                if (isset($a['relation_type']) !== isset($b['relation_type'])) {
                    if (isset($a['relation_type'])) {
                        return 1;
                    }
                    if (isset($b['relation_type'])) {
                        return -1;
                    }
                }

                return strcasecmp($a['label'], $b['label']);
            }
        );
    }
}

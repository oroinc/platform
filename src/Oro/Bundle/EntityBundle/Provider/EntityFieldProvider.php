<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata as ClassMetadataInterface;
use Oro\Bundle\EntityBundle\Exception\InvalidEntityException;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigHelper;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\EntitySerializedFieldsBundle\Entity\EntitySerializedFieldsHolder;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides detailed information about fields for a specific entity.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class EntityFieldProvider
{
    /**
     * Indicates whether association fields should be returned as well
     */
    public const OPTION_WITH_RELATIONS = 1 << 1;

    /**
     * Indicates whether virtual fields should be returned as well.
     */
    public const OPTION_WITH_VIRTUAL_FIELDS = 1 << 2;

    /**
     * Indicates whether details of related entity should be returned as well.
     */
    public const OPTION_WITH_ENTITY_DETAILS = 1 << 3;

    /**
     * Indicates whether Unidirectional association fields should be returned.
     */
    public const OPTION_WITH_UNIDIRECTIONAL = 1 << 4;

    /**
     * Indicates whether hidden fields should be included.
     */
    public const OPTION_WITH_HIDDEN_FIELDS = 1 << 5;

    /**
     * Indicates whether exclusion logic should be applied.
     */
    public const OPTION_APPLY_EXCLUSIONS = 1 << 6;

    /**
     * Flag means that label, plural label should be translated
     */
    public const OPTION_TRANSLATE = 1 << 7;

    /** @var EntityProvider */
    protected $entityProvider;

    /** @var VirtualFieldProviderInterface */
    protected $virtualFieldProvider;

    /** @var VirtualRelationProviderInterface */
    protected $virtualRelationProvider;

    /** @var VirtualFieldProviderInterface */
    protected $enumVirtualFieldProvider;

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

    /** @var string|null The locale or null to use the default */
    protected $locale;

    /** @var bool */
    private $isCachingEnabled;

    /** @var ClassMetadata[] */
    private $metadata;

    /** @var ClassMetadata[] */
    private $metadataForEntitiesWithAssociations;

    /**
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
     */
    public function setEntityProvider(EntityProvider $entityProvider)
    {
        $this->entityProvider = $entityProvider;
    }

    /**
     * Sets virtual field provider
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

    public function setEnumVirtualFieldProvider(VirtualFieldProviderInterface $enumVirtualFieldProvider)
    {
        $this->enumVirtualFieldProvider = $enumVirtualFieldProvider;
    }

    /**
     * Sets exclusion provider
     */
    public function setExclusionProvider(ExclusionProviderInterface $exclusionProvider)
    {
        $this->exclusionProvider = $exclusionProvider;
    }

    /**
     * Allows this provider to store results of some time-consuming operations in a memory.
     * Enabling the caching can significantly improve performance of this provider
     * if it is used to get fields for a lot of entities.
     *
     * @param bool $enable
     */
    public function enableCaching($enable = true)
    {
        $this->isCachingEnabled = $enable;
        $this->metadata = null;
        $this->metadataForEntitiesWithAssociations = null;
    }

    /**
     * Returns relations for the given entity
     *
     * @param string $entityName         Entity name. Can be full class name or short form: Bundle:Entity.
     * @param bool   $withEntityDetails  Indicates whether details of related entity should be returned as well.
     * @param bool   $applyExclusions    Indicates whether exclusion logic should be applied.
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
        if (!$this->isEntityAccessible($className)) {
            return [];
        }

        $result = [];

        $this->addRelations($result, $className, $withEntityDetails, $applyExclusions, $translate);

        return $result;
    }

    /**
     * Returns fields for the given entity
     *
     * @param string $entityName Entity name. Can be full class name or short form: Bundle:Entity.
     * @param int $options Bit mask of options, see EntityFieldProvider::OPTION_*
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
    public function getEntityFields(
        string $entityName,
        int $options = self::OPTION_APPLY_EXCLUSIONS | self::OPTION_TRANSLATE
    ): array {
        $className = $this->entityClassResolver->getEntityClass($entityName);
        if (!$this->isEntityAccessible($className)) {
            return [];
        }

        $result = [];

        $this->addEntityFields($result, $className, $options);

        $withVirtualFields = ($options & self::OPTION_WITH_VIRTUAL_FIELDS) !== 0;
        $applyExclusions = ($options & self::OPTION_APPLY_EXCLUSIONS) !== 0;
        $translate = ($options & self::OPTION_TRANSLATE) !== 0;
        if ($withVirtualFields) {
            $this->addVirtualFields($result, $className, $applyExclusions, $translate);
        }

        if ($options & self::OPTION_WITH_RELATIONS) {
            $withEntityDetails = ($options & self::OPTION_WITH_ENTITY_DETAILS) !== 0;

            $this->addRelations($result, $className, $withEntityDetails, $applyExclusions, $translate);

            if ($withVirtualFields) {
                $this->addVirtualRelations($result, $className, $withEntityDetails, $applyExclusions, $translate);
            }

            if ($options & self::OPTION_WITH_UNIDIRECTIONAL) {
                $this->addUnidirectionalRelations(
                    $result,
                    $className,
                    $withEntityDetails,
                    $applyExclusions,
                    $translate
                );
            }
        }

        $this->addEnumFields($result, $className, $translate);
        $this->sortFields($result);

        return $result;
    }

    private function addEnumFields(array &$result, string $className, bool $translate): void
    {
        $enumFields = $this->enumVirtualFieldProvider?->getVirtualFields($className) ?? [];

        foreach ($enumFields as $enumField) {
            $this->addField(
                $result,
                $enumField,
                EntitySerializedFieldsHolder::getFieldType($className, $enumField),
                $this->getFieldLabel($this->getMetadataFor($className), $enumField),
                false,
                $translate,
            );
            $enumCode = $this->enumVirtualFieldProvider->getEnumCode($className, $enumField);
            if (null !== $enumCode) {
                $result[$enumField]['options']['enum_code'] = $enumCode;
            }
        }
    }
    /**
     * Adds entity fields to $result
     *
     * @param array $result
     * @param string $className
     * @param int $options Bit mask of options, see EntityFieldProvider::OPTION_*
     */
    protected function addEntityFields(array &$result, string $className, int $options): void
    {
        $metadata = $this->getMetadataFor($className);

        // add regular fields
        $configs = $this->extendConfigProvider
            ->getConfigs($className, ($options & self::OPTION_WITH_HIDDEN_FIELDS) !== 0);

        $configs = $this->filterConfigFields(
            $metadata,
            $result,
            $className,
            (string) (($options & self::OPTION_APPLY_EXCLUSIONS) !== 0),
            $configs
        );
        foreach ($configs as $fieldConfig) {
            /** @var FieldConfigId $fieldConfigId */
            $fieldConfigId = $fieldConfig->getId();
            $fieldName = $fieldConfigId->getFieldName();

            $this->addField(
                $result,
                $fieldName,
                $fieldConfigId->getFieldType(),
                $this->getFieldLabel($metadata, $fieldName),
                $metadata->isIdentifier($fieldName),
                ($options & self::OPTION_TRANSLATE) !== 0
            );
        }
    }

    /**
     * @param ClassMetadataInterface $metadata
     * @param array $result
     * @param string $className
     * @param bool $applyExclusions
     * @param array $configs
     * @return ConfigInterface[]
     */
    protected function filterConfigFields(
        ClassMetadataInterface $metadata,
        array &$result,
        string $className,
        bool $applyExclusions,
        array $configs
    ): array {
        return \array_filter(
            $configs,
            function (ConfigInterface $fieldConfig) use (&$result, $className, $applyExclusions, $metadata) {
                /** @var FieldConfigId $fieldConfigId */
                $fieldConfigId = $fieldConfig->getId();
                $fieldName = $fieldConfigId->getFieldName();

                $underlyingFieldType = $this->fieldTypeHelper->getUnderlyingType(
                    $fieldConfigId->getFieldType(),
                    $fieldConfig
                );
                if ($this->fieldTypeHelper->isRelation($underlyingFieldType)) {
                    // skip because this field is relation
                    return false;
                }
                if (isset($result[$fieldName])) {
                    // skip because a field with this name is already added, it could be a virtual field
                    return false;
                }
                if (!ExtendHelper::isFieldAccessible($fieldConfig)) {
                    return false;
                }
                if ($this->isIgnoredField($metadata, $fieldName)) {
                    return false;
                }
                if ($applyExclusions && $this->exclusionProvider->isIgnoredField($metadata, $fieldName)) {
                    return false;
                }

                return true;
            }
        );
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     *
     * Adds entity virtual fields to $result
     *
     * @param array  $result
     * @param string $className
     * @param bool   $applyExclusions
     * @param bool   $translate
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
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

            $query = $this->virtualFieldProvider->getVirtualFieldQuery($className, $fieldName);

            if ($translate && isset($query['select']['translatable'])) {
                $translate = (bool) $query['select']['translatable'];
            }

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
                $result[$fieldName]['related_entity_name'] = isset($result[$fieldName]['type'])
                && ExtendHelper::isEnumerableType($result[$fieldName]['type'])
                    ? EnumOption::class
                    : $metadata->getAssociationTargetClass($fieldName);
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

        $metadata         = $this->getMetadataFor($className);
        $virtualRelations = $this->virtualRelationProvider->getVirtualRelations($className);
        foreach ($virtualRelations as $associationName => $virtualRelation) {
            if ($applyExclusions && $this->exclusionProvider->isIgnoredField($metadata, $associationName)) {
                continue;
            }

            $fieldType       = $virtualRelation['relation_type'];
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
            'label' => $this->getLocalizedValue($label, $translate)
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
        $metadata         = $this->getMetadataFor($className);
        $associationNames = $metadata->getAssociationNames();
        foreach ($associationNames as $associationName) {
            if (isset($result[$associationName])) {
                // skip because a relation with this name is already added, it could be a virtual field
                continue;
            }
            if (!$this->isFieldAccessible($className, $associationName)) {
                continue;
            }
            $targetClassName = $metadata->getAssociationTargetClass($associationName);
            if (!$this->isEntityAccessible($targetClassName)) {
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

            if (!$this->isFieldAccessible($relatedClassName, $fieldName)) {
                continue;
            }
            $metadata = $this->getMetadataFor($relatedClassName);
            if ($this->isIgnoredRelation($metadata, $fieldName)) {
                continue;
            }
            if ($applyExclusions && $this->exclusionProvider->isIgnoredRelation($metadata, $fieldName)) {
                continue;
            }

            $labelKey     = $this->getLocalizedValue(
                $this->entityConfigProvider->getConfig($relatedClassName, $fieldName)->get('label'),
                $translate
            );
            $labelType    = ($mapping['type'] & ClassMetadataInfo::TO_ONE) ? 'label' : 'plural_label';
            $labelTypeKey = $this->getLocalizedValue(
                $this->entityConfigProvider->getConfig($relatedClassName)->get($labelType),
                $translate
            );

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

        $entities = $this->getMetadataForEntitiesWithAssociations();
        foreach ($entities as $metadata) {
            $targetMappings = $metadata->getAssociationMappings();
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
     * Return metadata for accessible and not ignored entities that have at least one association
     *
     * @return ClassMetadata[]
     */
    protected function getMetadataForEntitiesWithAssociations()
    {
        if (null !== $this->metadataForEntitiesWithAssociations) {
            return $this->metadataForEntitiesWithAssociations;
        }

        $metadataForEntitiesWithAssociations = [];
        $entityConfigs = $this->extendConfigProvider->getConfigs();
        foreach ($entityConfigs as $entityConfig) {
            if (!ExtendHelper::isEntityAccessible($entityConfig)) {
                continue;
            }
            $metadata = $this->getMetadataFor($entityConfig->getId()->getClassName());
            if ($this->isIgnoredEntity($metadata)) {
                continue;
            }
            $associations = $metadata->getAssociationMappings();
            if (empty($associations)) {
                continue;
            }
            $metadataForEntitiesWithAssociations[] = $metadata;
        }
        if ($this->isCachingEnabled) {
            $this->metadataForEntitiesWithAssociations = $metadataForEntitiesWithAssociations;
        }

        return $metadataForEntitiesWithAssociations;
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
        $label = $this->getLocalizedValue($label, $translateLabel);

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
            if ($key !== 'name') {
                $relation['related_entity_' . $key] = $val;
            }
        }

        return $relation;
    }

    /**
     * Check if the given entity is ready to be used in a business logic.
     *
     * @param string $className
     *
     * @return bool
     */
    protected function isEntityAccessible($className)
    {
        return
            $this->extendConfigProvider->hasConfig($className)
            && ExtendHelper::isEntityAccessible($this->extendConfigProvider->getConfig($className));
    }

    /**
     * Check if the given field is ready to be used in a business logic.
     *
     * @param string $className
     * @param string $fieldName
     *
     * @return bool
     */
    protected function isFieldAccessible($className, $fieldName)
    {
        return
            $this->extendConfigProvider->hasConfig($className, $fieldName)
            && ExtendHelper::isFieldAccessible($this->extendConfigProvider->getConfig($className, $fieldName));
    }

    /**
     * Check if the given entity should be ignored
     *
     * @param ClassMetadataInterface $metadata
     *
     * @return bool
     */
    protected function isIgnoredEntity(ClassMetadataInterface $metadata)
    {
        return false;
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
        if (isset($this->hiddenFields[$metadata->getName()][$fieldName])) {
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
        return false;
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
        if (isset($this->metadata[$className])) {
            return $this->metadata[$className];
        }

        $metadata = $this->getManagerForClass($className)->getMetadataFactory()->getMetadataFor($className);
        if ($this->isCachingEnabled) {
            $this->metadata[$className] = $metadata;
        }

        return $metadata;
    }

    /**
     * Gets a label of a field
     *
     * @param ClassMetadata $metadata
     * @param string        $fieldName
     *
     * @return string
     */
    protected function getFieldLabel(ClassMetadata $metadata, $fieldName)
    {
        $label = null;
        $className = $metadata->getName();
        if ($this->entityConfigProvider->hasConfig($className, $fieldName)) {
            $label = $this->entityConfigProvider->getConfig($className, $fieldName)->get('label');
        }
        if (!$label) {
            $label = ConfigHelper::getTranslationKey('entity', 'label', $className, $fieldName);
        }

        return $label;
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

    /**
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * @return string|null
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Translates the given message according to the set or default locale
     *
     * @param string|null $messageId
     * @param bool   $translate
     *
     * @return string The translated string
     */
    protected function getLocalizedValue(?string $messageId, bool $translate): string
    {
        if ($translate) {
            return $this->translator->trans((string) $messageId, [], null, $this->locale);
        }

        return $messageId;
    }
}

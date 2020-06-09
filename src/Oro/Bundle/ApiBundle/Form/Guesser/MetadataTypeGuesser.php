<?php

namespace Oro\Bundle\ApiBundle\Form\Guesser;

use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Config\ConfigAccessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Form\Type\CollectionType;
use Oro\Bundle\ApiBundle\Form\Type\CompoundObjectType;
use Oro\Bundle\ApiBundle\Form\Type\EntityCollectionType;
use Oro\Bundle\ApiBundle\Form\Type\EntityType;
use Oro\Bundle\ApiBundle\Form\Type\NestedAssociationType;
use Oro\Bundle\ApiBundle\Form\Type\ScalarObjectType;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetadataAccessorInterface;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityMapper;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormTypeGuesserInterface;
use Symfony\Component\Form\Guess\TypeGuess;

/**
 * Guesses form types based on "form_type_guesses" configuration and API metadata.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class MetadataTypeGuesser implements FormTypeGuesserInterface
{
    /** @var array [data type => [form type, options], ...] */
    private $dataTypeMappings;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var MetadataAccessorInterface|null */
    private $metadataAccessor;

    /** @var ConfigAccessorInterface|null */
    private $configAccessor;

    /** @var EntityMapper|null */
    private $entityMapper;

    /** @var IncludedEntityCollection|null */
    private $includedEntities;

    /**
     * @param array          $dataTypeMappings [data type => [form type, options], ...]
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(array $dataTypeMappings, DoctrineHelper $doctrineHelper)
    {
        $this->dataTypeMappings = $dataTypeMappings;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @return MetadataAccessorInterface|null
     */
    public function getMetadataAccessor()
    {
        return $this->metadataAccessor;
    }

    /**
     * @param MetadataAccessorInterface|null $metadataAccessor
     */
    public function setMetadataAccessor(MetadataAccessorInterface $metadataAccessor = null)
    {
        $this->metadataAccessor = $metadataAccessor;
    }

    /**
     * @return ConfigAccessorInterface|null
     */
    public function getConfigAccessor()
    {
        return $this->configAccessor;
    }

    /**
     * @param ConfigAccessorInterface|null $configAccessor
     */
    public function setConfigAccessor(ConfigAccessorInterface $configAccessor = null)
    {
        $this->configAccessor = $configAccessor;
    }

    /**
     * @return EntityMapper|null
     */
    public function getEntityMapper()
    {
        return $this->entityMapper;
    }

    /**
     * @param EntityMapper|null $entityMapper
     */
    public function setEntityMapper(EntityMapper $entityMapper = null)
    {
        $this->entityMapper = $entityMapper;
    }

    /**
     * @return IncludedEntityCollection|null
     */
    public function getIncludedEntities()
    {
        return $this->includedEntities;
    }

    /**
     * @param IncludedEntityCollection|null $includedEntities
     */
    public function setIncludedEntities(IncludedEntityCollection $includedEntities = null)
    {
        $this->includedEntities = $includedEntities;
    }

    /**
     * {@inheritdoc}
     */
    public function guessType($class, $property)
    {
        $metadata = $this->getMetadataForClass($class);
        if (null !== $metadata) {
            if ($metadata->hasField($property)) {
                return $this->getTypeGuessForField($metadata->getField($property)->getDataType());
            }
            if ($metadata->hasAssociation($property)) {
                $association = $metadata->getAssociation($property);
                $fieldConfig = $this->getFieldConfig($class, $property);
                if (DataType::isAssociationAsField($association->getDataType())) {
                    if (null === $fieldConfig) {
                        return null;
                    }

                    if (DataType::isNestedObject($fieldConfig->getDataType())) {
                        return $this->getTypeGuessForNestedObjectAssociation($association, $fieldConfig);
                    }
                    if ($association->isCollapsed()) {
                        return $this->getTypeGuessForCollapsedArrayAssociation($association, $fieldConfig);
                    }

                    return $this->getTypeGuessForArrayAssociation($association, $fieldConfig);
                }

                if (null !== $fieldConfig && DataType::isNestedAssociation($fieldConfig->getDataType())) {
                    return $this->getTypeGuessForNestedAssociation($association, $fieldConfig);
                }

                return $this->getTypeGuessForAssociation($association);
            }
        }

        return $this->createDefaultTypeGuess();
    }

    /**
     * {@inheritdoc}
     */
    public function guessRequired($class, $property)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function guessMaxLength($class, $property)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function guessPattern($class, $property)
    {
        return null;
    }

    /**
     * @param string $class
     *
     * @return EntityMetadata|null
     */
    private function getMetadataForClass($class)
    {
        return null !== $this->metadataAccessor
            ? $this->metadataAccessor->getMetadata($class)
            : null;
    }

    /**
     * @param string $class
     *
     * @return EntityDefinitionConfig|null
     */
    private function getConfigForClass($class)
    {
        return null !== $this->configAccessor
            ? $this->configAccessor->getConfig($class)
            : null;
    }

    /**
     * @param string $class
     * @param string $property
     *
     * @return EntityDefinitionFieldConfig|null
     */
    private function getFieldConfig($class, $property)
    {
        $config = $this->getConfigForClass($class);

        return null !== $config
            ? $config->getField($property)
            : null;
    }

    /**
     * @param string $formType
     * @param array  $formOptions
     * @param int    $confidence
     *
     * @return TypeGuess
     */
    private function createTypeGuess($formType, array $formOptions, $confidence)
    {
        return new TypeGuess($formType, $formOptions, $confidence);
    }

    /**
     * @return TypeGuess
     */
    private function createDefaultTypeGuess()
    {
        return $this->createTypeGuess(TextType::class, [], TypeGuess::LOW_CONFIDENCE);
    }

    /**
     * @param string $dataType
     *
     * @return TypeGuess
     */
    private function getTypeGuessForField($dataType)
    {
        if (isset($this->dataTypeMappings[$dataType])) {
            return $this->getTypeGuessForMappedDataType($dataType);
        }

        if (isset($this->dataTypeMappings[DataType::ARRAY]) && DataType::isArray($dataType)) {
            return $this->getTypeGuessForMappedDataType(DataType::ARRAY);
        }

        return $this->createDefaultTypeGuess();
    }

    /**
     * @param string $dataType
     *
     * @return TypeGuess
     */
    private function getTypeGuessForMappedDataType($dataType)
    {
        [$formType, $options] = $this->dataTypeMappings[$dataType];

        return $this->createTypeGuess($formType, $options, TypeGuess::HIGH_CONFIDENCE);
    }

    /**
     * @param AssociationMetadata $metadata
     *
     * @return TypeGuess
     */
    private function getTypeGuessForAssociation(AssociationMetadata $metadata)
    {
        return $this->createTypeGuess(
            EntityType::class,
            [
                'metadata'          => $metadata,
                'entity_mapper'     => $this->entityMapper,
                'included_entities' => $this->includedEntities
            ],
            TypeGuess::HIGH_CONFIDENCE
        );
    }

    /**
     * @param AssociationMetadata         $metadata
     * @param EntityDefinitionFieldConfig $config
     *
     * @return TypeGuess|null
     */
    private function getTypeGuessForArrayAssociation(
        AssociationMetadata $metadata,
        EntityDefinitionFieldConfig $config
    ) {
        $targetMetadata = $metadata->getTargetMetadata();
        if (null === $targetMetadata) {
            return null;
        }

        if (!$metadata->isCollection()) {
            $formOptions = [
                'data_class' => $targetMetadata->getClassName(),
                'metadata'   => $targetMetadata,
                'config'     => $config->getTargetEntity()
            ];
            $configuredFormOptions = $config->getFormOptions();
            if ($configuredFormOptions) {
                $formOptions = array_merge($configuredFormOptions, $formOptions);
            }

            return $this->createTypeGuess(CompoundObjectType::class, $formOptions, TypeGuess::HIGH_CONFIDENCE);
        }

        $formType = $this->doctrineHelper->isManageableEntityClass($targetMetadata->getClassName())
            ? EntityCollectionType::class
            : CollectionType::class;
        $formOptions = [
            'entry_data_class' => $targetMetadata->getClassName(),
            'entry_type'       => CompoundObjectType::class,
            'entry_options'    => [
                'metadata' => $targetMetadata,
                'config'   => $config->getTargetEntity()
            ]
        ];
        $configuredFormOptions = $config->getFormOptions();
        if ($configuredFormOptions) {
            $formOptions = $this->mergeCollectionFormOptions($formOptions, $configuredFormOptions);
        }

        return $this->createTypeGuess($formType, $formOptions, TypeGuess::HIGH_CONFIDENCE);
    }

    /**
     * @param AssociationMetadata         $metadata
     * @param EntityDefinitionFieldConfig $config
     *
     * @return TypeGuess|null
     */
    private function getTypeGuessForCollapsedArrayAssociation(
        AssociationMetadata $metadata,
        EntityDefinitionFieldConfig $config
    ) {
        $targetMetadata = $metadata->getTargetMetadata();
        if (null === $targetMetadata) {
            return null;
        }

        // it is expected that collapsed association must have only one field or association
        $fieldNames = array_keys($targetMetadata->getFields());
        $targetFieldName = reset($fieldNames);
        if (!$targetFieldName) {
            $associationNames = array_keys($targetMetadata->getAssociations());
            $targetFieldName = reset($associationNames);
        }
        if (!$targetFieldName) {
            return null;
        }

        if (!$metadata->isCollection()) {
            $formOptions = [
                'data_class'    => $targetMetadata->getClassName(),
                'data_property' => $targetFieldName,
                'metadata'      => $targetMetadata,
                'config'        => $config->getTargetEntity()
            ];
            $configuredFormOptions = $config->getFormOptions();
            if ($configuredFormOptions) {
                $formOptions = array_merge($configuredFormOptions, $formOptions);
            }

            return $this->createTypeGuess(ScalarObjectType::class, $formOptions, TypeGuess::HIGH_CONFIDENCE);
        }

        $formType = $this->doctrineHelper->isManageableEntityClass($targetMetadata->getClassName())
            ? EntityCollectionType::class
            : CollectionType::class;
        $formOptions = [
            'entry_data_class' => $targetMetadata->getClassName(),
            'entry_type'       => ScalarObjectType::class,
            'entry_options'    => [
                'data_property' => $targetFieldName,
                'metadata'      => $targetMetadata,
                'config'        => $config->getTargetEntity()
            ]
        ];
        $configuredFormOptions = $config->getFormOptions();
        if ($configuredFormOptions) {
            $formOptions = $this->mergeCollectionFormOptions($formOptions, $configuredFormOptions);
        }

        return $this->createTypeGuess(
            $formType,
            $formOptions,
            TypeGuess::HIGH_CONFIDENCE
        );
    }

    /**
     * @param AssociationMetadata         $metadata
     * @param EntityDefinitionFieldConfig $config
     *
     * @return TypeGuess
     */
    private function getTypeGuessForNestedObjectAssociation(
        AssociationMetadata $metadata,
        EntityDefinitionFieldConfig $config
    ) {
        $configuredFormOptions = $config->getFormOptions();
        if (!$configuredFormOptions || empty($configuredFormOptions['data_class'])) {
            throw new InvalidArgumentException(sprintf(
                'The form options for the "%s" field should contain the "data_class" option.',
                $metadata->getName()
            ));
        }

        return $this->createTypeGuess(
            CompoundObjectType::class,
            array_merge(
                $configuredFormOptions,
                [
                    'metadata' => $metadata->getTargetMetadata(),
                    'config'   => $config->getTargetEntity()
                ]
            ),
            TypeGuess::HIGH_CONFIDENCE
        );
    }

    /**
     * @param AssociationMetadata         $metadata
     * @param EntityDefinitionFieldConfig $config
     *
     * @return TypeGuess
     */
    private function getTypeGuessForNestedAssociation(
        AssociationMetadata $metadata,
        EntityDefinitionFieldConfig $config
    ) {
        return $this->createTypeGuess(
            NestedAssociationType::class,
            ['metadata' => $metadata, 'config' => $config],
            TypeGuess::HIGH_CONFIDENCE
        );
    }

    /**
     * @param array $formOptions
     * @param array $configuredFormOptions
     *
     * @return array
     */
    private function mergeCollectionFormOptions(array $formOptions, array $configuredFormOptions): array
    {
        if (\array_key_exists('entry_options', $configuredFormOptions)) {
            $formOptions['entry_options'] = array_merge(
                $configuredFormOptions['entry_options'],
                $formOptions['entry_options']
            );
            unset($configuredFormOptions['entry_options']);
        }

        return array_merge($configuredFormOptions, $formOptions);
    }
}

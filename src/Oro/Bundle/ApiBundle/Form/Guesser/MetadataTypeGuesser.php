<?php

namespace Oro\Bundle\ApiBundle\Form\Guesser;

use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Config\ConfigAccessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Form\Type\CollectionType;
use Oro\Bundle\ApiBundle\Form\Type\CompoundObjectType;
use Oro\Bundle\ApiBundle\Form\Type\EntityCollectionType;
use Oro\Bundle\ApiBundle\Form\Type\EntityType;
use Oro\Bundle\ApiBundle\Form\Type\NestedAssociationType;
use Oro\Bundle\ApiBundle\Form\Type\ScalarObjectType;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetadataAccessorInterface;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityMapper;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Form\FormTypeGuesserInterface;
use Symfony\Component\Form\Guess\TypeGuess;

/**
 * Guesses form types based on "form_type_guesses" configuration and API metadata.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class MetadataTypeGuesser implements FormTypeGuesserInterface
{
    private DataTypeGuesser $dataTypeGuesser;
    private DoctrineHelper $doctrineHelper;
    private ?MetadataAccessorInterface $metadataAccessor = null;
    private ?ConfigAccessorInterface $configAccessor = null;
    private ?EntityMapper $entityMapper = null;
    private ?IncludedEntityCollection $includedEntities = null;

    public function __construct(DataTypeGuesser $dataTypeGuesser, DoctrineHelper $doctrineHelper)
    {
        $this->dataTypeGuesser = $dataTypeGuesser;
        $this->doctrineHelper = $doctrineHelper;
    }

    public function getMetadataAccessor(): ?MetadataAccessorInterface
    {
        return $this->metadataAccessor;
    }

    public function setMetadataAccessor(?MetadataAccessorInterface $metadataAccessor): void
    {
        $this->metadataAccessor = $metadataAccessor;
    }

    public function getConfigAccessor(): ?ConfigAccessorInterface
    {
        return $this->configAccessor;
    }

    public function setConfigAccessor(?ConfigAccessorInterface $configAccessor): void
    {
        $this->configAccessor = $configAccessor;
    }

    public function getEntityMapper(): ?EntityMapper
    {
        return $this->entityMapper;
    }

    public function setEntityMapper(?EntityMapper $entityMapper): void
    {
        $this->entityMapper = $entityMapper;
    }

    public function getIncludedEntities(): ?IncludedEntityCollection
    {
        return $this->includedEntities;
    }

    public function setIncludedEntities(?IncludedEntityCollection $includedEntities): void
    {
        $this->includedEntities = $includedEntities;
    }

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function guessType($class, $property)
    {
        $metadata = $this->metadataAccessor?->getMetadata($class);
        if (null !== $metadata) {
            if ($metadata->hasField($property)) {
                return $this->getTypeGuessForField($metadata->getField($property)->getDataType());
            }
            if ($metadata->hasAssociation($property)) {
                $association = $metadata->getAssociation($property);
                $fieldConfig = $this->configAccessor?->getConfig($class)?->getField($property);
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

        return $this->dataTypeGuesser->guessDefault();
    }

    /**
     * {@inheritDoc}
     */
    public function guessRequired($class, $property)
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function guessMaxLength($class, $property)
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function guessPattern($class, $property)
    {
        return null;
    }

    private function getTypeGuessForField(?string $dataType): TypeGuess
    {
        return $dataType
            ? $this->dataTypeGuesser->guessType($dataType)
            : $this->dataTypeGuesser->guessDefault();
    }

    private function getTypeGuessForAssociation(AssociationMetadata $metadata): TypeGuess
    {
        return new TypeGuess(
            EntityType::class,
            [
                'metadata'          => $metadata,
                'entity_mapper'     => $this->entityMapper,
                'included_entities' => $this->includedEntities
            ],
            TypeGuess::HIGH_CONFIDENCE
        );
    }

    private function getTypeGuessForArrayAssociation(
        AssociationMetadata $metadata,
        EntityDefinitionFieldConfig $config
    ): ?TypeGuess {
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

            return new TypeGuess(CompoundObjectType::class, $formOptions, TypeGuess::HIGH_CONFIDENCE);
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

        return new TypeGuess($formType, $formOptions, TypeGuess::HIGH_CONFIDENCE);
    }

    private function getTypeGuessForCollapsedArrayAssociation(
        AssociationMetadata $metadata,
        EntityDefinitionFieldConfig $config
    ): ?TypeGuess {
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

            return new TypeGuess(ScalarObjectType::class, $formOptions, TypeGuess::HIGH_CONFIDENCE);
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

        return new TypeGuess($formType, $formOptions, TypeGuess::HIGH_CONFIDENCE);
    }

    private function getTypeGuessForNestedObjectAssociation(
        AssociationMetadata $metadata,
        EntityDefinitionFieldConfig $config
    ): ?TypeGuess {
        $configuredFormOptions = $config->getFormOptions();
        $inheritData = $configuredFormOptions['inherit_data'] ?? false;
        if ($inheritData) {
            if (false === ($configuredFormOptions['mapped'] ?? true)) {
                $configuredFormOptions['children_mapped'] = false;
            }
        } elseif (empty($configuredFormOptions['data_class'])) {
            throw new InvalidArgumentException(sprintf(
                'The form options for the "%s" field should contain the "data_class" option.',
                $metadata->getName()
            ));
        }

        return new TypeGuess(
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

    private function getTypeGuessForNestedAssociation(
        AssociationMetadata $metadata,
        EntityDefinitionFieldConfig $config
    ): TypeGuess {
        return new TypeGuess(
            NestedAssociationType::class,
            ['metadata' => $metadata, 'config' => $config],
            TypeGuess::HIGH_CONFIDENCE
        );
    }

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

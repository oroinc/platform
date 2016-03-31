<?php

namespace Oro\Bundle\ApiBundle\Form\Guesser;

use Symfony\Component\Form\FormTypeGuesserInterface;
use Symfony\Component\Form\Guess\TypeGuess;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetadataAccessorInterface;

class MetadataTypeGuesser implements FormTypeGuesserInterface
{
    /** @var MetadataAccessorInterface|null */
    protected $metadataAccessor;

    /** @var array [data type => [form type, options], ...] */
    protected $dataTypeMappings = [];

    /**
     * @param array $dataTypeMappings [data type => [form type, options], ...]
     */
    public function __construct(array $dataTypeMappings = [])
    {
        $this->dataTypeMappings = $dataTypeMappings;
    }

    /**
     * @param MetadataAccessorInterface|null $metadataAccessor
     */
    public function setMetadataAccessor(MetadataAccessorInterface $metadataAccessor = null)
    {
        $this->metadataAccessor = $metadataAccessor;
    }

    /**
     * @param string $dataType
     * @param string $formType
     * @param array  $formOptions
     */
    public function addDataTypeMapping($dataType, $formType, array $formOptions = [])
    {
        $this->dataTypeMappings[$dataType] = [$formType, $formOptions];
    }

    /**
     * {@inheritdoc}
     */
    public function guessType($class, $property)
    {
        $metadata = $this->getMetadataForClass($class);
        if (null !== $metadata) {
            if ($metadata->hasField($property)) {
                $field = $metadata->getField($property);

                return $this->getTypeGuessByDataType($field->getDataType());
            } elseif ($metadata->hasAssociation($property)) {
                $association = $metadata->getAssociation($property);

                return $this->getTypeGuessByEntity(
                    $association->getTargetClassName(),
                    $association->isCollection()
                );
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
    protected function getMetadataForClass($class)
    {
        return null !== $this->metadataAccessor
            ? $this->metadataAccessor->getMetadata($class)
            : null;
    }

    /**
     * @param string $formType
     * @param array  $formOptions
     * @param int    $confidence
     *
     * @return TypeGuess
     */
    protected function createTypeGuess($formType, array $formOptions, $confidence)
    {
        return new TypeGuess($formType, $formOptions, $confidence);
    }

    /**
     * @return TypeGuess
     */
    protected function createDefaultTypeGuess()
    {
        return $this->createTypeGuess('text', [], TypeGuess::LOW_CONFIDENCE);
    }

    /**
     * @param string $dataType
     *
     * @return TypeGuess
     */
    protected function getTypeGuessByDataType($dataType)
    {
        if (!isset($this->dataTypeMappings[$dataType])) {
            return $this->createDefaultTypeGuess();
        }

        list($formType, $options) = $this->dataTypeMappings[$dataType];

        return $this->createTypeGuess($formType, $options, TypeGuess::HIGH_CONFIDENCE);
    }

    /**
     * @param string $class
     * @param bool   $multiple
     *
     * @return TypeGuess|null
     */
    protected function getTypeGuessByEntity($class, $multiple)
    {
        return null;
    }
}

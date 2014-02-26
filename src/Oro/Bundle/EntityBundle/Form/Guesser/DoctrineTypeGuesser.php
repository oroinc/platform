<?php

namespace Oro\Bundle\EntityBundle\Form\Guesser;

use Symfony\Component\Form\Guess\TypeGuess;

class DoctrineTypeGuesser extends AbstractFormGuesser
{
    /**
     * @var array
     */
    protected $doctrineTypeMappings = array();

    /**
     * @param string $doctrineType
     * @param string $formType
     * @param array $formOptions
     */
    public function addDoctrineTypeMapping($doctrineType, $formType, array $formOptions = array())
    {
        $this->doctrineTypeMappings[$doctrineType] = array(
            'type' => $formType,
            'options' => $formOptions,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function guessType($class, $property)
    {
        $metadata = $this->getMetadataForClass($class);
        if (!$metadata) {
            return $this->createDefaultTypeGuess();
        }

        if ($metadata->hasAssociation($property)) {
            $targetClass = $metadata->getAssociationTargetClass($property);
            $multiple = $metadata->isCollectionValuedAssociation($property);
            return $this->getTypeGuessByEntity($targetClass, $multiple);
        } else {
            $fieldType = $metadata->getTypeOfField($property);
            return $this->getTypeGuessByDoctrineType($fieldType, $class, $property);
        }
    }

    /**
     * @param string $doctrineType
     * @param string $class
     * @param string $field
     * @return TypeGuess
     */
    protected function getTypeGuessByDoctrineType($doctrineType, $class, $field)
    {
        if (!isset($this->doctrineTypeMappings[$doctrineType])) {
            return $this->createDefaultTypeGuess();
        }

        $formType = $this->doctrineTypeMappings[$doctrineType]['type'];
        $formOptions = $this->doctrineTypeMappings[$doctrineType]['options'];
        $formOptions = $this->addLabelOption($formOptions, $class, $field);

        return $this->createTypeGuess($formType, $formOptions);
    }

    /**
     * @param string $class
     * @param bool $multiple
     * @return TypeGuess
     */
    protected function getTypeGuessByEntity($class, $multiple)
    {
        $formType = 'entity';
        $formOptions = array(
            'class' => $class,
            'multiple' => $multiple
        );
        $formOptions = $this->addLabelOption($formOptions, $class, null, $multiple);

        return $this->createTypeGuess($formType, $formOptions);
    }
}

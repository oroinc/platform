<?php

namespace Oro\Bundle\ActionBundle\Model;

use Symfony\Component\Form\FormTypeGuesserInterface;
use Symfony\Component\Form\Guess\TypeGuess;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;

class AttributeGuesser extends AbstractGuesser
{
    /**
     * @var FormTypeGuesserInterface
     */
    protected $formTypeGuesser;

    /**
     * @param string $rootClass
     * @param string|PropertyPathInterface $propertyPath
     * @return array|null
     */
    public function guessAttributeParameters($rootClass, $propertyPath)
    {
        $metadataParameters = $this->guessMetadataAndField($rootClass, $propertyPath);
        if (!$metadataParameters) {
            return null;
        }

        /** @var ClassMetadata $metadata */
        $metadata = $metadataParameters['metadata'];
        $field = $metadataParameters['field'];

        $scalarParameters = $this->guessAttributeParametersScalarField($metadata, $field);
        if ($scalarParameters !== false) {
            return $scalarParameters;
        }

        if ($metadata->hasAssociation($field)) {
            $multiple = $metadata->isCollectionValuedAssociation($field);
            $type = $multiple
                ? 'object'
                : 'entity';
            $class = $multiple
                ? 'Doctrine\Common\Collections\ArrayCollection'
                : $metadata->getAssociationTargetClass($field);

            return $this->formatResult(
                $this->getLabel($metadata->getName(), $field, $multiple),
                $type,
                array('class' => $class)
            );
        }

        return null;
    }

    /**
     * @param Attribute $attribute
     * @return null|TypeGuess
     */
    public function guessAttributeForm(Attribute $attribute)
    {
        $attributeType = $attribute->getType();
        if ($attributeType === 'entity') {
            list($formType, $formOptions) = $this->getEntityForm($attribute->getOption('class'));
        } elseif (isset($this->formTypeMapping[$attributeType])) {
            $formType = $this->formTypeMapping[$attributeType]['type'];
            $formOptions = $this->formTypeMapping[$attributeType]['options'];
        } else {
            return null;
        }

        return new TypeGuess($formType, $formOptions, TypeGuess::VERY_HIGH_CONFIDENCE);
    }

    /**
     * @param string $entityClass
     * @return array
     */
    protected function getEntityForm($entityClass)
    {
        $formType = null;
        $formOptions = array();
        if ($this->formConfigProvider->hasConfig($entityClass)) {
            $formConfig = $this->formConfigProvider->getConfig($entityClass);
            $formType = $formConfig->get('form_type');
            $formOptions = $formConfig->get('form_options', false, array());
        }
        if (!$formType) {
            $formType = 'entity';
            $formOptions = array(
                'class' => $entityClass,
                'multiple' => false,
            );
        }

        return array($formType, $formOptions);
    }

    /**
     * @param string $rootClass
     * @param Attribute $attribute
     * @return null|TypeGuess
     */
    public function guessClassAttributeForm($rootClass, Attribute $attribute)
    {
        $propertyPath = $attribute->getPropertyPath();
        if (!$propertyPath) {
            return $this->guessAttributeForm($attribute);
        }

        $attributeParameters = $this->guessMetadataAndField($rootClass, $propertyPath);
        if (!$attributeParameters) {
            return $this->guessAttributeForm($attribute);
        }

        /** @var ClassMetadata $metadata */
        $metadata = $attributeParameters['metadata'];
        $class = $metadata->getName();
        $field = $attributeParameters['field'];

        return $this->getFormTypeGuesser()->guessType($class, $field);
    }

    /**
     * @param string $class
     * @param string $field
     * @param bool $multiple
     * @return string|null
     */
    protected function getLabel($class, $field, $multiple = false)
    {
        if (!$this->entityConfigProvider->hasConfig($class, $field)) {
            return null;
        }

        $entityConfig = $this->entityConfigProvider->getConfig($class, $field);
        $labelOption = $multiple ? 'plural_label' : 'label';

        return $entityConfig->get($labelOption);
    }

    /**
     * @return FormTypeGuesserInterface
     */
    protected function getFormTypeGuesser()
    {
        if (!$this->formTypeGuesser) {
            $this->formTypeGuesser = $this->formRegistry->getTypeGuesser();
        }

        return $this->formTypeGuesser;
    }

    /**
     * Return "false" if can't find config for field, "null" if field type is unknown for given field
     * or array with config data for given field
     *
     * @param ClassMetadata $metadata
     * @param $field
     * @return array|bool
     */
    protected function guessAttributeParametersScalarField(ClassMetadata $metadata, $field)
    {
        $typeMappings = $this->getRegisteredTypeMappings();

        if ($metadata->hasField($field)) {
            $doctrineType = $metadata->getTypeOfField($field);
            if (!isset($typeMappings[$doctrineType])) {
                return null;
            }

            return $this->formatResult(
                $this->getLabel($metadata->getName(), $field),
                $typeMappings[$doctrineType]['type'],
                $typeMappings[$doctrineType]['options']
            );
        } elseif ($this->entityConfigProvider->hasConfig($metadata->getName(), $field)) {
            $entityConfig = $this->entityConfigProvider->getConfig($metadata->getName(), $field);
            $fieldType = $entityConfig->getId()->getFieldType();
            if (!$metadata->hasAssociation($field)) {
                return $this->formatResult(
                    $entityConfig->get('label'),
                    $typeMappings[$fieldType]['type'],
                    $typeMappings[$fieldType]['options']
                );
            }
        }

        return false;
    }

    /**
     * @return array
     */
    private function getRegisteredTypeMappings()
    {
        if ($this->doctrineTypeMappingProvider !== null) {
            return array_merge(
                $this->doctrineTypeMapping,
                $this->doctrineTypeMappingProvider->getDoctrineTypeMappings()
            );
        }

        return $this->doctrineTypeMapping;
    }
}

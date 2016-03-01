<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Symfony\Component\Form\FormRegistry;
use Symfony\Component\Form\FormTypeGuesserInterface;
use Symfony\Component\Form\Guess\TypeGuess;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;

class AttributeGuesser
{
    /**
     * @var FormRegistry
     */
    protected $formRegistry;

    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @var ConfigProvider
     */
    protected $entityConfigProvider;

    /**
     * @var ConfigProvider
     */
    protected $formConfigProvider;

    /**
     * @var FormTypeGuesserInterface
     */
    protected $formTypeGuesser;

    /**
     * @var array
     */
    protected $doctrineTypeMapping = array();

    /**
     * @var array
     */
    protected $formTypeMapping = array();

    /**
     * @param FormRegistry    $formRegistry
     * @param ManagerRegistry $managerRegistry
     * @param ConfigProvider  $entityConfigProvider
     * @param ConfigProvider  $formConfigProvider
     */
    public function __construct(
        FormRegistry $formRegistry,
        ManagerRegistry $managerRegistry,
        ConfigProvider $entityConfigProvider,
        ConfigProvider $formConfigProvider
    ) {
        $this->formRegistry = $formRegistry;
        $this->managerRegistry = $managerRegistry;
        $this->entityConfigProvider = $entityConfigProvider;
        $this->formConfigProvider = $formConfigProvider;
    }

    /**
     * @param string $doctrineType
     * @param string $attributeType
     * @param array $attributeOptions
     */
    public function addDoctrineTypeMapping($doctrineType, $attributeType, array $attributeOptions = array())
    {
        $this->doctrineTypeMapping[$doctrineType] = array(
            'type' => $attributeType,
            'options' => $attributeOptions,
        );
    }

    /**
     * @param string $attributeType
     * @param string $formType
     * @param array $formOptions
     */
    public function addFormTypeMapping($attributeType, $formType, array $formOptions = array())
    {
        $this->formTypeMapping[$attributeType] = array(
            'type' => $formType,
            'options' => $formOptions,
        );
    }

    /**
     * @param string $rootClass
     * @param string|PropertyPathInterface $propertyPath
     * @return array|null
     */
    public function guessMetadataAndField($rootClass, $propertyPath)
    {
        if (!$propertyPath instanceof PropertyPathInterface) {
            $propertyPath = new PropertyPath($propertyPath);
        }

        $pathElements = array_values($propertyPath->getElements());
        $elementsCount = count($pathElements);
        if ($elementsCount < 2) {
            return null;
        }

        $metadata = $this->getMetadataForClass($rootClass);

        $field = null;
        for ($i = 1; $i < $elementsCount; $i++) {
            $field = $pathElements[$i];
            $hasAssociation = $metadata->hasAssociation($field)
                || $this->entityConfigProvider->hasConfig($rootClass, $field);
            if ($hasAssociation && $i < $elementsCount - 1) {
                $className = $metadata->hasAssociation($field)
                    ? $metadata->getAssociationTargetClass($field)
                    : $this->entityConfigProvider->getConfig($rootClass, $field)->getId()->getClassName();
                $metadata = $this->getMetadataForClass($className);
            } elseif (!$hasAssociation && !$metadata->hasField($field)) {
                return null;
            }
        }

        return array(
            'metadata' => $metadata,
            'field' => $field
        );
    }

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
                :  $metadata->getAssociationTargetClass($field);

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
     * @param string $label
     * @param string $type
     * @param array $options
     * @return array
     */
    protected function formatResult($label, $type, array $options = array())
    {
        return array(
            'label' => $label,
            'type' => $type,
            'options' => $options,
        );
    }

    /**
     * @param string $class
     * @return ClassMetadata
     * @throws WorkflowException
     */
    protected function getMetadataForClass($class)
    {
        $entityManager = $this->managerRegistry->getManagerForClass($class);
        if (!$entityManager) {
            throw new WorkflowException(sprintf('Can\'t get entity manager for class %s', $class));
        }

        return $entityManager->getClassMetadata($class);
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
        if ($metadata->hasField($field)) {
            $doctrineType = $metadata->getTypeOfField($field);
            if (!isset($this->doctrineTypeMapping[$doctrineType])) {
                return null;
            }

            return $this->formatResult(
                $this->getLabel($metadata->getName(), $field),
                $this->doctrineTypeMapping[$doctrineType]['type'],
                $this->doctrineTypeMapping[$doctrineType]['options']
            );
        } elseif ($this->entityConfigProvider->hasConfig($metadata->getName(), $field)) {
            $entityConfig = $this->entityConfigProvider->getConfig($metadata->getName(), $field);
            $fieldType = $entityConfig->getId()->getFieldType();
            if (!$metadata->hasAssociation($field)) {
                return $this->formatResult(
                    $entityConfig->get('label'),
                    $this->doctrineTypeMapping[$fieldType]['type'],
                    $this->doctrineTypeMapping[$fieldType]['options']
                );
            }
        }

        return false;
    }
}

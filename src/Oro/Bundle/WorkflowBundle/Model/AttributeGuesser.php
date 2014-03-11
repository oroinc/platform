<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Symfony\Component\Form\FormRegistry;
use Symfony\Component\Form\FormTypeGuesserInterface;
use Symfony\Component\Form\Guess\TypeGuess;
use Symfony\Component\PropertyAccess\PropertyPath;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
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
     * @var ConfigProviderInterface
     */
    protected $entityConfigProvider;

    /**
     * @var ConfigProviderInterface
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
     * @param FormRegistry $formRegistry
     * @param ManagerRegistry $managerRegistry
     * @param ConfigProviderInterface $entityConfigProvider
     * @param ConfigProviderInterface $formConfigProvider
     */
    public function __construct(
        FormRegistry $formRegistry,
        ManagerRegistry $managerRegistry,
        ConfigProviderInterface $entityConfigProvider,
        ConfigProviderInterface $formConfigProvider
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
     * @param string|PropertyPath $propertyPath
     * @return array|null
     */
    public function guessMetadataAndField($rootClass, $propertyPath)
    {
        if (!$propertyPath instanceof PropertyPath) {
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
            $hasAssociation = $metadata->hasAssociation($field);

            if ($hasAssociation && $i < $elementsCount - 1) {
                $metadata = $this->getMetadataForClass($metadata->getAssociationTargetClass($field));
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
     * @param string|PropertyPath $propertyPath
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
        if ($attributeType == 'entity') {
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
            $formType = $formConfig->has('form_type') ? $formConfig->get('form_type') : null;
            $formOptions = $formConfig->has('form_options') ? $formConfig->get('form_options') : array();
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
        // TODO: Remove in scope of https://magecore.atlassian.net/browse/BAP-2907
        $field = $this->fixPropertyPath($field);

        if (!$this->entityConfigProvider->hasConfig($class, $field)) {
            return null;
        }

        $entityConfig = $this->entityConfigProvider->getConfig($class, $field);
        $labelOption = $multiple ? 'plural_label' : 'label';
        if (!$entityConfig->has($labelOption)) {
            return null;
        }

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
     * Remove "field_" prefix from property path for extended fields
     * TODO: Remove in scope of https://magecore.atlassian.net/browse/BAP-2907
     *
     * @param string $propertyPath
     * @return string
     */
    public function fixPropertyPath($propertyPath)
    {
        $parts = explode('.', $propertyPath);
        foreach ($parts as $key => $part) {
            if (strpos($part, ExtendConfigDumper::FIELD_PREFIX) === 0) {
                $parts[$key] = substr($part, strlen(ExtendConfigDumper::FIELD_PREFIX));
            }
        }

        return implode('.', $parts);
    }
}

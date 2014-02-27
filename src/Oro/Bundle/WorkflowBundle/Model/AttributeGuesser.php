<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Symfony\Component\PropertyAccess\PropertyPath;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;

class AttributeGuesser
{
    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @var ConfigProviderInterface
     */
    protected $entityConfigProvider;

    /**
     * @var array
     */
    protected $doctrineTypeMapping = array();

    /**
     * @param ManagerRegistry $managerRegistry
     * @param ConfigProviderInterface $entityConfigProvider
     */
    public function __construct(ManagerRegistry $managerRegistry, ConfigProviderInterface $entityConfigProvider)
    {
        $this->managerRegistry = $managerRegistry;
        $this->entityConfigProvider = $entityConfigProvider;
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
        if (!$entityConfig->has($labelOption)) {
            return null;
        }

        return $entityConfig->get($labelOption);
    }
}

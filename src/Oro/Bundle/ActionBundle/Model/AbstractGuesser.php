<?php

namespace Oro\Bundle\ActionBundle\Model;

use Oro\Bundle\ActionBundle\Exception\AttributeException;
use Symfony\Component\Form\FormRegistry;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;

use Oro\Bundle\ActionBundle\Provider\DoctrineTypeMappingProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

abstract class AbstractGuesser
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
     * @var array
     */
    protected $doctrineTypeMapping = [];

    /**
     * @var array
     */
    protected $formTypeMapping = [];

    /**
     * @var DoctrineTypeMappingProvider|null
     */
    protected $doctrineTypeMappingProvider;

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
     * @param DoctrineTypeMappingProvider|null $doctrineTypeMappingProvider
     */
    public function setDoctrineTypeMappingProvider(DoctrineTypeMappingProvider $doctrineTypeMappingProvider = null)
    {
        $this->doctrineTypeMappingProvider = $doctrineTypeMappingProvider;
    }

    /**
     * @param string $doctrineType
     * @param string $attributeType
     * @param array  $attributeOptions
     */
    public function addDoctrineTypeMapping($doctrineType, $attributeType, array $attributeOptions = [])
    {
        $this->doctrineTypeMapping[$doctrineType] = [
            'type' => $attributeType,
            'options' => $attributeOptions
        ];
    }

    /**
     * @param string $variableType
     * @param string $formType
     * @param array  $formOptions
     */
    public function addFormTypeMapping($variableType, $formType, array $formOptions = [])
    {
        $this->formTypeMapping[$variableType] = [
            'type' => $formType,
            'options' => $formOptions,
        ];
    }

    /**
     * @param string                       $rootClass
     * @param string|PropertyPathInterface $propertyPath
     *
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

        return [
            'metadata' => $metadata,
            'field' => $field
        ];
    }

    /**
     * @param string $label
     * @param string $type
     * @param array  $options
     *
     * @return array
     */
    protected function formatResult($label, $type, array $options = [])
    {
        return [
            'label' => $label,
            'type' => $type,
            'options' => $options,
        ];
    }

    /**
     * @param string $class
     *
     * @return ClassMetadata
     * @throws AttributeException
     */
    protected function getMetadataForClass($class)
    {
        $entityManager = $this->managerRegistry->getManagerForClass($class);
        if (!$entityManager) {
            throw new AttributeException(sprintf('Can\'t get entity manager for class %s', $class));
        }

        return $entityManager->getClassMetadata($class);
    }
}

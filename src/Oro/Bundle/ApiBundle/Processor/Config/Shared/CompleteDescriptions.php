<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Symfony\Component\HttpKernel\Config\FileLocator;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\ApiDoc\EntityDescriptionProvider;
use Oro\Bundle\ApiBundle\ApiDoc\ResourceDocProviderInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\FiltersConfig;
use Oro\Bundle\ApiBundle\Model\Label;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Symfony\Component\Yaml\Yaml;

/**
 * Adds human-readable descriptions for the entity, fields and filters.
 */
class CompleteDescriptions implements ProcessorInterface
{
    /** @var EntityDescriptionProvider */
    protected $entityDescriptionProvider;

    /** @var ResourceDocProviderInterface */
    protected $resourceDocProvider;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var FileLocator */
    protected $fileLocator;

    protected $loadedDocumentation = [];

    protected $classDocumentation = [];

    /**
     * @param EntityDescriptionProvider    $entityDescriptionProvider
     * @param ResourceDocProviderInterface $resourceDocProvider
     * @param TranslatorInterface          $translator
     */
    public function __construct(
        EntityDescriptionProvider $entityDescriptionProvider,
        ResourceDocProviderInterface $resourceDocProvider,
        TranslatorInterface $translator,
        FileLocator $fileLocator
    ) {
        $this->entityDescriptionProvider = $entityDescriptionProvider;
        $this->resourceDocProvider = $resourceDocProvider;
        $this->translator = $translator;
        $this->fileLocator = $fileLocator;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        $targetAction = $context->getTargetAction();
        if (!$targetAction) {
            // descriptions cannot be set for undefined target action
            return;
        }

        $entityClass = $context->getClassName();
        $definition = $context->getResult();

        $this->setDescriptionForEntity(
            $definition,
            $entityClass,
            $targetAction,
            $context->isCollection(),
            $context->getAssociationName()
        );
        $this->setDescriptionsForFields($definition, $entityClass);
        $filters = $context->getFilters();
        if (null !== $filters) {
            $this->setDescriptionsForFilters($filters, $entityClass);
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $entityClass
     * @param string                 $targetAction
     * @param bool                   $isCollection
     * @param string                 $associationName
     */
    protected function setDescriptionForEntity(
        EntityDefinitionConfig $definition,
        $entityClass,
        $targetAction,
        $isCollection,
        $associationName
    ) {
        $entityDescription = false;
        $associationDescription = false;

        if (!$definition->hasDescription()) {
            if ($associationName) {
                $associationDescription = $this->getAssociationDescription($associationName);
                $this->setDescriptionForSubresource(
                    $definition,
                    $associationDescription,
                    $targetAction,
                    $isCollection
                );
            } else {
                $entityDescription = $this->getEntityDescription($entityClass, $isCollection);
                if ($entityDescription) {
                    $this->setDescriptionForResource($definition, $targetAction, $entityDescription);
                }
            }
        } else {
            $description = $definition->getDescription();
            if ($description instanceof Label) {
                $definition->setDescription($this->trans($description));
            }
        }
        if (!$definition->hasDocumentation()) {
            if ($associationName) {
                if (false === $associationDescription) {
                    $associationDescription = $this->getAssociationDescription($associationName);
                }
                $this->setDocumentationForSubresource(
                    $definition,
                    $associationDescription,
                    $targetAction,
                    $isCollection
                );
            } else {
                if (false === $entityDescription) {
                    $entityDescription = $this->getEntityDescription($entityClass, $isCollection);
                }
                if ($entityDescription) {
                    $this->setDocumentationForResource($definition, $targetAction, $entityDescription);
                }
            }
        } else {
            $documentation = $definition->getDocumentation();
            $loadedDocumentation = $this->loadDocumentation($entityClass, 'actions', $documentation, $targetAction);
            if ($loadedDocumentation) {
                $definition->setDocumentation($loadedDocumentation);
            }
        }
    }


    /**
     * @param string $className
     * @param string $section
     * @param string $resourceLink
     * @param string $position
     *
     * @return mixed|string
     */
    public function loadDocumentation($className, $section, $resourceLink = '', $position = '')
    {
        if ($resourceLink) {
            $extensionPosition = strpos($resourceLink, '.yml');
            if ($extensionPosition) {
                $filePath = substr($resourceLink, 0, $extensionPosition + 4);
                $filePath = $this->fileLocator->locate($filePath);
                $anchor = $extensionPosition + 4 < strlen($resourceLink)
                    ? substr($resourceLink, $extensionPosition + 5)
                    : null;

                $position = $anchor ? : $position;

                if (!array_key_exists($filePath, $this->loadedDocumentation)) {
                    $configValues = Yaml::parse(file_get_contents($filePath));
                    $this->loadedDocumentation[$filePath] = $configValues;
                } else {
                    $configValues = $this->loadedDocumentation[$filePath];
                }

                if ($position && array_key_exists($position, $configValues)) {
                    return $configValues[$anchor];
                }

                if (array_key_exists('documentation', $configValues)) {
                    if (!is_array($configValues['documentation'])) {
                        return $configValues['documentation'];
                    }

                    foreach ($configValues['documentation'] as $documentationClassName => $classData) {
                        if (!array_key_exists($documentationClassName, $this->classDocumentation)) {
                            $this->classDocumentation[$documentationClassName] = $classData;
                        }
                    }
                }
            }
        }

        if (array_key_exists($className, $this->classDocumentation)) {
            $classDocumentation = $this->classDocumentation[$className];
            if (array_key_exists($section, $classDocumentation)) {
                $sectionDocumentation = $classDocumentation[$section];
                if (!is_array($sectionDocumentation)) {
                    return $sectionDocumentation;
                }

                if (array_key_exists($position, $sectionDocumentation)) {
                    return $sectionDocumentation[$position];
                }
            }
        }

        return '';
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $targetAction
     * @param string                 $entityDescription
     */
    protected function setDescriptionForResource(
        EntityDefinitionConfig $definition,
        $targetAction,
        $entityDescription
    ) {
        $description = $this->resourceDocProvider->getResourceDescription($targetAction, $entityDescription);
        if ($description) {
            $definition->setDescription($description);
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $targetAction
     * @param string                 $entityDescription
     */
    protected function setDocumentationForResource(
        EntityDefinitionConfig $definition,
        $targetAction,
        $entityDescription
    ) {
        $documentation = $this->resourceDocProvider->getResourceDocumentation($targetAction, $entityDescription);
        if ($documentation) {
            $definition->setDocumentation($documentation);
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $associationDescription
     * @param string                 $targetAction
     * @param bool                   $isCollection
     */
    protected function setDescriptionForSubresource(
        EntityDefinitionConfig $definition,
        $associationDescription,
        $targetAction,
        $isCollection
    ) {
        $description = $this->resourceDocProvider->getSubresourceDescription(
            $targetAction,
            $associationDescription,
            $isCollection
        );
        if ($description) {
            $definition->setDescription($description);
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $associationDescription
     * @param string                 $targetAction
     * @param bool                   $isCollection
     */
    protected function setDocumentationForSubresource(
        EntityDefinitionConfig $definition,
        $associationDescription,
        $targetAction,
        $isCollection
    ) {
        $documentation = $this->resourceDocProvider->getSubresourceDocumentation(
            $targetAction,
            $associationDescription,
            $isCollection
        );
        if ($documentation) {
            $definition->setDocumentation($documentation);
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $entityClass
     * @param string|null            $fieldPrefix
     */
    protected function setDescriptionsForFields(EntityDefinitionConfig $definition, $entityClass, $fieldPrefix = null)
    {
        $fields = $definition->getFields();
        foreach ($fields as $fieldName => $field) {
            if (!$field->hasDescription()) {
                // todo:add check for parent doc
                $loadedDescription = $this->loadDocumentation($entityClass, 'fields', '', $fieldName);
                if ($loadedDescription) {
                    $field->setDescription($loadedDescription);
                    continue;
                }

                $propertyPath = $field->getPropertyPath() ?: $fieldName;
                if ($fieldPrefix) {
                    $propertyPath = $fieldPrefix . $propertyPath;
                }
                $description = $this->entityDescriptionProvider->getFieldDescription($entityClass, $propertyPath);
                if ($description) {
                    $field->setDescription($description);
                }
            } else {
                $label = $field->getDescription();
                if ($label instanceof Label) {
                    $field->setDescription($this->trans($label));
                }

                // todo:add check for the link
                if (is_string($label)) {
                    $loadedDescription = $this->loadDocumentation($entityClass, 'fields', $label, $fieldName);
                    if ($loadedDescription) {
                        $field->setDescription($loadedDescription);
                        continue;
                    }
                }
            }
            $targetEntity = $field->getTargetEntity();
            if ($targetEntity && $targetEntity->hasFields()) {
                $targetClass = $field->getTargetClass();
                if ($targetClass) {
                    $this->setDescriptionsForFields($targetEntity, $targetClass);
                } else {
                    $propertyPath = $field->getPropertyPath() ?: $fieldName;
                    $this->setDescriptionsForFields($targetEntity, $entityClass, $propertyPath . '.');
                }
            }
        }
    }

    /**
     * @param FiltersConfig $filters
     * @param string        $entityClass
     */
    protected function setDescriptionsForFilters(FiltersConfig $filters, $entityClass)
    {
        $fields = $filters->getFields();
        foreach ($fields as $fieldName => $field) {
            if (!$field->hasDescription()) {
                // todo:add check for parent doc
                $loadedDescription = $this->loadDocumentation($entityClass, 'filters', '', $fieldName);
                if ($loadedDescription) {
                    $field->setDescription($loadedDescription);
                    continue;
                }
                $propertyPath = $field->getPropertyPath() ?: $fieldName;
                $description = $this->entityDescriptionProvider->getFieldDescription($entityClass, $propertyPath);
                if ($description) {
                    $field->setDescription($description);
                }
            } else {
                $description = $field->getDescription();
                if ($description instanceof Label) {
                    $field->setDescription($this->trans($description));
                }
                // todo:add check for the link
                if (is_string($description)) {
                    $loadedDescription = $this->loadDocumentation($entityClass, 'filters', $description, $fieldName);
                    if ($loadedDescription) {
                        $field->setDescription($loadedDescription);
                        continue;
                    }
                }
            }
        }
    }

    /**
     * @param string $entityClass
     * @param bool   $isCollection
     *
     * @return string|null
     */
    protected function getEntityDescription($entityClass, $isCollection)
    {
        return $isCollection
            ? $this->entityDescriptionProvider->getEntityPluralDescription($entityClass)
            : $this->entityDescriptionProvider->getEntityDescription($entityClass);
    }

    /**
     * @param string $associationName
     *
     * @return string
     */
    protected function getAssociationDescription($associationName)
    {
        return $this->entityDescriptionProvider->humanizeAssociationName($associationName);
    }

    /**
     * @param Label $label
     *
     * @return string|null
     */
    protected function trans(Label $label)
    {
        return $label->trans($this->translator) ?: null;
    }
}

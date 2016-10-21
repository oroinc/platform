<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\ApiDoc\EntityDescriptionProvider;
use Oro\Bundle\ApiBundle\ApiDoc\Parser\MarkdownApiDocParser;
use Oro\Bundle\ApiBundle\ApiDoc\ResourceDocProviderInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Config\FiltersConfig;
use Oro\Bundle\ApiBundle\Model\Label;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

/**
 * Adds human-readable descriptions for:
 * * entity
 * * fields
 * * filters
 * * identifier field
 * * "createdAt" and "updatedAt" fields
 * * ownership fields (e.g. owner, organization).
 * By performance reasons all these actions are done in one processor.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class CompleteDescriptions implements ProcessorInterface
{
    const PLACEHOLDER_INHERIT_DOC = '{@inheritdoc}';

    /** @var EntityDescriptionProvider */
    protected $entityDocProvider;

    /** @var ResourceDocProviderInterface */
    protected $resourceDocProvider;

    /** @var MarkdownApiDocParser */
    protected $apiDocParser;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var ConfigProvider */
    protected $ownershipConfigProvider;

    /**
     * @param EntityDescriptionProvider    $entityDocProvider
     * @param ResourceDocProviderInterface $resourceDocProvider
     * @param MarkdownApiDocParser         $apiDocParser
     * @param TranslatorInterface          $translator
     * @param ConfigProvider               $ownershipConfigProvider
     */
    public function __construct(
        EntityDescriptionProvider $entityDocProvider,
        ResourceDocProviderInterface $resourceDocProvider,
        MarkdownApiDocParser $apiDocParser,
        TranslatorInterface $translator,
        ConfigProvider $ownershipConfigProvider
    ) {
        $this->entityDocProvider = $entityDocProvider;
        $this->resourceDocProvider = $resourceDocProvider;
        $this->apiDocParser = $apiDocParser;
        $this->translator = $translator;
        $this->ownershipConfigProvider = $ownershipConfigProvider;
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
            $context->getAssociationName(),
            $context->getParentClassName()
        );
        $this->setDescriptionsForFields($definition, $entityClass, $targetAction);
        $this->setDescriptionForIdentifierField($definition);
        $this->setDescriptionForCreatedAtField($definition);
        $this->setDescriptionForUpdatedAtField($definition);
        $this->setDescriptionsForOwnershipFields($definition, $entityClass);
        $filters = $context->getFilters();
        if (null !== $filters) {
            $this->setDescriptionsForFilters($filters, $definition, $entityClass);
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $entityClass
     * @param string                 $targetAction
     * @param bool                   $isCollection
     * @param string                 $associationName
     * @param string                 $parentEntityClass
     */
    protected function setDescriptionForEntity(
        EntityDefinitionConfig $definition,
        $entityClass,
        $targetAction,
        $isCollection,
        $associationName,
        $parentEntityClass
    ) {
        $entityDescription = false;
        $processInheritDoc = !$associationName;

        if ($definition->hasDescription()) {
            $description = $definition->getDescription();
            if ($description instanceof Label) {
                $definition->setDescription($this->trans($description));
            }
        } else {
            if ($associationName) {
                $entityDescription = $this->getAssociationDescription($associationName);
                $this->setDescriptionForSubresource($definition, $entityDescription, $targetAction, $isCollection);
            } else {
                $entityDescription = $this->getEntityDescription($entityClass, $isCollection);
                if ($entityDescription) {
                    $this->setDescriptionForResource($definition, $targetAction, $entityDescription);
                }
            }
        }

        $this->loadDocumentationForEntity(
            $definition,
            $entityClass,
            $targetAction,
            $associationName,
            $parentEntityClass
        );
        if (!$definition->hasDocumentation()) {
            if ($associationName) {
                if (false === $entityDescription) {
                    $entityDescription = $this->getAssociationDescription($associationName);
                }
                $this->setDocumentationForSubresource($definition, $entityDescription, $targetAction, $isCollection);
            } else {
                $processInheritDoc = false;
                if (false === $entityDescription) {
                    $entityDescription = $this->getEntityDescription($entityClass, $isCollection);
                }
                if ($entityDescription) {
                    $this->setDocumentationForResource($definition, $targetAction, $entityDescription);
                }
            }
        }
        if ($processInheritDoc && $definition->hasDocumentation()) {
            $documentation = $definition->getDocumentation();
            if (false !== strpos($documentation, self::PLACEHOLDER_INHERIT_DOC)) {
                $entityDocumentation = $this->entityDocProvider->getEntityDocumentation($entityClass);
                $definition->setDocumentation(
                    str_replace(self::PLACEHOLDER_INHERIT_DOC, $entityDocumentation, $documentation)
                );
            }
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $entityClass
     * @param string                 $targetAction
     * @param string                 $associationName
     * @param string                 $parentEntityClass
     */
    protected function loadDocumentationForEntity(
        EntityDefinitionConfig $definition,
        $entityClass,
        $targetAction,
        $associationName,
        $parentEntityClass
    ) {
        $this->registerDocumentationResource($definition->getDocumentationResource());
        if (!$definition->hasDocumentation()
            || $this->registerDocumentationResource($definition->getDocumentation())
        ) {
            $documentation = $associationName
                ? $this->apiDocParser->getSubresourceDocumentation($parentEntityClass, $associationName, $targetAction)
                : $this->apiDocParser->getActionDocumentation($entityClass, $targetAction);
            if ($documentation) {
                $definition->setDocumentation($documentation);
            }
        }
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
     * @param string                 $targetAction
     * @param string|null            $fieldPrefix
     */
    protected function setDescriptionsForFields(
        EntityDefinitionConfig $definition,
        $entityClass,
        $targetAction,
        $fieldPrefix = null
    ) {
        $fields = $definition->getFields();
        foreach ($fields as $fieldName => $field) {
            $propertyPath = $this->getFieldPropertyPath($field, $fieldName, $fieldPrefix);
            if (!$field->hasDescription()) {
                $description = $this->apiDocParser->getFieldDocumentation($entityClass, $fieldName, $targetAction);
                if ($description) {
                    $field->setDescription(
                        $this->processInheritDocForField($description, $entityClass, $fieldName, $propertyPath)
                    );
                } else {
                    $description = $this->entityDocProvider->getFieldDocumentation($entityClass, $propertyPath);
                    if ($description) {
                        $field->setDescription($description);
                    }
                }
            } else {
                $description = $field->getDescription();
                if ($description instanceof Label) {
                    $field->setDescription($this->trans($description));
                } elseif ($this->registerDocumentationResource($description)) {
                    $description = $this->apiDocParser->getFieldDocumentation($entityClass, $fieldName, $targetAction);
                    if ($description) {
                        $field->setDescription(
                            $this->processInheritDocForField($description, $entityClass, $fieldName, $propertyPath)
                        );
                    }
                }
            }

            $targetEntity = $field->getTargetEntity();
            if ($targetEntity && $targetEntity->hasFields()) {
                $targetClass = $field->getTargetClass();
                if ($targetClass) {
                    $this->setDescriptionsForFields($targetEntity, $targetClass, $targetAction);
                } else {
                    $propertyPath = $field->getPropertyPath($fieldName);
                    $this->setDescriptionsForFields($targetEntity, $entityClass, $targetAction, $propertyPath . '.');
                }
            }
        }
    }

    /**
     * @param EntityDefinitionFieldConfig $field
     * @param string                      $fieldName
     * @param string|null                 $fieldPrefix
     *
     * @return string
     */
    protected function getFieldPropertyPath(
        EntityDefinitionFieldConfig $field,
        $fieldName,
        $fieldPrefix = null
    ) {
        $propertyPath = $field->getPropertyPath($fieldName);
        if ($fieldPrefix) {
            $propertyPath = $fieldPrefix . $propertyPath;
        }

        return $propertyPath;
    }

    /**
     * @param string $description
     * @param string $entityClass
     * @param string $fieldName
     * @param string $propertyPath
     *
     * @return string
     */
    protected function processInheritDocForField($description, $entityClass, $fieldName, $propertyPath)
    {
        if (false !== strpos($description, self::PLACEHOLDER_INHERIT_DOC)) {
            $commonDescription = $this->apiDocParser->getFieldDocumentation($entityClass, $fieldName);
            if (!$commonDescription) {
                $commonDescription = $this->entityDocProvider->getFieldDocumentation($entityClass, $propertyPath);
            }
            $description = str_replace(self::PLACEHOLDER_INHERIT_DOC, $commonDescription, $description);
        }

        return $description;
    }

    /**
     * @param FiltersConfig          $filters
     * @param EntityDefinitionConfig $definition
     * @param string                 $entityClass
     */
    protected function setDescriptionsForFilters(
        FiltersConfig $filters,
        EntityDefinitionConfig $definition,
        $entityClass
    ) {
        $fields = $filters->getFields();
        foreach ($fields as $fieldName => $field) {
            if (!$field->hasDescription()) {
                $description = $this->apiDocParser->getFilterDocumentation($entityClass, $fieldName);
                if ($description) {
                    $field->setDescription($description);
                } else {
                    $fieldsDefinition = $definition->getField($fieldName);
                    if ($fieldsDefinition && $fieldsDefinition->hasTargetEntity()) {
                        $field->setDescription(sprintf('Filter records by \'%s\' relationship.', $fieldName));
                    } else {
                        $field->setDescription(sprintf('Filter records by \'%s\' field.', $fieldName));
                    }
                }
            } else {
                $description = $field->getDescription();
                if ($description instanceof Label) {
                    $field->setDescription($this->trans($description));
                } else {
                    $this->registerDocumentationResource($description);
                    $description = $this->apiDocParser->getFilterDocumentation($entityClass, $fieldName);
                    if ($description) {
                        $field->setDescription($description);
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
            ? $this->entityDocProvider->getEntityPluralDescription($entityClass)
            : $this->entityDocProvider->getEntityDescription($entityClass);
    }

    /**
     * @param string $associationName
     *
     * @return string
     */
    protected function getAssociationDescription($associationName)
    {
        return $this->entityDocProvider->humanizeAssociationName($associationName);
    }

    /**
     * @param Label $label
     *
     * @return string|null
     */
    protected function trans(Label $label)
    {
        return $label->trans($this->translator) ? : null;
    }

    /**
     * @param mixed $resource
     *
     * @return bool
     */
    protected function registerDocumentationResource($resource)
    {
        return is_string($resource) && !empty($resource)
            ? $this->apiDocParser->parseDocumentationResource($resource)
            : false;
    }

    /**
     * @param EntityDefinitionConfig $definition
     */
    protected function setDescriptionForIdentifierField(EntityDefinitionConfig $definition)
    {
        $identifierFieldNames = $definition->getIdentifierFieldNames();
        if (1 !== count($identifierFieldNames)) {
            // keep descriptions for composite identifier as is
            return;
        }

        $this->updateFieldDescription(
            $definition,
            reset($identifierFieldNames),
            'The identifier of an entity'
        );
    }

    /**
     * @param EntityDefinitionConfig $definition
     */
    protected function setDescriptionForCreatedAtField(EntityDefinitionConfig $definition)
    {
        $this->updateFieldDescription(
            $definition,
            'createdAt',
            'The date and time of resource record creation'
        );
    }

    /**
     * @param EntityDefinitionConfig $definition
     */
    protected function setDescriptionForUpdatedAtField(EntityDefinitionConfig $definition)
    {
        $this->updateFieldDescription(
            $definition,
            'updatedAt',
            'The date and time of the last update of the resource record'
        );
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $entityClass
     */
    protected function setDescriptionsForOwnershipFields(EntityDefinitionConfig $definition, $entityClass)
    {
        if (!$this->ownershipConfigProvider->hasConfig($entityClass)) {
            // ownership fields are available only for configurable entities
            return;
        }

        $entityConfig = $this->ownershipConfigProvider->getConfig($entityClass);
        $this->updateOwnershipFieldDescription(
            $definition,
            $entityConfig,
            'owner_field_name',
            'An Owner record represents the ownership capabilities of the record'
        );
        $this->updateOwnershipFieldDescription(
            $definition,
            $entityConfig,
            'organization_field_name',
            'An Organization record represents a real enterprise, business, firm, '
            . 'company or another organization, to which the record belongs'
        );
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $fieldName
     * @param string                 $description
     */
    protected function updateFieldDescription(EntityDefinitionConfig $definition, $fieldName, $description)
    {
        $field = $definition->getField($fieldName);
        if (null !== $field) {
            $existingDescription = $field->getDescription();
            if (empty($existingDescription)) {
                $field->setDescription($description);
            }
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param ConfigInterface        $entityConfig
     * @param string                 $configKey
     * @param string                 $description
     */
    protected function updateOwnershipFieldDescription(
        EntityDefinitionConfig $definition,
        ConfigInterface $entityConfig,
        $configKey,
        $description
    ) {
        $propertyPath = $entityConfig->get($configKey);
        if ($propertyPath) {
            $field = $definition->findField($propertyPath, true);
            if (null !== $field) {
                $existingDescription = $field->getDescription();
                if (empty($existingDescription)) {
                    $field->setDescription($description);
                }
            }
        }
    }
}

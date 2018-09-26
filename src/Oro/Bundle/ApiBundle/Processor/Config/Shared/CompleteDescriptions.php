<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Oro\Bundle\ApiBundle\ApiDoc\EntityDescriptionProvider;
use Oro\Bundle\ApiBundle\ApiDoc\ResourceDocParserInterface;
use Oro\Bundle\ApiBundle\ApiDoc\ResourceDocParserRegistry;
use Oro\Bundle\ApiBundle\ApiDoc\ResourceDocProvider;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Config\FiltersConfig;
use Oro\Bundle\ApiBundle\Model\Label;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\InheritDocUtil;
use Oro\Bundle\ApiBundle\Util\RequestDependedTextProcessor;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Adds human-readable descriptions for:
 * * entity
 * * fields
 * * filters
 * * identifier field
 * * "createdAt" and "updatedAt" fields
 * * ownership fields such as "owner" and "organization".
 * * fields for entities represent enumerations
 * By performance reasons all these actions are done in one processor.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class CompleteDescriptions implements ProcessorInterface
{
    public const ID_DESCRIPTION           = 'The unique identifier of a resource.';
    public const CREATED_AT_DESCRIPTION   = 'The date and time of resource record creation.';
    public const UPDATED_AT_DESCRIPTION   = 'The date and time of the last update of the resource record.';
    public const OWNER_DESCRIPTION        = 'An owner record represents the ownership capabilities of the record.';
    public const ORGANIZATION_DESCRIPTION = 'An organization record represents a real enterprise, business, firm, '
    . 'company or another organization to which the users belong.';

    public const ENUM_NAME_DESCRIPTION     = 'The human readable name of the option.';
    public const ENUM_DEFAULT_DESCRIPTION  = 'Determines if this option is selected by default for new records.';
    public const ENUM_PRIORITY_DESCRIPTION = 'The order in which options are ranked. '
    . 'First appears the option with the higher number of the priority.';

    public const FIELD_FILTER_DESCRIPTION       = 'Filter records by \'%s\' field.';
    public const ASSOCIATION_FILTER_DESCRIPTION = 'Filter records by \'%s\' relationship.';

    /** @var EntityDescriptionProvider */
    private $entityDocProvider;

    /** @var ResourceDocProvider */
    private $resourceDocProvider;

    /** @var ResourceDocParserRegistry */
    private $resourceDocParserRegistry;

    /** @var TranslatorInterface */
    private $translator;

    /** @var ConfigProvider */
    private $ownershipConfigProvider;

    /** @var RequestDependedTextProcessor */
    private $requestDependedTextProcessor;

    /** @var ResourceDocParserInterface[] */
    private $resourceDocParsers = [];

    /**
     * @param EntityDescriptionProvider    $entityDocProvider
     * @param ResourceDocProvider          $resourceDocProvider
     * @param ResourceDocParserRegistry    $resourceDocParserRegistry
     * @param TranslatorInterface          $translator
     * @param ConfigProvider               $ownershipConfigProvider
     * @param RequestDependedTextProcessor $requestDependedTextProcessor
     */
    public function __construct(
        EntityDescriptionProvider $entityDocProvider,
        ResourceDocProvider $resourceDocProvider,
        ResourceDocParserRegistry $resourceDocParserRegistry,
        TranslatorInterface $translator,
        ConfigProvider $ownershipConfigProvider,
        RequestDependedTextProcessor $requestDependedTextProcessor
    ) {
        $this->entityDocProvider = $entityDocProvider;
        $this->resourceDocProvider = $resourceDocProvider;
        $this->resourceDocParserRegistry = $resourceDocParserRegistry;
        $this->translator = $translator;
        $this->ownershipConfigProvider = $ownershipConfigProvider;
        $this->requestDependedTextProcessor = $requestDependedTextProcessor;
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

        $requestType = $context->getRequestType();
        $entityClass = $context->getClassName();
        $definition = $context->getResult();
        $this->setDescriptionForEntity(
            $definition,
            $requestType,
            $entityClass,
            $targetAction,
            $context->isCollection(),
            $context->getAssociationName(),
            $context->getParentClassName()
        );
        $this->setDescriptionsForFields($definition, $requestType, $entityClass, $targetAction);
        $filters = $context->getFilters();
        if (null !== $filters) {
            $this->setDescriptionsForFilters($filters, $definition, $requestType, $entityClass);
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param RequestType            $requestType
     * @param string                 $entityClass
     * @param string                 $targetAction
     * @param bool                   $isCollection
     * @param string|null            $associationName
     * @param string|null            $parentEntityClass
     */
    private function setDescriptionForEntity(
        EntityDefinitionConfig $definition,
        RequestType $requestType,
        $entityClass,
        $targetAction,
        $isCollection,
        $associationName,
        $parentEntityClass
    ) {
        if (!$definition->hasIdentifierDescription()) {
            $definition->setIdentifierDescription(self::ID_DESCRIPTION);
        }

        $entityDescription = null;
        if ($definition->hasDescription()) {
            $description = $definition->getDescription();
            if ($description instanceof Label) {
                $definition->setDescription($this->trans($description));
            }
        } elseif ($associationName) {
            $entityDescription = $this->getAssociationDescription($associationName);
            $this->setDescriptionForSubresource($definition, $entityDescription, $targetAction, $isCollection);
        } else {
            $entityDescription = $this->getEntityDescription($entityClass, $isCollection);
            $this->setDescriptionForResource($definition, $targetAction, $entityDescription);
        }

        $this->setDocumentationForEntity(
            $definition,
            $requestType,
            $entityClass,
            $targetAction,
            $isCollection,
            $associationName,
            $parentEntityClass,
            $entityDescription
        );
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param RequestType            $requestType
     * @param string                 $entityClass
     * @param string                 $targetAction
     * @param bool                   $isCollection
     * @param string                 $associationName
     * @param string                 $parentEntityClass
     * @param string|null            $entityDescription
     */
    private function setDocumentationForEntity(
        EntityDefinitionConfig $definition,
        RequestType $requestType,
        $entityClass,
        $targetAction,
        $isCollection,
        $associationName,
        $parentEntityClass,
        $entityDescription
    ) {
        $this->registerDocumentationResources($definition, $requestType);
        $this->loadDocumentationForEntity(
            $definition,
            $requestType,
            $entityClass,
            $targetAction,
            $associationName,
            $parentEntityClass
        );
        $processInheritDoc = !$associationName;
        if (!$definition->hasDocumentation()) {
            if ($associationName) {
                if (!$entityDescription) {
                    $entityDescription = $this->getAssociationDescription($associationName);
                }
                $this->setDocumentationForSubresource($definition, $entityDescription, $targetAction, $isCollection);
            } else {
                $processInheritDoc = false;
                if (!$entityDescription) {
                    $entityDescription = $this->getEntityDescription($entityClass, $isCollection);
                }
                $this->setDocumentationForResource($definition, $targetAction, $entityDescription);
            }
        }
        if ($processInheritDoc) {
            $this->processInheritDocForEntity($definition, $entityClass);
        }

        $documentation = $definition->getDocumentation();
        if ($documentation) {
            $definition->setDocumentation($this->processRequestDependedContent($documentation, $requestType));
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param RequestType            $requestType
     */
    private function registerDocumentationResources(EntityDefinitionConfig $definition, RequestType $requestType)
    {
        $resourceDocParser = $this->getResourceDocParser($requestType);
        $documentationResources = $definition->getDocumentationResources();
        foreach ($documentationResources as $resource) {
            if (\is_string($resource) && !empty($resource)) {
                $resourceDocParser->registerDocumentationResource($resource);
            }
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $entityClass
     */
    private function processInheritDocForEntity(EntityDefinitionConfig $definition, $entityClass)
    {
        $documentation = $definition->getDocumentation();
        if (InheritDocUtil::hasInheritDoc($documentation)) {
            $entityDocumentation = $this->entityDocProvider->getEntityDocumentation($entityClass);
            $definition->setDocumentation(InheritDocUtil::replaceInheritDoc($documentation, $entityDocumentation));
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param RequestType            $requestType
     * @param string                 $entityClass
     * @param string                 $targetAction
     * @param string                 $associationName
     * @param string                 $parentEntityClass
     */
    private function loadDocumentationForEntity(
        EntityDefinitionConfig $definition,
        RequestType $requestType,
        $entityClass,
        $targetAction,
        $associationName,
        $parentEntityClass
    ) {
        if (!$definition->hasDocumentation()) {
            $resourceDocParser = $this->getResourceDocParser($requestType);
            if ($associationName) {
                $documentation = $resourceDocParser->getSubresourceDocumentation(
                    $parentEntityClass,
                    $associationName,
                    $targetAction
                );
            } else {
                $documentation = $resourceDocParser->getActionDocumentation($entityClass, $targetAction);
            }
            if ($documentation) {
                $definition->setDocumentation($documentation);
            }
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $targetAction
     * @param string            $entityDescription
     */
    private function setDescriptionForResource(
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
    private function setDocumentationForResource(
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
    private function setDescriptionForSubresource(
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
    private function setDocumentationForSubresource(
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
     * @param RequestType            $requestType
     * @param string                 $entityClass
     * @param string                 $targetAction
     * @param string|null            $fieldPrefix
     */
    private function setDescriptionsForFields(
        EntityDefinitionConfig $definition,
        RequestType $requestType,
        $entityClass,
        $targetAction,
        $fieldPrefix = null
    ) {
        $this->setDescriptionForIdentifierField($definition);

        $fields = $definition->getFields();
        foreach ($fields as $fieldName => $field) {
            if (!$field->hasDescription()) {
                $this->loadFieldDescription(
                    $field,
                    $requestType,
                    $entityClass,
                    $targetAction,
                    $fieldName,
                    $fieldPrefix
                );
            } else {
                $description = $field->getDescription();
                if ($description instanceof Label) {
                    $field->setDescription($this->trans($description));
                } elseif (InheritDocUtil::hasInheritDoc($description)) {
                    $field->setDescription(InheritDocUtil::replaceInheritDoc(
                        $description,
                        $this->getFieldDescription($entityClass, $field, $fieldName, $fieldPrefix)
                    ));
                }
            }

            $description = $field->getDescription();
            if ($description) {
                $field->setDescription($this->processRequestDependedContent($description, $requestType));
            }

            $targetEntity = $field->getTargetEntity();
            if ($targetEntity && $targetEntity->hasFields()) {
                $targetClass = $field->getTargetClass();
                $targetFieldPrefix = null;
                if (!$targetClass) {
                    $targetFieldPrefix = $field->getPropertyPath($fieldName) . '.';
                }
                $this->setDescriptionsForFields(
                    $targetEntity,
                    $requestType,
                    $entityClass,
                    $targetAction,
                    $targetFieldPrefix
                );
            }
        }

        $this->setDescriptionsForSpecialFields($definition, $entityClass);
    }

    /**
     * @param EntityDefinitionFieldConfig $field
     * @param RequestType                 $requestType
     * @param string                      $entityClass
     * @param string                      $targetAction
     * @param string                      $fieldName
     * @param string|null                 $fieldPrefix
     */
    private function loadFieldDescription(
        EntityDefinitionFieldConfig $field,
        RequestType $requestType,
        $entityClass,
        $targetAction,
        $fieldName,
        $fieldPrefix
    ) {
        $resourceDocParser = $this->getResourceDocParser($requestType);
        $description = $resourceDocParser->getFieldDocumentation($entityClass, $fieldName, $targetAction);
        if ($description) {
            if (InheritDocUtil::hasInheritDoc($description)) {
                $commonDescription = $resourceDocParser->getFieldDocumentation($entityClass, $fieldName);
                if ($commonDescription) {
                    if (InheritDocUtil::hasInheritDoc($commonDescription)) {
                        $commonDescription = InheritDocUtil::replaceInheritDoc(
                            $commonDescription,
                            $this->getFieldDescription($entityClass, $field, $fieldName, $fieldPrefix)
                        );
                    }
                } else {
                    $commonDescription = $this->getFieldDescription($entityClass, $field, $fieldName, $fieldPrefix);
                }
                $description = InheritDocUtil::replaceInheritDoc($description, $commonDescription);
            }
        } else {
            $description = $resourceDocParser->getFieldDocumentation($entityClass, $fieldName);
            if ($description) {
                if (InheritDocUtil::hasInheritDoc($description)) {
                    $description = InheritDocUtil::replaceInheritDoc(
                        $description,
                        $this->getFieldDescription($entityClass, $field, $fieldName, $fieldPrefix)
                    );
                }
            } else {
                $description = $this->getFieldDescription($entityClass, $field, $fieldName, $fieldPrefix);
            }
        }
        if ($description) {
            $field->setDescription($description);
        }
    }

    /**
     * @param string                      $entityClass
     * @param EntityDefinitionFieldConfig $field
     * @param string                      $fieldName
     * @param string|null                 $fieldPrefix
     *
     * @return string|null
     */
    private function getFieldDescription(
        $entityClass,
        EntityDefinitionFieldConfig $field,
        $fieldName,
        $fieldPrefix
    ) {
        return $this->entityDocProvider->getFieldDocumentation(
            $entityClass,
            $this->getFieldPropertyPath($field, $fieldName, $fieldPrefix)
        );
    }

    /**
     * @param EntityDefinitionFieldConfig $field
     * @param string                      $fieldName
     * @param string|null                 $fieldPrefix
     *
     * @return string
     */
    private function getFieldPropertyPath(
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
     * @param FiltersConfig          $filters
     * @param EntityDefinitionConfig $definition
     * @param RequestType            $requestType
     * @param string                 $entityClass
     */
    private function setDescriptionsForFilters(
        FiltersConfig $filters,
        EntityDefinitionConfig $definition,
        RequestType $requestType,
        $entityClass
    ) {
        $resourceDocParser = $this->getResourceDocParser($requestType);
        $fields = $filters->getFields();
        foreach ($fields as $fieldName => $field) {
            if (!$field->hasDescription()) {
                $description = $resourceDocParser->getFilterDocumentation($entityClass, $fieldName);
                if ($description) {
                    if (InheritDocUtil::hasInheritDoc($description)) {
                        $description = InheritDocUtil::replaceInheritDoc(
                            $description,
                            $this->getFilterDefaultDescription($fieldName, $definition->getField($fieldName))
                        );
                    }
                    $field->setDescription($description);
                } else {
                    $field->setDescription(
                        $this->getFilterDefaultDescription($fieldName, $definition->getField($fieldName))
                    );
                }
            } else {
                $description = $field->getDescription();
                if ($description instanceof Label) {
                    $field->setDescription($this->trans($description));
                }
            }

            $description = $field->getDescription();
            if ($description) {
                $field->setDescription($this->processRequestDependedContent($description, $requestType));
            }
        }
    }

    /**
     * @param string                           $fieldName
     * @param EntityDefinitionFieldConfig|null $fieldConfig
     *
     * @return string
     */
    private function getFilterDefaultDescription(string $fieldName, ?EntityDefinitionFieldConfig $fieldConfig): string
    {
        if (null !== $fieldConfig && $fieldConfig->hasTargetEntity()) {
            return \sprintf(self::ASSOCIATION_FILTER_DESCRIPTION, $fieldName);
        }

        return \sprintf(self::FIELD_FILTER_DESCRIPTION, $fieldName);
    }

    /**
     * @param string $entityClass
     * @param bool   $isCollection
     *
     * @return string
     */
    private function getEntityDescription($entityClass, $isCollection)
    {
        $entityDescription = $isCollection
            ? $this->entityDocProvider->getEntityPluralDescription($entityClass)
            : $this->entityDocProvider->getEntityDescription($entityClass);
        if (!$entityDescription) {
            $lastDelimiter = \strrpos($entityClass, '\\');
            $entityDescription = false === $lastDelimiter
                ? $entityClass
                : \substr($entityClass, $lastDelimiter + 1);
        }

        return $entityDescription;
    }

    /**
     * @param string $associationName
     *
     * @return string
     */
    private function getAssociationDescription($associationName)
    {
        return $this->entityDocProvider->humanizeAssociationName($associationName);
    }

    /**
     * @param Label $label
     *
     * @return string|null
     */
    private function trans(Label $label)
    {
        return $label->trans($this->translator) ?: null;
    }

    /**
     * @param EntityDefinitionConfig $definition
     */
    private function setDescriptionForIdentifierField(EntityDefinitionConfig $definition)
    {
        $identifierFieldNames = $definition->getIdentifierFieldNames();
        if (1 !== \count($identifierFieldNames)) {
            // keep descriptions for composite identifier as is
            return;
        }

        $this->updateFieldDescription(
            $definition,
            \reset($identifierFieldNames),
            self::ID_DESCRIPTION
        );
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $entityClass
     */
    private function setDescriptionsForSpecialFields(EntityDefinitionConfig $definition, $entityClass)
    {
        $this->setDescriptionForCreatedAtField($definition);
        $this->setDescriptionForUpdatedAtField($definition);
        $this->setDescriptionsForOwnershipFields($definition, $entityClass);
        if (\is_a($entityClass, AbstractEnumValue::class, true)) {
            $this->setDescriptionsForEnumFields($definition);
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     */
    private function setDescriptionForCreatedAtField(EntityDefinitionConfig $definition)
    {
        $this->updateFieldDescription(
            $definition,
            'createdAt',
            self::CREATED_AT_DESCRIPTION
        );
    }

    /**
     * @param EntityDefinitionConfig $definition
     */
    private function setDescriptionForUpdatedAtField(EntityDefinitionConfig $definition)
    {
        $this->updateFieldDescription(
            $definition,
            'updatedAt',
            self::UPDATED_AT_DESCRIPTION
        );
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $entityClass
     */
    private function setDescriptionsForOwnershipFields(EntityDefinitionConfig $definition, $entityClass)
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
            self::OWNER_DESCRIPTION
        );
        $this->updateOwnershipFieldDescription(
            $definition,
            $entityConfig,
            'organization_field_name',
            self::ORGANIZATION_DESCRIPTION
        );
    }

    /**
     * @param EntityDefinitionConfig $definition
     */
    private function setDescriptionsForEnumFields(EntityDefinitionConfig $definition)
    {
        $this->updateFieldDescription(
            $definition,
            'name',
            self::ENUM_NAME_DESCRIPTION
        );
        $this->updateFieldDescription(
            $definition,
            'default',
            self::ENUM_DEFAULT_DESCRIPTION
        );
        $this->updateFieldDescription(
            $definition,
            'priority',
            self::ENUM_PRIORITY_DESCRIPTION
        );
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $fieldName
     * @param string                 $description
     */
    private function updateFieldDescription(EntityDefinitionConfig $definition, $fieldName, $description)
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
    private function updateOwnershipFieldDescription(
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

    /**
     * @param string      $content
     * @param RequestType $requestType
     *
     * @return string
     */
    private function processRequestDependedContent($content, RequestType $requestType)
    {
        return $this->requestDependedTextProcessor->process($content, $requestType);
    }

    /**
     * @param RequestType $requestType
     *
     * @return ResourceDocParserInterface
     */
    private function getResourceDocParser(RequestType $requestType)
    {
        $cacheKey = (string)$requestType;
        if (isset($this->resourceDocParsers[$cacheKey])) {
            return $this->resourceDocParsers[$cacheKey];
        }

        $parser = $this->resourceDocParserRegistry->getParser($requestType);
        $this->resourceDocParsers[$cacheKey] = $parser;

        return $parser;
    }
}

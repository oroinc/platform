<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDescriptions;

use Oro\Bundle\ApiBundle\ApiDoc\EntityDescriptionProvider;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Model\Label;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\InheritDocUtil;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The helper that is used to set descriptions of fields,
 * including special fields such as "createdAt", "updatedAt", "owner", "organizations"
 * and fields for entities based on AbstractEnumValue.
 */
class FieldsDescriptionHelper
{
    private const CREATED_AT_DESCRIPTION = 'The date and time of resource record creation.';
    private const UPDATED_AT_DESCRIPTION = 'The date and time of the last update of the resource record.';
    private const OWNER_DESCRIPTION = 'An owner record represents the ownership capabilities of the record.';
    private const ORGANIZATION_DESCRIPTION = 'An organization record represents a real enterprise, business, firm,'
    . ' company or another organization to which the users belong.';
    private const ENUM_NAME_DESCRIPTION = 'The human readable name of the option.';
    private const ENUM_DEFAULT_DESCRIPTION = 'Determines if this option is selected by default for new records.';
    private const ENUM_PRIORITY_DESCRIPTION = 'The order in which options are ranked.'
    . ' First appears the option with the higher number of the priority.';

    private EntityDescriptionProvider $entityDescriptionProvider;
    private TranslatorInterface $translator;
    private ResourceDocParserProvider $resourceDocParserProvider;
    private DescriptionProcessor $descriptionProcessor;
    private IdentifierDescriptionHelper $identifierDescriptionHelper;
    private ConfigProvider $ownershipConfigProvider;

    public function __construct(
        EntityDescriptionProvider $entityDescriptionProvider,
        TranslatorInterface $translator,
        ResourceDocParserProvider $resourceDocParserProvider,
        DescriptionProcessor $descriptionProcessor,
        IdentifierDescriptionHelper $identifierDescriptionHelper,
        ConfigProvider $ownershipConfigProvider
    ) {
        $this->entityDescriptionProvider = $entityDescriptionProvider;
        $this->translator = $translator;
        $this->resourceDocParserProvider = $resourceDocParserProvider;
        $this->descriptionProcessor = $descriptionProcessor;
        $this->identifierDescriptionHelper = $identifierDescriptionHelper;
        $this->ownershipConfigProvider = $ownershipConfigProvider;
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function setDescriptionsForFields(
        EntityDefinitionConfig $definition,
        RequestType $requestType,
        string $entityClass,
        bool $isInherit,
        string $targetAction,
        string $fieldPrefix = null
    ): void {
        $entityConfig = $this->getEntityConfig($entityClass);
        $identifierFieldName = $this->getIdentifierFieldName($definition);
        $fields = $definition->getFields();
        foreach ($fields as $fieldName => $field) {
            if ($isInherit || !$field->hasDescription()) {
                $description = $this->getDescriptionOfField(
                    $entityConfig,
                    $field,
                    $requestType,
                    $entityClass,
                    $targetAction,
                    $fieldName,
                    $fieldPrefix,
                    $fieldName === $identifierFieldName ? IdentifierDescriptionHelper::ID_DESCRIPTION : null
                );
                if ($description) {
                    $field->setDescription($description);
                }
            } else {
                $description = $field->getDescription();
                if ($description instanceof Label) {
                    $field->setDescription($this->trans($description));
                } elseif (InheritDocUtil::hasInheritDoc($description)) {
                    $field->setDescription(InheritDocUtil::replaceInheritDoc(
                        $description,
                        $fieldName === $identifierFieldName
                            ? IdentifierDescriptionHelper::ID_DESCRIPTION
                            : $this->getFieldDescription($entityClass, $entityConfig, $field, $fieldName, $fieldPrefix)
                    ));
                }
            }

            $description = $field->getDescription();
            if ($description) {
                if (InheritDocUtil::hasDescriptionInheritDoc($description)) {
                    $description = InheritDocUtil::replaceDescriptionInheritDoc(
                        $description,
                        $this->getFieldDescription($entityClass, $entityConfig, $field, $fieldName, $fieldPrefix)
                    );
                }
                $field->setDescription($this->descriptionProcessor->process($description, $requestType));
            }

            $targetEntity = $field->getTargetEntity();
            if ($targetEntity && $targetEntity->hasFields()) {
                $targetClass = $field->getTargetClass();
                $targetFieldPrefix = null;
                if (!$targetClass) {
                    $targetFieldPrefix = $this->resolveFieldName($fieldName, $field) . ConfigUtil::PATH_DELIMITER;
                }
                $this->setDescriptionsForFields(
                    $targetEntity,
                    $requestType,
                    $entityClass,
                    $isInherit,
                    $targetAction,
                    $targetFieldPrefix
                );
            }
        }

        $this->identifierDescriptionHelper->setDescriptionForIdentifierField(
            $definition,
            $entityClass,
            $targetAction
        );
        $this->setDescriptionForCreatedAtField($definition, $targetAction);
        $this->setDescriptionForUpdatedAtField($definition, $targetAction);
        if (is_a($entityClass, AbstractEnumValue::class, true)) {
            $this->setDescriptionsForEnumFields($definition);
        }
    }

    private function getIdentifierFieldName(EntityDefinitionConfig $definition): ?string
    {
        $identifierFieldNames = $definition->getIdentifierFieldNames();
        if (\count($identifierFieldNames) !== 1) {
            return null;
        }

        return $definition->findFieldNameByPropertyPath(reset($identifierFieldNames));
    }

    private function resolveFieldName(string $fieldName, EntityDefinitionFieldConfig $field): string
    {
        $propertyPath = $field->getPropertyPath();
        if ($propertyPath && ConfigUtil::IGNORE_PROPERTY_PATH !== $propertyPath) {
            $fieldName = $propertyPath;
        }

        return $fieldName;
    }

    private function setDescriptionForCreatedAtField(EntityDefinitionConfig $definition, string $targetAction): void
    {
        FieldDescriptionUtil::updateFieldDescription(
            $definition,
            'createdAt',
            self::CREATED_AT_DESCRIPTION
        );
        FieldDescriptionUtil::updateReadOnlyFieldDescription($definition, 'createdAt', $targetAction);
    }

    private function setDescriptionForUpdatedAtField(EntityDefinitionConfig $definition, string $targetAction): void
    {
        FieldDescriptionUtil::updateFieldDescription(
            $definition,
            'updatedAt',
            self::UPDATED_AT_DESCRIPTION
        );
        FieldDescriptionUtil::updateReadOnlyFieldDescription($definition, 'updatedAt', $targetAction);
    }

    private function setDescriptionsForEnumFields(EntityDefinitionConfig $definition): void
    {
        FieldDescriptionUtil::updateFieldDescription(
            $definition,
            'name',
            self::ENUM_NAME_DESCRIPTION
        );
        FieldDescriptionUtil::updateFieldDescription(
            $definition,
            'default',
            self::ENUM_DEFAULT_DESCRIPTION
        );
        FieldDescriptionUtil::updateFieldDescription(
            $definition,
            'priority',
            self::ENUM_PRIORITY_DESCRIPTION
        );
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function getDescriptionOfField(
        ?ConfigInterface $entityConfig,
        EntityDefinitionFieldConfig $field,
        RequestType $requestType,
        string $entityClass,
        string $targetAction,
        string $fieldName,
        ?string $fieldPrefix,
        ?string $fieldDescriptionReplacement
    ): ?string {
        $resourceDocParser = $this->resourceDocParserProvider->getResourceDocParser($requestType);
        $description = $resourceDocParser->getFieldDocumentation($entityClass, $fieldName, $targetAction);
        if ($description) {
            if (InheritDocUtil::hasInheritDoc($description)) {
                $fieldDescription = $fieldDescriptionReplacement;
                if (!$fieldDescription) {
                    $fieldDescription = $this->getFieldDescription(
                        $entityClass,
                        $entityConfig,
                        $field,
                        $fieldName,
                        $fieldPrefix
                    );
                }
                $commonDescription = $field->getDescription()
                    ?: $resourceDocParser->getFieldDocumentation($entityClass, $fieldName);
                if ($commonDescription) {
                    if (InheritDocUtil::hasInheritDoc($commonDescription)) {
                        $commonDescription = InheritDocUtil::replaceInheritDoc($commonDescription, $fieldDescription);
                    }
                } else {
                    $commonDescription = $fieldDescription;
                }
                $description = InheritDocUtil::replaceInheritDoc($description, $commonDescription);
            }
        } else {
            $description = $resourceDocParser->getFieldDocumentation($entityClass, $fieldName);
            if ($description) {
                if (InheritDocUtil::hasInheritDoc($description)) {
                    $fieldDescription = $fieldDescriptionReplacement;
                    if (!$fieldDescription) {
                        $fieldDescription = $this->getFieldDescription(
                            $entityClass,
                            $entityConfig,
                            $field,
                            $fieldName,
                            $fieldPrefix
                        );
                    }
                    $description = InheritDocUtil::replaceInheritDoc($description, $fieldDescription);
                }
            } else {
                $description = $this->getFieldDescription(
                    $entityClass,
                    $entityConfig,
                    $field,
                    $fieldName,
                    $fieldPrefix
                );
                if ($description && $fieldDescriptionReplacement) {
                    $description = $fieldDescriptionReplacement;
                }
            }
        }

        return $description;
    }

    private function getFieldDescription(
        string $entityClass,
        ?ConfigInterface $entityConfig,
        EntityDefinitionFieldConfig $field,
        string $fieldName,
        ?string $fieldPrefix
    ): ?string {
        $propertyPath = $field->getPropertyPath($fieldName);
        if ($fieldPrefix) {
            $propertyPath = $fieldPrefix . $propertyPath;
        } elseif (null !== $entityConfig) {
            if ($entityConfig->get('owner_field_name') === $propertyPath) {
                return self::OWNER_DESCRIPTION;
            }
            if ($entityConfig->get('organization_field_name') === $propertyPath) {
                return self::ORGANIZATION_DESCRIPTION;
            }
        }

        return $this->entityDescriptionProvider->getFieldDocumentation($entityClass, $propertyPath);
    }

    private function trans(Label $label): ?string
    {
        return $label->trans($this->translator) ?: null;
    }

    private function getEntityConfig(string $entityClass): ?ConfigInterface
    {
        return $this->ownershipConfigProvider->hasConfig($entityClass)
            ? $this->ownershipConfigProvider->getConfig($entityClass)
            : null;
    }
}

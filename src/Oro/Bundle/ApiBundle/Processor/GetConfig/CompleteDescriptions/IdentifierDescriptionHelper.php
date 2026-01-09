<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDescriptions;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * The helper that is used to set descriptions of an API resource identifier and an entity identifier field.
 */
class IdentifierDescriptionHelper
{
    public const ID_DESCRIPTION = 'The unique identifier of a resource.';
    private const REQUIRED_ID_DESCRIPTION = '<p>' . self::ID_DESCRIPTION . '</p>' . self::REQUIRED_ID_NOTE;
    private const REQUIRED_ID_DESCRIPTION_TEMPLATE = '<p>%s</p>' . self::REQUIRED_ID_NOTE;
    private const REQUIRED_ID_NOTE = '<p><strong>The required field.</strong></p>';

    private DoctrineHelper $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    public function setDescriptionForEntityIdentifier(EntityDefinitionConfig $definition): void
    {
        if (!$definition->hasIdentifierDescription()) {
            $definition->setIdentifierDescription(self::ID_DESCRIPTION);
        }
    }

    public function setDescriptionForIdentifierField(
        EntityDefinitionConfig $definition,
        string $entityClass,
        string $targetAction
    ): void {
        $identifierFieldNames = $definition->getIdentifierFieldNames();
        if (\count($identifierFieldNames) !== 1) {
            // keep descriptions for composite identifier as is
            return;
        }

        $identifierFieldName = reset($identifierFieldNames);
        $identifierField = $definition->getField($identifierFieldName);
        if (null !== $identifierField) {
            if (!$identifierField->getDescription()) {
                $identifierField->setDescription(
                    $this->getDescriptionForIdentifierField(
                        $definition,
                        $entityClass,
                        $identifierFieldName,
                        $targetAction
                    )
                );
            } elseif (
                ApiAction::UPDATE === $targetAction
                && self::ID_DESCRIPTION === $identifierField->getDescription()
            ) {
                $identifierField->setDescription(self::REQUIRED_ID_DESCRIPTION);
            }
        }
    }

    private function getDescriptionForIdentifierField(
        EntityDefinitionConfig $definition,
        string $entityClass,
        string $identifierFieldName,
        string $targetAction
    ): string {
        $required = false;
        if (ApiAction::UPDATE === $targetAction) {
            $required = true;
        } elseif (
            ApiAction::CREATE === $targetAction
            && !$this->hasIdentifierGenerator($entityClass, $identifierFieldName)
        ) {
            $required = true;
        }

        if ($definition->hasIdentifierDescription()) {
            return $required
                ? sprintf(self::REQUIRED_ID_DESCRIPTION_TEMPLATE, $definition->getIdentifierDescription())
                : $definition->getIdentifierDescription();
        }

        return $required
            ? self::REQUIRED_ID_DESCRIPTION
            : self::ID_DESCRIPTION;
    }

    private function hasIdentifierGenerator(string $entityClass, string $identifierFieldName): bool
    {
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            return false;
        }

        $classMetadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);
        if (!$classMetadata->usesIdGenerator()) {
            return false;
        }

        $entityIdentifierFieldNames = $classMetadata->getIdentifierFieldNames();

        return
            \count($entityIdentifierFieldNames) === 1
            && reset($entityIdentifierFieldNames) === $identifierFieldName;
    }
}

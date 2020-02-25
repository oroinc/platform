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

    private const REQUIRED_ID_DESCRIPTION =
        '<p>' . self::ID_DESCRIPTION . '</p><p><strong>The required field.</strong></p>';

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param EntityDefinitionConfig $definition
     */
    public function setDescriptionForEntityIdentifier(EntityDefinitionConfig $definition): void
    {
        if (!$definition->hasIdentifierDescription()) {
            $definition->setIdentifierDescription(self::ID_DESCRIPTION);
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $entityClass
     * @param string                 $targetAction
     */
    public function setDescriptionForIdentifierField(
        EntityDefinitionConfig $definition,
        string $entityClass,
        string $targetAction
    ): void {
        $identifierFieldNames = $definition->getIdentifierFieldNames();
        if (count($identifierFieldNames) !== 1) {
            // keep descriptions for composite identifier as is
            return;
        }

        $identifierFieldName = reset($identifierFieldNames);
        $identifierField = $definition->getField($identifierFieldName);
        if (null !== $identifierField) {
            if (!$identifierField->getDescription()) {
                $identifierField->setDescription(
                    $this->getDescriptionForIdentifierField($entityClass, $identifierFieldName, $targetAction)
                );
            } elseif (ApiAction::UPDATE === $targetAction
                && self::ID_DESCRIPTION === $identifierField->getDescription()
            ) {
                $identifierField->setDescription(self::REQUIRED_ID_DESCRIPTION);
            }
        }
    }

    /**
     * @param string $entityClass
     * @param string $identifierFieldName
     * @param string $targetAction
     *
     * @return string
     */
    private function getDescriptionForIdentifierField(
        string $entityClass,
        string $identifierFieldName,
        string $targetAction
    ): string {
        if (ApiAction::UPDATE === $targetAction) {
            return self::REQUIRED_ID_DESCRIPTION;
        }
        if (ApiAction::CREATE === $targetAction
            && !$this->hasIdentifierGenerator($entityClass, $identifierFieldName)
        ) {
            return self::REQUIRED_ID_DESCRIPTION;
        }

        return self::ID_DESCRIPTION;
    }

    /**
     * @param string $entityClass
     * @param string $identifierFieldName
     *
     * @return bool
     */
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
            count($entityIdentifierFieldNames) === 1
            && reset($entityIdentifierFieldNames) === $identifierFieldName;
    }
}

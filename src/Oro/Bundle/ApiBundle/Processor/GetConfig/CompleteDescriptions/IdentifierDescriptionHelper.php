<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDescriptions;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;

/**
 * The helper that is used to set descriptions of an API resource identifier and an entity identifier field.
 */
class IdentifierDescriptionHelper
{
    private const ID_DESCRIPTION = 'The unique identifier of a resource.';

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
     */
    public function setDescriptionForIdentifierField(EntityDefinitionConfig $definition): void
    {
        $identifierFieldNames = $definition->getIdentifierFieldNames();
        if (1 !== \count($identifierFieldNames)) {
            // keep descriptions for composite identifier as is
            return;
        }

        FieldDescriptionUtil::updateFieldDescription(
            $definition,
            \reset($identifierFieldNames),
            self::ID_DESCRIPTION
        );
    }
}

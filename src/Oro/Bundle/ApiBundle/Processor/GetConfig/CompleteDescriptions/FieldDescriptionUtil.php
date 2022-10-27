<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDescriptions;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Request\ApiAction;

/**
 * Provides a set of static methods to simplify building field descriptions.
 */
class FieldDescriptionUtil
{
    public const MODIFY_READ_ONLY_FIELD_DESCRIPTION = '**The read-only field. A passed value will be ignored.**';

    public static function updateFieldDescription(
        EntityDefinitionConfig $definition,
        string $fieldName,
        string $description
    ): void {
        $field = $definition->getField($fieldName);
        if (null !== $field) {
            $existingDescription = $field->getDescription();
            if (!$existingDescription) {
                $field->setDescription($description);
            }
        }
    }

    public static function updateReadOnlyFieldDescription(
        EntityDefinitionConfig $definition,
        string $fieldName,
        string $targetAction
    ): void {
        if (ApiAction::CREATE !== $targetAction && ApiAction::UPDATE !== $targetAction) {
            return;
        }

        $field = $definition->getField($fieldName);
        if (null === $field) {
            return;
        }

        $formOptions = $field->getFormOptions();
        if ($formOptions && isset($formOptions['mapped']) && !$formOptions['mapped']) {
            $existingDescription = $field->getDescription();
            if (!empty($existingDescription)
                && !str_contains($existingDescription, self::MODIFY_READ_ONLY_FIELD_DESCRIPTION)
            ) {
                $field->setDescription($existingDescription . "\n\n" . self::MODIFY_READ_ONLY_FIELD_DESCRIPTION);
            }
        }
    }
}

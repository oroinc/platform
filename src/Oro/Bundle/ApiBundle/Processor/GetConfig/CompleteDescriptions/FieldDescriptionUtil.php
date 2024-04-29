<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDescriptions;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Request\ApiAction;

/**
 * Provides a set of static methods to simplify building field descriptions.
 */
class FieldDescriptionUtil
{
    private const READ_ONLY_FIELD_NOTE = '<strong>The read-only field. A passed value will be ignored.</strong>';

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
            if ($existingDescription) {
                $field->setDescription(self::addReadOnlyFieldNote($existingDescription));
            }
        }
    }

    public static function addFieldNote(string $description, string $note): string
    {
        if (str_contains($description, '</p>')) {
            return $description . "<p>" . $note . '</p>';
        }

        return '<p>' . $description . "</p><p>" . $note . '</p>';
    }

    public static function addReadOnlyFieldNote(string $description): string
    {
        if (str_contains($description, self::READ_ONLY_FIELD_NOTE)) {
            return $description;
        }

        return self::addFieldNote($description, self::READ_ONLY_FIELD_NOTE);
    }
}

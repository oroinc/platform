<?php

namespace Oro\Bundle\WorkflowBundle\Field;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;

/**
 * Provides detailed information about fields for a specific entity taking into account workflow context.
 */
class FieldProvider extends EntityFieldProvider
{
    /**
     * {@inheritdoc}
     */
    protected function addEntityFields(array &$result, string $className, int $options): void
    {
        // Exclusions are not used in workflow.
        parent::addEntityFields($result, $className, $options &~ EntityFieldProvider::OPTION_APPLY_EXCLUSIONS);

        $metadata = $this->getMetadataFor($className);

        // add single association fields
        foreach ($metadata->getAssociationNames() as $associationName) {
            if ($this->isIgnoredInWorkflow($metadata, $associationName)) {
                continue;
            }

            if (isset($result[$associationName])) {
                // skip because a field with this name is already added, it could be a virtual field
                continue;
            }

            if (!$this->entityConfigProvider->hasConfig($metadata->getName(), $associationName)) {
                // skip non configurable relation
                continue;
            }

            $label = $this->getFieldLabel($metadata, $associationName);

            $field = [
                'name' => $associationName,
                'type' => $this->getRelationFieldType($className, $associationName),
                'label' => $options & EntityFieldProvider::OPTION_TRANSLATE ? $this->translator->trans($label) : $label,
            ];

            $result[$associationName . '-field'] = $field;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function isIgnoredRelation(ClassMetadata $metadata, $associationName)
    {
        // skip workflow and collection relations
        if ($this->isIgnoredInWorkflow($metadata, $associationName)) {
            return true;
        }

        return parent::isIgnoredRelation($metadata, $associationName);
    }

    /**
     * Checks if the given relation should be ignored in workflows
     *
     * @param ClassMetadata $metadata
     * @param string        $associationName
     *
     * @return bool
     */
    protected function isIgnoredInWorkflow(ClassMetadata $metadata, $associationName)
    {
        return !$metadata->isSingleValuedAssociation($associationName);
    }
}

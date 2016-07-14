<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * Removes excluded fields and associations.
 * Renames fields and associations if their names are not correspond the configuration of entity
 * and there is the "property_path" attribute for the field.
 * For example, if the metadata has the "address_name" field, and there is the following configuration:
 * 'fields' => [
 *      'address' => ['property_path' => 'address_name']
 * ]
 * the metadata field will be renamed to "address".
 */
class NormalizeMetadata implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var MetadataContext $context */

        if (!$context->hasResult()) {
            // metadata is not loaded
            return;
        }

        $config = $context->getConfig();
        if (null === $config) {
            // a configuration does not exist
            return;
        }

        $entityMetadata = $context->getResult();
        $this->normalizeMetadata($entityMetadata, $config);
    }

    /**
     * @param EntityMetadata         $entityMetadata
     * @param EntityDefinitionConfig $definition
     */
    protected function normalizeMetadata(EntityMetadata $entityMetadata, EntityDefinitionConfig $definition)
    {
        $fields = $definition->getFields();
        foreach ($fields as $fieldName => $field) {
            if ($field->isExcluded()) {
                $entityMetadata->removeProperty($fieldName);
            } else {
                $propertyPath = $field->getPropertyPath();
                if ($propertyPath
                    && $fieldName !== $propertyPath
                    && count(ConfigUtil::explodePropertyPath($propertyPath)) === 1
                ) {
                    $entityMetadata->renameProperty($propertyPath, $fieldName);
                }
            }
        }

        if ($definition->isExcludeAll()) {
            $toRemoveFieldNames = array_diff(
                array_merge(array_keys($entityMetadata->getFields()), array_keys($entityMetadata->getAssociations())),
                array_keys($fields)
            );
            foreach ($toRemoveFieldNames as $fieldName) {
                $entityMetadata->removeProperty($fieldName);
            }
        }
    }
}

<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Bundle\ApiBundle\Config\EntityConfigInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;

/**
 * Makes sure that the sorters configuration contains all supported sorters
 * and all sorters are fully configured.
 */
class CompleteSorters extends CompleteSection
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        $this->complete($context->getSorters(), $context->getClassName(), $context->getResult());
    }

    /**
     * {@inheritdoc}
     */
    protected function completeFields(
        EntityConfigInterface $section,
        $entityClass,
        EntityDefinitionConfig $definition
    ) {
        $metadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);

        $this->completeSorters(
            $section,
            $definition,
            $definition->getIdentifierFieldNames()
        );
        $this->completeSortersByPropertyPath(
            $section,
            $definition,
            array_keys($this->doctrineHelper->getIndexedFields($metadata))
        );
        $this->completeSortersByPropertyPath(
            $section,
            $definition,
            array_keys($this->doctrineHelper->getIndexedAssociations($metadata))
        );
    }

    /**
     * @param EntityConfigInterface  $section
     * @param EntityDefinitionConfig $definition
     * @param string[]               $fields
     */
    protected function completeSorters(
        EntityConfigInterface $section,
        EntityDefinitionConfig $definition,
        array $fields
    ) {
        foreach ($fields as $fieldName) {
            if ($definition->hasField($fieldName)) {
                $section->getOrAddField($fieldName);
            }
        }
    }

    /**
     * @param EntityConfigInterface  $section
     * @param EntityDefinitionConfig $definition
     * @param string[]               $fields
     */
    protected function completeSortersByPropertyPath(
        EntityConfigInterface $section,
        EntityDefinitionConfig $definition,
        array $fields
    ) {
        foreach ($fields as $propertyPath) {
            $fieldName = $definition->findFieldNameByPropertyPath($propertyPath);
            if ($fieldName) {
                $section->getOrAddField($fieldName);
            }
        }
    }
}

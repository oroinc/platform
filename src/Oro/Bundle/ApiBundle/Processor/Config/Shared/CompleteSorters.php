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

        $fields = array_merge(
            array_keys($this->doctrineHelper->getIndexedFields($metadata)),
            array_keys($this->doctrineHelper->getIndexedAssociations($metadata))
        );
        foreach ($fields as $propertyPath) {
            $fieldName = $definition->findFieldNameByPropertyPath($propertyPath);
            if ($fieldName) {
                $section->getOrAddField($fieldName);
            }
        }
    }
}

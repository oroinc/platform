<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\DataTransformer\DataTransformerRegistry;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

class SetDataTransformers implements ProcessorInterface
{
    /** @var DataTransformerRegistry */
    protected $dataTransformerRegistry;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param DataTransformerRegistry $dataTransformerRegistry
     * @param DoctrineHelper          $doctrineHelper
     */
    public function __construct(
        DataTransformerRegistry $dataTransformerRegistry,
        DoctrineHelper $doctrineHelper
    ) {
        $this->dataTransformerRegistry = $dataTransformerRegistry;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        $definition = $context->getResult();
        if (!$definition->isExcludeAll() || !$definition->hasFields()) {
            // expected completed configs
            return;
        }

        $this->setDataTransformers($definition, $context);
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param ConfigContext          $context
     */
    protected function setDataTransformers(EntityDefinitionConfig $definition, ConfigContext $context)
    {
        $metadata = $this->doctrineHelper->getEntityMetadataForClass($context->getClassName(), false);
        $fields = $definition->getFields();
        foreach ($fields as $fieldName => $field) {
            if ($field->hasDataTransformers()) {
                continue;
            }
            $dataType = $field->getDataType();
            if (!$dataType && $metadata) {
                $propertyPath = $field->getPropertyPath() ?: $fieldName;
                if ($metadata->hasField($propertyPath)) {
                    $dataType = $metadata->getTypeOfField($propertyPath);
                }
            }
            $dataTransformer = $this->dataTransformerRegistry->getDataTransformer($dataType);
            if (null !== $dataTransformer) {
                $field->addDataTransformer($dataTransformer);
            }
        }
    }
}

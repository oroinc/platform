<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetConfig;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\DataTransformer\DataTransformerRegistry;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * Registers data transformers for fields which data type requires
 * an additional transformation, e.g. DateTime, Date, Time, etc.
 */
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

        $this->setDataTransformers(
            $definition,
            $this->doctrineHelper->getEntityMetadataForClass($context->getClassName(), false)
        );
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param ClassMetadata|null     $metadata
     */
    protected function setDataTransformers(EntityDefinitionConfig $definition, ClassMetadata $metadata = null)
    {
        $fields = $definition->getFields();
        foreach ($fields as $fieldName => $field) {
            if ($field->hasDataTransformers()) {
                continue;
            }

            $targetConfig = $field->getTargetEntity();
            if (null !== $targetConfig) {
                if (null !== $metadata && $targetConfig->hasFields()) {
                    $propertyPath = $field->getPropertyPath() ?: $fieldName;
                    if ($metadata->hasAssociation($propertyPath)) {
                        $this->setDataTransformers(
                            $targetConfig,
                            $this->doctrineHelper->getEntityMetadataForClass(
                                $metadata->getAssociationTargetClass($propertyPath)
                            )
                        );
                    }
                }
            } else {
                $dataType = $field->getDataType();
                if (null !== $metadata && !$dataType) {
                    $propertyPath = $field->getPropertyPath() ?: $fieldName;
                    if ($metadata->hasField($propertyPath)) {
                        $dataType = $metadata->getTypeOfField($propertyPath);
                    }
                }
                if ($dataType) {
                    $dataTransformer = $this->dataTransformerRegistry->getDataTransformer($dataType);
                    if (null !== $dataTransformer) {
                        $field->addDataTransformer($dataTransformer);
                    }
                }
            }
        }
    }
}

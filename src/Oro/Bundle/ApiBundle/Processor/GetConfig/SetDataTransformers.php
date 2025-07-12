<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\DataTransformer\DataTransformerRegistry;
use Oro\Bundle\ApiBundle\PostProcessor\PostProcessingDataTransformer;
use Oro\Bundle\ApiBundle\PostProcessor\PostProcessorRegistry;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Registers data transformers for fields which data type requires
 * an additional transformation, e.g. DateTime, Date, Time, etc.
 */
class SetDataTransformers implements ProcessorInterface
{
    private DataTransformerRegistry $dataTransformerRegistry;
    private PostProcessorRegistry $postProcessorRegistry;
    private DoctrineHelper $doctrineHelper;

    public function __construct(
        DataTransformerRegistry $dataTransformerRegistry,
        PostProcessorRegistry $postProcessorRegistry,
        DoctrineHelper $doctrineHelper
    ) {
        $this->dataTransformerRegistry = $dataTransformerRegistry;
        $this->postProcessorRegistry = $postProcessorRegistry;
        $this->doctrineHelper = $doctrineHelper;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var ConfigContext $context */

        $definition = $context->getResult();
        if (!$definition->isExcludeAll() || !$definition->hasFields()) {
            // expected completed configs
            return;
        }

        $this->setDataTransformers(
            $definition,
            $context->getRequestType(),
            $this->doctrineHelper->getEntityMetadataForClass($context->getClassName(), false)
        );
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function setDataTransformers(
        EntityDefinitionConfig $definition,
        RequestType $requestType,
        ?ClassMetadata $metadata = null
    ): void {
        $fields = $definition->getFields();
        foreach ($fields as $fieldName => $field) {
            if (!$field->hasDataTransformers()) {
                $targetConfig = $field->getTargetEntity();
                if (null === $targetConfig) {
                    $dataType = $this->getDataType($fieldName, $field, $metadata);
                    if ($dataType) {
                        $dataTransformer = $this->dataTransformerRegistry->getDataTransformer($dataType, $requestType);
                        if (null !== $dataTransformer) {
                            $field->addDataTransformer($dataTransformer);
                        }
                    }
                } elseif ($targetConfig->hasFields()) {
                    $propertyPath = $field->getPropertyPath($fieldName);
                    if (null !== $metadata && $metadata->hasAssociation($propertyPath)) {
                        $this->setDataTransformers(
                            $targetConfig,
                            $requestType,
                            $this->doctrineHelper->getEntityMetadataForClass(
                                $metadata->getAssociationTargetClass($propertyPath)
                            )
                        );
                    } else {
                        $this->setDataTransformers($targetConfig, $requestType);
                    }
                }
            }
            $postProcessorType = $field->getPostProcessor();
            if ($postProcessorType) {
                $postProcessor = $this->postProcessorRegistry->getPostProcessor($postProcessorType, $requestType);
                if (null !== $postProcessor) {
                    $field->addDataTransformer(new PostProcessingDataTransformer(
                        $postProcessor,
                        $field->getPostProcessorOptions() ?? []
                    ));
                }
            }
        }
    }

    private function getDataType(
        string $fieldName,
        EntityDefinitionFieldConfig $field,
        ?ClassMetadata $metadata
    ): ?string {
        $dataType = $field->getDataType();
        if (!$dataType && null !== $metadata) {
            $dataType = $metadata->getTypeOfField($field->getPropertyPath($fieldName));
        }
        if ($dataType) {
            $dataTypeDetailDelimiterPos = strpos($dataType, DataType::DETAIL_DELIMITER);
            if (false !== $dataTypeDetailDelimiterPos) {
                $dataType = substr($dataType, 0, $dataTypeDetailDelimiterPos);
            }
        }

        return $dataType;
    }
}

<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetConfig;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\DataTransformer\DataTransformerRegistry;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
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
            $context->getRequestType(),
            $this->doctrineHelper->getEntityMetadataForClass($context->getClassName(), false)
        );
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param RequestType            $requestType
     * @param ClassMetadata|null     $metadata
     */
    protected function setDataTransformers(
        EntityDefinitionConfig $definition,
        RequestType $requestType,
        ClassMetadata $metadata = null
    ) {
        $fields = $definition->getFields();
        foreach ($fields as $fieldName => $field) {
            if ($field->hasDataTransformers()) {
                continue;
            }

            $targetConfig = $field->getTargetEntity();
            if (null === $targetConfig) {
                $dataType = $field->getDataType();
                if (!$dataType && null !== $metadata) {
                    $propertyPath = $field->getPropertyPath($fieldName);
                    if ($metadata->hasField($propertyPath)) {
                        $dataType = $metadata->getTypeOfField($propertyPath);
                    }
                }
                if ($dataType) {
                    $dataTransformer = $this->dataTransformerRegistry->getDataTransformer($dataType, $requestType);
                    if (null !== $dataTransformer) {
                        $field->addDataTransformer($dataTransformer);
                    }
                }
            } elseif ($targetConfig->hasFields() && null !== $metadata) {
                $propertyPath = $field->getPropertyPath($fieldName);
                if ($metadata->hasAssociation($propertyPath)) {
                    $this->setDataTransformers(
                        $targetConfig,
                        $requestType,
                        $this->doctrineHelper->getEntityMetadataForClass(
                            $metadata->getAssociationTargetClass($propertyPath)
                        )
                    );
                }
            }
        }
    }
}

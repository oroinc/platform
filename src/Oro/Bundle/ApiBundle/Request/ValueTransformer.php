<?php

namespace Oro\Bundle\ApiBundle\Request;

use Oro\Bundle\ApiBundle\DataTransformer\DataTransformerRegistry;
use Oro\Bundle\ApiBundle\Processor\ApiContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Component\EntitySerializer\DataTransformerInterface;

/**
 * Provides a way to convert a value to concrete data-type for API response.
 */
class ValueTransformer
{
    private DataTransformerRegistry $dataTransformerRegistry;
    private DataTransformerInterface $dataTransformer;

    public function __construct(
        DataTransformerRegistry $dataTransformerRegistry,
        DataTransformerInterface $dataTransformer
    ) {
        $this->dataTransformerRegistry = $dataTransformerRegistry;
        $this->dataTransformer = $dataTransformer;
    }

    /**
     * Converts a value to the given data-type using data transformers registered in the data transformer registry.
     *
     * @see \Oro\Bundle\ApiBundle\Processor\ApiContext::getNormalizationContext for the transformation context.
     */
    public function transformValue(mixed $value, string $dataType, array $context): mixed
    {
        if (!isset($context[ApiContext::REQUEST_TYPE])) {
            throw new \InvalidArgumentException(sprintf(
                'The transformation context must have "%s" attribute.',
                ApiContext::REQUEST_TYPE
            ));
        }

        $dataTransformer = $this->dataTransformerRegistry->getDataTransformer(
            $dataType,
            $context[ApiContext::REQUEST_TYPE]
        );
        if (null === $dataTransformer) {
            return $value;
        }

        return $this->dataTransformer->transform(
            $value,
            [ConfigUtil::DATA_TYPE => $dataType, ConfigUtil::DATA_TRANSFORMER => [$dataTransformer]],
            $context
        );
    }

    /**
     * Converts a value of the given field using data transformer(s) from "data_transformer" configuration attribute.
     *
     * @see \Oro\Bundle\ApiBundle\Processor\ApiContext::getNormalizationContext for the transformation context.
     * @see \Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig::toArray() for the field configuration. Usually
     * the $excludeTargetEntity parameter is TRUE.
     */
    public function transformFieldValue(mixed $fieldValue, array $fieldConfig, array $context): mixed
    {
        return $this->dataTransformer->transform($fieldValue, $fieldConfig, $context);
    }
}

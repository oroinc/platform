<?php

namespace Oro\Bundle\ApiBundle\Normalizer;

use Oro\Bundle\ApiBundle\DataTransformer\DataTransformerRegistry;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Normalizes an instance of \DateTimeInterface.
 */
class DateTimeNormalizer implements ObjectNormalizerInterface
{
    private DataTransformerRegistry $dataTransformerRegistry;

    public function __construct(DataTransformerRegistry $dataTransformerRegistry)
    {
        $this->dataTransformerRegistry = $dataTransformerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize(object $object, RequestType $requestType): mixed
    {
        $dataTransformer = $this->dataTransformerRegistry->getDataTransformer(DataType::DATETIME, $requestType);

        return $dataTransformer instanceof DataTransformerInterface
            ? $dataTransformer->transform($object)
            : $object;
    }
}

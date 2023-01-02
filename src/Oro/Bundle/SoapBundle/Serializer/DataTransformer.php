<?php

namespace Oro\Bundle\SoapBundle\Serializer;

use Oro\Component\EntitySerializer\DataTransformerInterface;

/**
 * Implements default transformation rules:
 * * transforms an object to a string using "__toString()" method
 * * transforms DateTime object to a string contains date in ISO 8601 format
 */
class DataTransformer implements DataTransformerInterface
{
    private DataTransformerInterface $innerDataTransformer;

    public function __construct(DataTransformerInterface $innerDataTransformer)
    {
        $this->innerDataTransformer = $innerDataTransformer;
    }

    /**
     * {@inheritDoc}
     */
    public function transform(mixed $value, array $config, array $context): mixed
    {
        $value = $this->innerDataTransformer->transform($value, $config, $context);
        if (\is_object($value)) {
            if (method_exists($value, '__toString')) {
                $value = (string)$value;
            } elseif ($value instanceof \DateTime) {
                $value = $value->format('c');
            }
        }

        return $value;
    }
}

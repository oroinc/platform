<?php

namespace Oro\Bundle\ApiBundle\DataTransformer;

/**
 * Field data transformers registry.
 * @see Oro\Component\EntitySerializer\EntityDataTransformer
 */
class DataTransformerRegistry
{
    /** @var array */
    protected $transformers = [];

    /**
     * Registers a data transformer for a given data type.
     *
     * @param string $dataType
     * @param mixed  $transformer Can be the id of a service in DIC,
     *                            an instance of "Oro\Component\EntitySerializer\DataTransformerInterface"
     *                            or "Symfony\Component\Form\DataTransformerInterface",
     *                            or function ($class, $property, $value, $config) : mixed.
     */
    public function addDataTransformer($dataType, $transformer)
    {
        $this->transformers[$dataType] = $transformer;
    }

    /**
     * Returns a data transformer for a given data type.
     *
     * @param string $dataType
     *
     * @return mixed|null Can be NULL,
     *                    the id of a service in DIC,
     *                    an instance of "Oro\Component\EntitySerializer\DataTransformerInterface"
     *                    or "Symfony\Component\Form\DataTransformerInterface",
     *                    or function ($class, $property, $value, $config) : mixed.
     */
    public function getDataTransformer($dataType)
    {
        return isset($this->transformers[$dataType])
            ? $this->transformers[$dataType]
            : null;
    }
}

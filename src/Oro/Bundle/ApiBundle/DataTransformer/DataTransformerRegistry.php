<?php

namespace Oro\Bundle\ApiBundle\DataTransformer;

class DataTransformerRegistry
{
    /** @var array */
    protected $transformers = [];

    /**
     * Registers a data transformer for a given data type.
     *
     * @param string $dataType
     * @param object $transformer
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
     * @return object|null
     */
    public function getDataTransformer($dataType)
    {
        return isset($this->transformers[$dataType])
            ? $this->transformers[$dataType]
            : null;
    }
}

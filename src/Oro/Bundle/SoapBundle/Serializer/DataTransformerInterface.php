<?php

namespace Oro\Bundle\SoapBundle\Serializer;

interface DataTransformerInterface
{
    /**
     * Prepares the given value for serialization
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public function transformValue($value);
}

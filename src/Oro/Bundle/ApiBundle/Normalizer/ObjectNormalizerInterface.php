<?php

namespace Oro\Bundle\ApiBundle\Normalizer;

use Oro\Bundle\ApiBundle\Request\RequestType;

/**
 * Provides an interface for normalizers of different kind of objects.
 */
interface ObjectNormalizerInterface
{
    /**
     * @param object      $object
     * @param RequestType $requestType
     *
     * @return mixed
     */
    public function normalize($object, RequestType $requestType);
}

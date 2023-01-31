<?php

namespace Oro\Bundle\ApiBundle\Normalizer;

use Oro\Bundle\ApiBundle\Request\RequestType;

/**
 * Provides an interface for normalizers of different kind of objects.
 */
interface ObjectNormalizerInterface
{
    public function normalize(object $object, RequestType $requestType): mixed;
}

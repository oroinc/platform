<?php

namespace Oro\Bundle\ApiBundle\Normalizer;

/**
 * An interface for normalizers of different kind of objects
 */
interface ObjectNormalizerInterface
{
    /**
     * @param object $object
     *
     * @return bool
     */
    public function supports($object);

    /**
     * @param object $object
     *
     * @return mixed
     */
    public function normalize($object);
}

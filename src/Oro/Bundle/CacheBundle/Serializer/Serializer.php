<?php

namespace Oro\Bundle\CacheBundle\Serializer;

use Symfony\Component\Serializer\Serializer as BaseSerializer;

/**
 * Override basic serializer to support iterable constructor arguments.
 */
class Serializer extends BaseSerializer
{
    /**
     * @param array|iterable $normalizers
     * @param array|iterable $encoders
     */
    public function __construct(iterable $normalizers = [], iterable $encoders = [])
    {
        if ($normalizers instanceof \IteratorAggregate) {
            $normalizers = $normalizers->getIterator();
        }
        if ($normalizers instanceof \Iterator) {
            $normalizers = iterator_to_array($normalizers);
        }

        if ($encoders instanceof \IteratorAggregate) {
            $encoders = $encoders->getIterator();
        }
        if ($encoders instanceof \Iterator) {
            $encoders = iterator_to_array($encoders);
        }

        parent::__construct($normalizers, $encoders);
    }
}

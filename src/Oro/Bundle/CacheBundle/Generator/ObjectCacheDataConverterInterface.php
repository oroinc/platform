<?php

namespace Oro\Bundle\CacheBundle\Generator;

/**
 * The main purpose of this interface is to convert any object to a string in any way.
 * This string will be used for cache generation.
 */
interface ObjectCacheDataConverterInterface
{
    /**
     * @param object $object
     * @param string $scope
     * @return string
     */
    public function convertToString($object, string $scope): string;
}

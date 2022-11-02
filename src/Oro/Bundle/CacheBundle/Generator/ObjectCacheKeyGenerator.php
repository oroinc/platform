<?php

namespace Oro\Bundle\CacheBundle\Generator;

/**
 * This is the main entry point for generating a cache key from the provided object.
 */
class ObjectCacheKeyGenerator
{
    /**
     * @var ObjectCacheDataConverterInterface
     */
    protected $converter;

    public function __construct(ObjectCacheDataConverterInterface $converter)
    {
        $this->converter = $converter;
    }

    /**
     * @param object $object
     * @param string $scope
     * @return string
     */
    public function generate($object, string $scope): string
    {
        $result = $this->converter->convertToString($object, $scope);

        return sha1(get_class($object) . $result);
    }
}

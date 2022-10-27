<?php

namespace Oro\Bundle\CacheBundle\Generator;

use Symfony\Contracts\Cache\ItemInterface;

/**
 * This is the main entry point for generating a cache key from the provided arguments.
 */
class UniversalCacheKeyGenerator
{
    private const DELIMITER = '|';

    /** @var ObjectCacheKeyGenerator */
    private $objectCacheKeyGenerator;

    public function __construct(ObjectCacheKeyGenerator $objectCacheKeyGenerator)
    {
        $this->objectCacheKeyGenerator = $objectCacheKeyGenerator;
    }

    /**
     * @param mixed $arguments Argument/arguments to generate cache key from. If $arguments is an array, then key can
     * be a scope name to use when getting cache key from corresponding object value. For example if you pass the
     * following array as argument:
     *  [
     *      'sample_scope_name' => $sampleEntity,
     *      ['sample_value11', 'sample_value12']
     *      'sample_value2',
     *      3,
     *      false
     *  ]
     * then it will pass $sampleEntity to ObjectCacheKeyGenerator with scope 'sample_scope_name' (and let's say will get
     * 'sample_key' in return), then the resulting cache key for the given array will look like the following string
     * (example is not hashed with sha1): sample_key|sample_value11|sample_value12|sample_value2|3|0
     *
     * @return string
     */
    public function generate($arguments): string
    {
        $cacheKey = is_scalar($arguments) ? $arguments : $this->getCacheKey(func_get_args());

        return sha1($cacheKey);
    }

    private function getCacheKey(array $arguments, string $scope = ''): string
    {
        $cacheKey = [];
        foreach ($arguments as $key => $argument) {
            if (is_object($argument)) {
                $cacheKey[] = $this->objectCacheKeyGenerator->generate($argument, is_numeric($key) ? $scope : $key);
            } elseif (is_array($argument)) {
                $cacheKey[] = $this->getCacheKey($argument, is_numeric($key) ? $scope : $key);
            } elseif (is_resource($argument)) {
                $cacheKey[] = (int) $argument;
            } else {
                $cacheKey[] = $argument;
            }
        }

        return implode(self::DELIMITER, $cacheKey);
    }

    /**
     * Adopt cache key to be valid for Symfony Cache adapters
     * @param string $cacheKey
     * @return string
     */
    public static function normalizeCacheKey(string $cacheKey) : string
    {
        return false !== strpbrk($cacheKey, ItemInterface::RESERVED_CHARACTERS)
            ? rawurlencode($cacheKey)
            : $cacheKey;
    }
}

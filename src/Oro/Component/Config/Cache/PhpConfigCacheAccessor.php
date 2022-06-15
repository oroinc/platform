<?php

namespace Oro\Component\Config\Cache;

use Symfony\Component\Config\ConfigCacheInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Helper class to load and write a configuration as a PHP file.
 */
class PhpConfigCacheAccessor
{
    /** @var callable|null */
    private $configValidator;

    public function __construct(callable $configValidator = null)
    {
        $this->configValidator = $configValidator;
    }

    /**
     * Loads a PHP file from the path returned by the given cache instance.
     *
     * @throws \LogicException When the cache file does not exist or has invalid content
     */
    public function load(ConfigCacheInterface $cache): mixed
    {
        $file = $cache->getPath();
        if (!file_exists($file)) {
            throw new \LogicException(sprintf('The file "%s" does not exist.', $file));
        }

        $config = require $file;
        if (null !== $this->configValidator) {
            try {
                \call_user_func($this->configValidator, $config);
            } catch (\Exception $e) {
                throw new \LogicException(sprintf(
                    'The file "%s" has not valid content. %s',
                    $file,
                    $e->getMessage()
                ));
            }
        }

        return $config;
    }

    /**
     * Saves the given config and metadata to the given cache instance.
     *
     * @throws \LogicException When the given config is invalid
     * @throws \RuntimeException When the cache file cannot be written
     */
    public function save(ConfigCacheInterface $cache, mixed $config, array $metadata = null): void
    {
        try {
            if (null === $config) {
                throw new \LogicException('Must not be NULL.');
            }
            if (null !== $this->configValidator) {
                \call_user_func($this->configValidator, $config);
            }
        } catch (\Exception $e) {
            throw new \LogicException(sprintf(
                'The config "%s" is not valid. %s',
                $cache->getPath(),
                $e->getMessage()
            ));
        }
        $cache->write(
            sprintf('<?php return %s;', var_export($config, true)),
            $metadata
        );
    }

    /**
     * Removes the given cache instance.
     */
    public function remove(ConfigCacheInterface $cache): void
    {
        $cacheFile = $cache->getPath();
        if (is_file($cacheFile)) {
            $fs = new Filesystem();
            $fs->remove($cacheFile);
        }
    }
}

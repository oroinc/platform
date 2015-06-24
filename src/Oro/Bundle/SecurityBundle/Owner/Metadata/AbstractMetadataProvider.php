<?php

namespace Oro\Bundle\SecurityBundle\Owner\Metadata;

use Doctrine\Common\Cache\CacheProvider;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

abstract class AbstractMetadataProvider implements MetadataProviderInterface
{
    /**
     * @var ConfigProvider
     */
    protected $configProvider;

    /**
     * @var CacheProvider
     */
    protected $cache;

    /**
     * @var array
     *          key = class name
     *          value = OwnershipMetadataInterface or true if an entity has no ownership config
     */
    protected $localCache = [];

    /**
     * @var OwnershipMetadataInterface
     */
    protected $noOwnershipMetadata;

    /**
     * @param array               $owningEntityNames
     * @param ConfigProvider      $configProvider
     * @param EntityClassResolver $entityClassResolver
     * @param CacheProvider|null  $cache
     */
    public function __construct(
        array $owningEntityNames,
        ConfigProvider $configProvider,
        EntityClassResolver $entityClassResolver = null,
        CacheProvider $cache = null
    ) {
        $this->setAccessLevelClasses($owningEntityNames, $entityClassResolver);
        $this->configProvider = $configProvider;
        $this->cache = $cache;
        $this->createNoOwnershipMetadata();
    }

    /**
     * @param array $owningEntityNames
     *          key = class name
     *          value = OwnershipMetadataInterface or true if an entity has no ownership config
     * @param EntityClassResolver|null $entityClassResolver
     */
    abstract protected function setAccessLevelClasses(
        array $owningEntityNames,
        EntityClassResolver $entityClassResolver = null
    );

    /**
     * Set instance of OwnershipMetadataInterface to `noOwnershipMetadata` property
     */
    abstract protected function createNoOwnershipMetadata();

    /**
     * {@inheritDoc}
     */
    public function getMetadata($className)
    {
        $this->ensureMetadataLoaded($className);

        $result = $this->localCache[$className];
        if ($result === true) {
            return $this->noOwnershipMetadata;
        }

        return $result;
    }

    /**
     * Warms up the cache
     *
     * If the class name is specified this method warms up cache for this class only
     *
     * @param string|null $className
     */
    public function warmUpCache($className = null)
    {
        if ($className === null) {
            $configs = $this->configProvider->getConfigs();
            foreach ($configs as $config) {
                $this->ensureMetadataLoaded($config->getId()->getClassName());
            }
        } else {
            $this->ensureMetadataLoaded($className);
        }
    }

    /**
     * Clears the ownership metadata cache
     *
     * If the class name is not specified this method clears all cached data
     *
     * @param string|null $className
     */
    public function clearCache($className = null)
    {
        if ($this->cache) {
            if ($className !== null) {
                $this->cache->delete($className);
            } else {
                $this->cache->deleteAll();
            }
        }
    }

    /**
     * Makes sure that metadata for the given class are loaded
     *
     * @param string $className
     *
     * @throws InvalidConfigurationException
     */
    protected function ensureMetadataLoaded($className)
    {
        if (!isset($this->localCache[$className])) {
            $data = null;
            if ($this->cache) {
                $data = $this->cache->fetch($className);
            }
            if (!$data) {
                if ($this->configProvider->hasConfig($className)) {
                    $config = $this->configProvider->getConfig($className);
                    try {
                        $data = $this->getOwnershipMetadata($config);
                    } catch (\InvalidArgumentException $ex) {
                        throw new InvalidConfigurationException(
                            sprintf('Invalid entity ownership configuration for "%s".', $className),
                            0,
                            $ex
                        );
                    }
                }
                if (!$data) {
                    $data = true;
                }

                if ($this->cache) {
                    $this->cache->save($className, $data);
                }
            }

            $this->localCache[$className] = $data;
        }
    }

    /**
     * @param ConfigInterface $config
     *
     * @return OwnershipMetadataInterface
     */
    abstract protected function getOwnershipMetadata(ConfigInterface $config);
}

<?php

namespace Oro\Bundle\SecurityBundle\Owner\Metadata;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Abstract class for providers which provide ownership metadata for entities
 */
abstract class AbstractOwnershipMetadataProvider implements OwnershipMetadataProviderInterface
{
    protected ConfigManager $configManager;
    /** [class name => OwnershipMetadataInterface or true if an entity has no ownership config, ...] */
    private array $localCache = [];
    private ?OwnershipMetadataInterface $noOwnershipMetadata = null;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    public function getMetadata($className): OwnershipMetadataInterface
    {
        if ($className === null) {
            return $this->getNoOwnershipMetadata();
        }

        $className = ClassUtils::getRealClass($className);
        $this->ensureMetadataLoaded($className);

        $result = $this->localCache[$className];
        if ($result === true) {
            return $this->getNoOwnershipMetadata();
        }

        return $result;
    }

    public function warmUpCache(?string $className = null): void
    {
        if ($className === null) {
            $configs = $this->getOwnershipConfigs();
            foreach ($configs as $config) {
                $this->ensureMetadataLoaded($config->getId()->getClassName());
            }
        } else {
            $this->ensureMetadataLoaded(ClassUtils::getRealClass($className));
        }
    }

    public function clearCache(?string $className = null): void
    {
        if ($this->getCache()) {
            if ($className !== null) {
                $this->getCache()->delete(
                    UniversalCacheKeyGenerator::normalizeCacheKey(ClassUtils::getRealClass($className))
                );
            } else {
                $this->getCache()->clear();
            }
        }
    }

    abstract protected function getCache(): CacheInterface;

    abstract protected function getOwnershipMetadata(ConfigInterface $config): OwnershipMetadataInterface;

    abstract protected function createNoOwnershipMetadata(): OwnershipMetadataInterface;

    /**
     * Gets an instance of OwnershipMetadataInterface that represents an entity that does not have an owner
     */
    protected function getNoOwnershipMetadata(): OwnershipMetadataInterface
    {
        if (!$this->noOwnershipMetadata) {
            $this->noOwnershipMetadata = $this->createNoOwnershipMetadata();
        }

        return $this->noOwnershipMetadata;
    }

    /**
     * Gets an instance of OwnershipMetadataInterface that represents a "root" ACL entry
     */
    protected function getRootMetadata(): OwnershipMetadataInterface
    {
        return $this->createRootMetadata();
    }

    protected function createRootMetadata(): OwnershipMetadataInterface
    {
        return new RootOwnershipMetadata();
    }

    protected function getOwnershipConfigs(): array #ConfigInterface[]
    {
        return $this->configManager->getConfigs('ownership');
    }

    /**
     * Makes sure that metadata for the given class are loaded
     *
     * @throws InvalidConfigurationException
     */
    protected function ensureMetadataLoaded(string $className): void
    {
        if (!isset($this->localCache[$className])) {
            $cacheKey = UniversalCacheKeyGenerator::normalizeCacheKey($className);
            $data = $this->getCache()->get($cacheKey, function () use ($className) {
                $metadata = null;
                if (ObjectIdentityFactory::ROOT_IDENTITY_TYPE === $className) {
                    $metadata = $this->getRootMetadata();
                } elseif ($this->configManager->hasConfig($className)) {
                    $config = $this->configManager->getEntityConfig('ownership', $className);
                    try {
                        $metadata = $this->getOwnershipMetadata($config);
                    } catch (\InvalidArgumentException $ex) {
                        throw new InvalidConfigurationException(
                            sprintf('Invalid entity ownership configuration for "%s".', $className),
                            0,
                            $ex
                        );
                    }
                }
                $metadata ??= true;
                return $metadata;
            });
            $this->localCache[$className] = $data;
        }
    }
}

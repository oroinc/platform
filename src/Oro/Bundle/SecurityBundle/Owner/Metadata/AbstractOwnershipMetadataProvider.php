<?php

namespace Oro\Bundle\SecurityBundle\Owner\Metadata;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * Abstract class for providers which provide ownership metadata for entities
 */
abstract class AbstractOwnershipMetadataProvider implements OwnershipMetadataProviderInterface
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var array [class name => OwnershipMetadataInterface or true if an entity has no ownership config, ...] */
    private $localCache = [];

    /** @var OwnershipMetadataInterface */
    private $noOwnershipMetadata;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritDoc}
     */
    public function getMetadata($className)
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

    /**
     * {@inheritdoc}
     */
    public function warmUpCache($className = null)
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

    /**
     * {@inheritdoc}
     */
    public function clearCache($className = null)
    {
        if ($this->getCache()) {
            if ($className !== null) {
                $this->getCache()->delete(ClassUtils::getRealClass($className));
            } else {
                $this->getCache()->deleteAll();
            }
        }
    }

    /**
     * @return CacheProvider
     */
    abstract protected function getCache();

    /**
     * @param ConfigInterface $config
     *
     * @return OwnershipMetadataInterface
     */
    abstract protected function getOwnershipMetadata(ConfigInterface $config);

    /**
     * @return OwnershipMetadataInterface
     */
    abstract protected function createNoOwnershipMetadata();

    /**
     * Gets an instance of OwnershipMetadataInterface that represents an entity that does not have an owner
     *
     * @return OwnershipMetadataInterface
     */
    protected function getNoOwnershipMetadata()
    {
        if (!$this->noOwnershipMetadata) {
            $this->noOwnershipMetadata = $this->createNoOwnershipMetadata();
        }

        return $this->noOwnershipMetadata;
    }

    /**
     * Gets an instance of OwnershipMetadataInterface that represents a "root" ACL entry
     *
     * @return OwnershipMetadataInterface
     */
    protected function getRootMetadata()
    {
        return $this->createRootMetadata();
    }

    /**
     * @return OwnershipMetadataInterface
     */
    protected function createRootMetadata()
    {
        return new RootOwnershipMetadata();
    }

    /**
     * @return ConfigInterface[]
     */
    protected function getOwnershipConfigs()
    {
        return $this->configManager->getConfigs('ownership');
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
            if ($this->getCache()) {
                $data = $this->getCache()->fetch($className);
            }
            if (!$data) {
                if (ObjectIdentityFactory::ROOT_IDENTITY_TYPE === $className) {
                    $data = $this->getRootMetadata();
                } elseif ($this->configManager->hasConfig($className)) {
                    $config = $this->configManager->getEntityConfig('ownership', $className);
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

                if ($this->getCache()) {
                    $this->getCache()->save($className, $data);
                }
            }

            $this->localCache[$className] = $data;
        }
    }
}

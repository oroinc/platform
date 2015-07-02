<?php

namespace Oro\Bundle\SecurityBundle\Owner\Metadata;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\Cache\CacheProvider;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

abstract class AbstractMetadataProvider implements MetadataProviderInterface, ContainerAwareInterface
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var EntityClassResolver
     */
    private $entityClassResolver;

    /**
     * @var array
     *          key = class name
     *          value = OwnershipMetadataInterface or true if an entity has no ownership config
     */
    protected $localCache = [];

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var array
     */
    protected $owningEntityNames = [];

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;

        $this->setAccessLevelClasses($this->owningEntityNames);
    }

    /**
     * @param array $owningEntityNames
     */
    public function __construct(array $owningEntityNames)
    {
        $this->owningEntityNames = $owningEntityNames;
    }

    /**
     * @return ConfigProvider
     */
    protected function getConfigProvider()
    {
        if (!$this->configProvider) {
            $this->configProvider = $this->getContainer()->get('oro_entity_config.provider.ownership');
        }

        return $this->configProvider;
    }

    /**
     * @return EntityClassResolver
     */
    protected function getEntityClassResolver()
    {
        if (!$this->entityClassResolver) {
            $this->entityClassResolver = $this->getContainer()->get('oro_entity.orm.entity_class_resolver');
        }

        return $this->entityClassResolver;
    }

    /**
     * @return ContainerInterface
     */
    protected function getContainer()
    {
        if (!$this->container) {
            throw new \InvalidArgumentException('ContainerInterface is not injected');
        }

        return $this->container;
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
     * Get instance of OwnershipMetadataInterface
     *
     * @return OwnershipMetadataInterface
     */
    abstract protected function getNoOwnershipMetadata();

    /**
     * @return CacheProvider
     */
    abstract protected function getCache();

    /**
     * {@inheritDoc}
     */
    public function getMetadata($className)
    {
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
            $this->ensureMetadataLoaded($className);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function clearCache($className = null)
    {
        if ($this->getCache()) {
            if ($className !== null) {
                $this->getCache()->delete($className);
            } else {
                $this->getCache()->deleteAll();
            }
        }
    }

    /**
     * @return ConfigInterface[]
     */
    protected function getOwnershipConfigs()
    {
        return $this->getConfigProvider()->getConfigs();
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
                if ($this->getConfigProvider()->hasConfig($className)) {
                    $config = $this->getConfigProvider()->getConfig($className);
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

    /**
     * @param ConfigInterface $config
     *
     * @return OwnershipMetadataInterface
     */
    abstract protected function getOwnershipMetadata(ConfigInterface $config);
}

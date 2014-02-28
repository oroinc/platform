<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;

use Oro\Bundle\EntityBundle\ORM\Query\FilterCollection;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class OroEntityManager extends EntityManager
{
    /**
     * Collection of query filters.
     *
     * @var FilterCollection
     */
    protected $filterCollection;

    /**
     * Entity config provider for "extend" scope
     *
     * @var ConfigProvider
     */
    protected $extendConfigProvider;

    public static function create($conn, Configuration $config, EventManager $eventManager = null)
    {
        if (!$config->getMetadataDriverImpl()) {
            throw ORMException::missingMappingDriverImpl();
        }

        if (is_array($conn)) {
            $conn = \Doctrine\DBAL\DriverManager::getConnection($conn, $config, ($eventManager ? : new EventManager()));
        } elseif ($conn instanceof Connection) {
            if ($eventManager !== null && $conn->getEventManager() !== $eventManager) {
                throw ORMException::mismatchedEventManager();
            }
        } else {
            throw new \InvalidArgumentException("Invalid argument: " . $conn);
        }

        return new OroEntityManager($conn, $config, $conn->getEventManager());
    }

    /**
     * @param ConfigProvider $extendConfigProvider
     * @return $this
     */
    public function setExtendConfigProvider($extendConfigProvider)
    {
        $this->extendConfigProvider = $extendConfigProvider;

        return $this;
    }

    /**
     * @return ConfigProvider
     */
    public function getExtendConfigProvider()
    {
        return $this->extendConfigProvider;
    }

    /**
     * @param string $className
     * @return bool
     */
    public function isExtendEntity($className)
    {
        return $this->extendConfigProvider->getConfig($className)->is('is_extend');
    }

    /**
     * @param FilterCollection $collection
     */
    public function setFilterCollection(FilterCollection $collection)
    {
        $this->filterCollection = $collection;
    }

    /**
     * Gets the enabled filters.
     *
     * @return FilterCollection The active filter collection.
     */
    public function getFilters()
    {
        if (null === $this->filterCollection) {
            $this->filterCollection = new FilterCollection($this);
        }

        return $this->filterCollection;
    }

    /**
     * Checks whether the state of the filter collection is clean.
     *
     * @return boolean True, if the filter collection is clean.
     */
    public function isFiltersStateClean()
    {
        return null === $this->filterCollection || $this->filterCollection->isClean();
    }

    /**
     * Checks whether the Entity Manager has filters.
     *
     * @return boolean True, if the EM has a filter collection with enabled filters.
     */
    public function hasFilters()
    {
        return null !== $this->filterCollection && $this->filterCollection->getEnabledFilters();
    }

    /**
     * {@inheritdoc}
     */
    public function getRepository($className)
    {
        $repo = parent::getRepository($className);
        if ($repo instanceof EntityConfigAwareRepositoryInterface) {
            $repo->setEntityConfigManager($this->extendConfigProvider->getConfigManager());
        }

        return $repo;
    }
}

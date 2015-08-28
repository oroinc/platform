<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;

use Oro\Bundle\EntityBundle\ORM\Event\PreCloseEventArgs;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class OroEntityManager extends EntityManager
{
    const CLASS_NAME = 'Oro\Bundle\EntityBundle\ORM\OroEntityManager';

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

        $entityManagerClassName = static::CLASS_NAME;

        return new $entityManagerClassName($conn, $config, $conn->getEventManager());
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $evm = $this->getEventManager();
        if ($evm->hasListeners(Events::preClose)) {
            $evm->dispatchEvent(Events::preClose, new PreCloseEventArgs($this));
        }

        parent::close();
    }

    /**
     * @param ConfigProvider $extendConfigProvider
     * @return $this
     *
     * @deprecated since 1.8. Will be removed in 2.0
     */
    public function setExtendConfigProvider($extendConfigProvider)
    {
        $this->extendConfigProvider = $extendConfigProvider;

        return $this;
    }

    /**
     * @return ConfigProvider
     *
     * @deprecated since 1.8. Will be removed in 2.0
     */
    public function getExtendConfigProvider()
    {
        return $this->extendConfigProvider;
    }
}

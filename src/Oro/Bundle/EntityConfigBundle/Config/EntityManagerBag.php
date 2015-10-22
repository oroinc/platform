<?php

namespace Oro\Bundle\EntityConfigBundle\Config;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

/**
 * This class provides an access to entity managers that may contain configurable entities.
 */
class EntityManagerBag
{
    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var string[] */
    protected $managerNames;

    /**
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * Registers an entity manager that may contain configurable entities.
     *
     * @param string $managerName
     */
    public function addEntityManager($managerName)
    {
        if (null === $this->managerNames) {
            $this->managerNames = [];
        }
        $this->managerNames[] = $managerName;
    }

    /**
     * Gets all entity managers that may contain configurable entities.
     *
     * @return EntityManager[]
     */
    public function getEntityManagers()
    {
        $result = [];
        // add default entity manager
        $result[] = $this->doctrine->getManager();
        // add other entity managers
        if (!empty($this->managerNames)) {
            foreach ($this->managerNames as $managerName) {
                $result[] = $this->doctrine->getManager($managerName);
            }
        }

        return $result;
    }
}

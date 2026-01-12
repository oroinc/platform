<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Doctrine\Persistence\ObjectManager;

/**
 * Defines the contract for accessing all Doctrine object managers.
 *
 * Implementations of this interface provide access to all configured Doctrine
 * object managers (entity managers) that may contain entities in the application.
 */
interface ManagerBagInterface
{
    /**
     * Gets all managers that may contain entities.
     *
     * @return ObjectManager[]
     */
    public function getManagers();
}

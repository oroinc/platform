<?php

namespace Oro\Bundle\SoapBundle\Controller\Api;

use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;

/**
 * Defines the contract for objects that require access to an API entity manager.
 *
 * Implementing classes can retrieve the entity manager instance used for managing
 * entity persistence operations in API contexts.
 */
interface EntityManagerAwareInterface
{
    /**
     * Get entity Manager
     *
     * @return ApiEntityManager
     */
    public function getManager();
}

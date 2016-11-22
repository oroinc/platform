<?php

namespace Oro\Bundle\SoapBundle\Handler;

use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;

interface DeleteHandlerInterface
{
    /**
     * Handle delete entity object.
     *
     * @param mixed            $id
     * @param ApiEntityManager $manager
     */
    public function handleDelete($id, ApiEntityManager $manager);
}

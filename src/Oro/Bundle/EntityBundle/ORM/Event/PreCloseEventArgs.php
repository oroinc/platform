<?php

namespace Oro\Bundle\EntityBundle\ORM\Event;

use Doctrine\Common\EventArgs;
use Doctrine\ORM\EntityManager;

/**
 * Provides event arguments for the preClose event.
 */
class PreCloseEventArgs extends EventArgs
{
    /** @var EntityManager */
    private $em;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Retrieves associated EntityManager.
     *
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->em;
    }
}

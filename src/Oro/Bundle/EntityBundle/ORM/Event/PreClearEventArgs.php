<?php

namespace Oro\Bundle\EntityBundle\ORM\Event;

use Doctrine\Common\EventArgs;
use Doctrine\ORM\EntityManager;

/**
 * Provides event arguments for the preClear event.
 */
class PreClearEventArgs extends EventArgs
{
    /** @var EntityManager */
    private $em;

    /** @var string|null */
    private $entityName;

    /**
     * @param EntityManager $em
     * @param string $entityName
     */
    public function __construct(EntityManager $em, ?string $entityName)
    {
        $this->em = $em;
        $this->entityName = $entityName;
    }

    public function getEntityManager(): EntityManager
    {
        return $this->em;
    }

    public function getEntityName(): ?string
    {
        return $this->entityName;
    }
}

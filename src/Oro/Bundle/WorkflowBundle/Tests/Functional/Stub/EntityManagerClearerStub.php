<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Stub;

use Doctrine\Common\Persistence\ManagerRegistry;

class EntityManagerClearerStub
{
    /** @var ManagerRegistry */
    private $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function clear()
    {
        $this->registry->getManager()->clear();
    }
}

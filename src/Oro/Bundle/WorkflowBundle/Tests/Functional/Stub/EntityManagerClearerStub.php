<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Stub;

use Doctrine\Persistence\ManagerRegistry;

class EntityManagerClearerStub
{
    /** @var ManagerRegistry */
    private $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function clear()
    {
        $this->registry->getManager()->clear();
    }
}

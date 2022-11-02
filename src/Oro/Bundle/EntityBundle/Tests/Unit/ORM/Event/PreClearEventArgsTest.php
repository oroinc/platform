<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\ORM\Event;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\ORM\Event\PreClearEventArgs;

class PreClearEventArgsTest extends \PHPUnit\Framework\TestCase
{
    public function testGetEntityManager(): void
    {
        $entityManager = $this->createMock(EntityManager::class);
        $this->assertEquals($entityManager, (new PreClearEventArgs($entityManager, null))->getEntityManager());
    }

    public function testGetEntityName(): void
    {
        $entityManager = $this->createMock(EntityManager::class);
        $entityName = 'SampleName';
        $this->assertEquals($entityName, (new PreClearEventArgs($entityManager, $entityName))->getEntityName());
    }
}

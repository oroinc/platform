<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\ORM;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\EntityIdAccessor;
use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Fixtures\TestEntity;

class EntityIdAccessorTest extends \PHPUnit\Framework\TestCase
{
    public function testGetIdentifier()
    {
        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $accessor = new EntityIdAccessor($doctrineHelper);

        $entity   = new TestEntity();
        $entityId = 123;

        $doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($this->identicalTo($entity))
            ->willReturn($entityId);

        $this->assertEquals($entityId, $accessor->getIdentifier($entity));
    }
}

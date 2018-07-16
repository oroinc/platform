<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\ORM;

use Oro\Bundle\EntityBundle\ORM\EntityIdAccessor;
use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Fixtures\TestEntity;

class EntityIdAccessorTest extends \PHPUnit\Framework\TestCase
{
    public function testGetIdentifier()
    {
        $doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $accessor       = new EntityIdAccessor($doctrineHelper);

        $entity   = new TestEntity();
        $entityId = 123;

        $doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($this->identicalTo($entity))
            ->will($this->returnValue($entityId));

        $this->assertEquals($entityId, $accessor->getIdentifier($entity));
    }
}

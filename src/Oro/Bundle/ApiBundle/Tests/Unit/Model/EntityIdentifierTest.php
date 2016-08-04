<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Model;

use Oro\Bundle\ApiBundle\Model\EntityIdentifier;

class EntityIdentifierTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $entityIdentifier = new EntityIdentifier('id');
        $this->assertEquals('id', $entityIdentifier->getId());
    }

    public function testId()
    {
        $entityIdentifier = new EntityIdentifier();
        $this->assertNull($entityIdentifier->getId());

        $entityIdentifier->setId('test');
        $this->assertEquals('test', $entityIdentifier->getId());
    }
}

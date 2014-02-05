<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Config\Id;

use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;

class EntityIdTest extends \PHPUnit_Framework_TestCase
{
    public function testGetConfig()
    {
        $entityId = new EntityConfigId('Test\Class', 'testScope');

        $this->assertEquals('Test\Class', $entityId->getClassName());
        $this->assertEquals('testScope', $entityId->getScope());
        $this->assertEquals('entity_testScope_Test-Class', $entityId->toString());
    }

    public function testSerialize()
    {
        $entityId = new EntityConfigId('Test\Class', 'testScope');

        $this->assertEquals($entityId, unserialize(serialize($entityId)));
    }

    public function testSetState()
    {
        $entityId = EntityConfigId::__set_state(
            [
                'className' => 'Test\Class',
                'scope' => 'testScope',
            ]
        );
        $this->assertEquals('Test\Class', $entityId->getClassName());
        $this->assertEquals('testScope', $entityId->getScope());
    }
}

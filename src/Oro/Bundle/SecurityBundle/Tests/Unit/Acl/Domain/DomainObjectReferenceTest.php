<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain;

use Oro\Bundle\SecurityBundle\Acl\Domain\DomainObjectReference;

class DomainObjectReferenceTest extends \PHPUnit\Framework\TestCase
{
    public function testGetObjectData()
    {
        $className = 'testClass';
        $objectId = 1;
        $ownerId = 12;
        $organizationId = 23;

        $objectReference = new DomainObjectReference($className, $objectId, $ownerId, $organizationId);

        $this->assertEquals($className, $objectReference->getType());
        $this->assertEquals($objectId, $objectReference->getIdentifier());
        $this->assertEquals($ownerId, $objectReference->getOwnerId());
        $this->assertEquals($organizationId, $objectReference->getOrganizationId());
    }

    public function testGetObjectDataNoOrganization()
    {
        $className = 'testClass';
        $objectId = 1;
        $ownerId = 12;

        $objectReference = new DomainObjectReference($className, $objectId, $ownerId);

        $this->assertEquals($className, $objectReference->getType());
        $this->assertEquals($objectId, $objectReference->getIdentifier());
        $this->assertEquals($ownerId, $objectReference->getOwnerId());
        $this->assertNull($objectReference->getOrganizationId());
    }
}

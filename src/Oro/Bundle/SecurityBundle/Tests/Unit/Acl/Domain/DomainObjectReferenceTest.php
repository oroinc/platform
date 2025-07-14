<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain;

use Oro\Bundle\SecurityBundle\Acl\Domain\DomainObjectReference;
use PHPUnit\Framework\TestCase;

class DomainObjectReferenceTest extends TestCase
{
    public function testGetObjectData(): void
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

    public function testGetObjectDataNoOrganization(): void
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

<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Owner;

use Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Fixtures\Entity\TestEntity;
use Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Fixtures\Entity\TestEntityWithOwnerFieldButWithoutGetOwnerMethod;
use Oro\Bundle\SecurityBundle\Tests\Unit\Stub\OwnershipMetadataProviderStub;

class EntityOwnerAccessorTest extends \PHPUnit\Framework\TestCase
{
    public function testGetOwner()
    {
        $metadataProvider = new OwnershipMetadataProviderStub($this);
        $accessor = new EntityOwnerAccessor($metadataProvider);

        $obj1 = new TestEntity('testId1');
        $obj1->setOwner('testOwner1');
        $metadataProvider->setMetadata(get_class($obj1), new OwnershipMetadata('ORGANIZATION', 'owner', 'owner_id'));
        $this->assertEquals('testOwner1', $accessor->getOwner($obj1));

        $obj2 = new TestEntityWithOwnerFieldButWithoutGetOwnerMethod('testOwner2');
        $metadataProvider->setMetadata(get_class($obj2), new OwnershipMetadata('ORGANIZATION', 'owner', 'owner_id'));
        $this->assertEquals('testOwner2', $accessor->getOwner($obj2));

        $obj1 = new TestEntity('testId3');
        $obj1->setCustomOwner('testOwner3');
        $metadataProvider->setMetadata(
            get_class($obj1),
            new OwnershipMetadata('ORGANIZATION', 'customOwner', 'custom_owner_id')
        );
        $this->assertEquals('testOwner3', $accessor->getOwner($obj1));
    }

    public function testGetOwnerNoMetadata()
    {
        $accessor = new EntityOwnerAccessor(new OwnershipMetadataProviderStub($this));

        $obj = new TestEntity('testId');
        $obj->setOwner('testOwner');
        $this->assertNull($accessor->getOwner($obj));
    }

    /**
     * @expectedException \Oro\Bundle\EntityBundle\Exception\InvalidEntityException
     */
    public function testGetOwnerNull()
    {
        $accessor = new EntityOwnerAccessor(new OwnershipMetadataProviderStub($this));
        $accessor->getOwner(null);
    }

    /**
     * @expectedException \Oro\Bundle\EntityBundle\Exception\InvalidEntityException
     */
    public function testGetOwnerNoGetOwnerAndNoOwnerField()
    {
        $metadataProvider = new OwnershipMetadataProviderStub($this);
        $accessor = new EntityOwnerAccessor($metadataProvider);

        $obj = new \stdClass();
        $metadataProvider->setMetadata(get_class($obj), new OwnershipMetadata('ORGANIZATION', 'owner', 'owner_id'));

        $accessor->getOwner($obj);
    }

    /**
     * @expectedException \Oro\Bundle\EntityBundle\Exception\InvalidEntityException
     */
    public function testGetOrganizationWrongObject()
    {
        $metadataProvider = new OwnershipMetadataProviderStub($this);
        $accessor = new EntityOwnerAccessor($metadataProvider);
        $accessor->getOrganization('not_an_object');
    }

    public function testGetOrganization()
    {
        $metadataProvider = new OwnershipMetadataProviderStub($this);
        $accessor = new EntityOwnerAccessor($metadataProvider);
        $org = new \stdClass();
        $obj = new TestEntity(1, null, $org);
        $metadataProvider->setMetadata(get_class($obj), new OwnershipMetadata(null, null, null, 'organization'));
        $this->assertSame($org, $accessor->getOrganization($obj));
    }
}

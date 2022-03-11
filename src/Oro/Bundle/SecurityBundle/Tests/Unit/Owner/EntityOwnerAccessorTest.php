<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Owner;

use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\Rules\English\InflectorFactory;
use Oro\Bundle\EntityBundle\Exception\InvalidEntityException;
use Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Fixtures\Entity\TestEntity;
use Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Fixtures\Entity\TestEntityWithOwnerFieldButWithoutGetOwnerMethod;
use Oro\Bundle\SecurityBundle\Tests\Unit\Stub\OwnershipMetadataProviderStub;

class EntityOwnerAccessorTest extends \PHPUnit\Framework\TestCase
{
    private Inflector $inflector;

    protected function setUp(): void
    {
        $this->inflector = (new InflectorFactory())->build();
    }

    public function testGetOwner()
    {
        $metadataProvider = new OwnershipMetadataProviderStub($this);
        $accessor = new EntityOwnerAccessor($metadataProvider, $this->inflector);

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
        $metadataProvider = new OwnershipMetadataProviderStub($this);
        $accessor = new EntityOwnerAccessor($metadataProvider, $this->inflector);
        $metadataProvider->getCacheMock()
            ->expects(self::any())
            ->method('get')
            ->willReturn(true);

        $obj = new TestEntity('testId');
        $obj->setOwner('testOwner');
        $this->assertNull($accessor->getOwner($obj));
    }

    public function testGetOwnerNull()
    {
        $this->expectException(InvalidEntityException::class);
        $accessor = new EntityOwnerAccessor(new OwnershipMetadataProviderStub($this), $this->inflector);
        $accessor->getOwner(null);
    }

    public function testGetOwnerNoGetOwnerAndNoOwnerField()
    {
        $this->expectException(InvalidEntityException::class);
        $metadataProvider = new OwnershipMetadataProviderStub($this);
        $accessor = new EntityOwnerAccessor($metadataProvider, $this->inflector);

        $obj = new \stdClass();
        $metadataProvider->setMetadata(get_class($obj), new OwnershipMetadata('ORGANIZATION', 'owner', 'owner_id'));

        $accessor->getOwner($obj);
    }

    public function testGetOrganizationWrongObject()
    {
        $this->expectException(InvalidEntityException::class);
        $metadataProvider = new OwnershipMetadataProviderStub($this);
        $accessor = new EntityOwnerAccessor($metadataProvider, $this->inflector);
        $accessor->getOrganization('not_an_object');
    }

    public function testGetOrganization()
    {
        $metadataProvider = new OwnershipMetadataProviderStub($this);
        $accessor = new EntityOwnerAccessor($metadataProvider, $this->inflector);
        $org = new \stdClass();
        $obj = new TestEntity(1, null, $org);
        $metadataProvider->setMetadata(get_class($obj), new OwnershipMetadata('', '', '', 'organization'));
        $this->assertSame($org, $accessor->getOrganization($obj));
    }
}

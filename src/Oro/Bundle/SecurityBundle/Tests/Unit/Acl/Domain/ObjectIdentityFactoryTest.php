<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain;

use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\SecurityBundle\Acl\Exception\InvalidAclException;
use Oro\Bundle\SecurityBundle\Annotation\Acl as AclAnnotation;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\TestEntity;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\TestEntityImplementsDomainObjectInterface;
use Oro\Bundle\SecurityBundle\Tests\Unit\TestHelper;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;

class ObjectIdentityFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var ObjectIdentityFactory */
    private $factory;

    protected function setUp()
    {
        $this->factory = new ObjectIdentityFactory(
            TestHelper::get($this)->createAclExtensionSelector()
        );
    }

    public function testRoot()
    {
        $id = $this->factory->root('entity');
        $this->assertEquals('entity', $id->getIdentifier());
        $this->assertEquals(ObjectIdentityFactory::ROOT_IDENTITY_TYPE, $id->getType());

        $id = $this->factory->root(\stdClass::class);
        $this->assertEquals('stdclass', $id->getIdentifier());
        $this->assertEquals(ObjectIdentityFactory::ROOT_IDENTITY_TYPE, $id->getType());

        $id = $this->factory->root($this->factory->get('Entity: Test:TestEntity'));
        $this->assertEquals('entity', $id->getIdentifier());
        $this->assertEquals(ObjectIdentityFactory::ROOT_IDENTITY_TYPE, $id->getType());

        $id = $this->factory->root($this->factory->get(new TestEntity(123)));
        $this->assertEquals('entity', $id->getIdentifier());
        $this->assertEquals(ObjectIdentityFactory::ROOT_IDENTITY_TYPE, $id->getType());

        $id = $this->factory->root('action');
        $this->assertEquals('action', $id->getIdentifier());
        $this->assertEquals(ObjectIdentityFactory::ROOT_IDENTITY_TYPE, $id->getType());

        $id = $this->factory->root('Action');
        $this->assertEquals('action', $id->getIdentifier());
        $this->assertEquals(ObjectIdentityFactory::ROOT_IDENTITY_TYPE, $id->getType());

        $id = $this->factory->root($this->factory->get('Action: Some Action'));
        $this->assertEquals('action', $id->getIdentifier());
        $this->assertEquals(ObjectIdentityFactory::ROOT_IDENTITY_TYPE, $id->getType());
    }

    public function testUnderlyingForObjectLevelObjectIdentity()
    {
        $id = $this->createMock(ObjectIdentityInterface::class);
        $id->expects(self::any())
            ->method('getIdentifier')
            ->willReturn(123);
        $id->expects(self::any())
            ->method('getType')
            ->willReturn(TestEntity::class);

        $underlyingId = $this->factory->underlying($id);
        $this->assertEquals('entity', $underlyingId->getIdentifier());
        $this->assertEquals(TestEntity::class, $underlyingId->getType());
    }

    public function testUnderlyingForRootObjectIdentity()
    {
        $this->expectException(InvalidAclException::class);
        $this->expectExceptionMessage(sprintf(
            'Cannot get underlying ACL for ObjectIdentity(entity, %s)',
            ObjectIdentityFactory::ROOT_IDENTITY_TYPE
        ));

        $this->factory->underlying($this->factory->root('entity'));
    }

    public function testUnderlyingForClassLevelObjectIdentity()
    {
        $this->expectException(InvalidAclException::class);
        $this->expectExceptionMessage(sprintf(
            'Cannot get underlying ACL for ObjectIdentity(entity, %s)',
            TestEntity::class
        ));

        $this->factory->underlying($this->factory->get('entity:' . TestEntity::class));
    }

    public function testUnderlyingForClassLevelObjectIdentityThatDoesNotHaveToStringMethod()
    {
        $id = $this->createMock(ObjectIdentityInterface::class);
        $id->expects(self::any())
            ->method('getIdentifier')
            ->willReturn('entity');
        $id->expects(self::any())
            ->method('getType')
            ->willReturn(TestEntity::class);

        $this->expectException(InvalidAclException::class);
        $this->expectExceptionMessage(sprintf(
            'Cannot get underlying ACL for %s(entity, %s)',
            get_class($id),
            TestEntity::class
        ));

        $this->factory->underlying($id);
    }

    public function testFromDomainObjectPrefersInterfaceOverGetId()
    {
        $obj = new TestEntityImplementsDomainObjectInterface('getObjectIdentifier()');
        $id = $this->factory->get($obj);
        $this->assertEquals('getObjectIdentifier()', $id->getIdentifier());
        $this->assertEquals(get_class($obj), $id->getType());
    }

    public function testFromDomainObjectWithoutDomainObjectInterface()
    {
        $obj = new TestEntity('getId()');
        $id = $this->factory->get($obj);
        $this->assertEquals('getId()', $id->getIdentifier());
        $this->assertEquals(get_class($obj), $id->getType());
    }

    /**
     * @expectedException \Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException
     */
    public function testFromDomainObjectNull()
    {
        $this->factory->get(null);
    }

    /**
     * @expectedException \Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException
     */
    public function testGetShouldCatchInvalidArgumentException()
    {
        $this->factory->get(new TestEntityImplementsDomainObjectInterface());
    }

    /**
     * @dataProvider getProvider
     */
    public function testGet($descriptor, $expectedId, $expectedType)
    {
        $id = $this->factory->get($descriptor);
        $this->assertEquals($expectedType, $id->getType());
        $this->assertEquals($expectedId, $id->getIdentifier());
    }

    /**
     * @expectedException \Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException
     */
    public function testGetIncorrectClassDescriptor()
    {
        $this->factory->get('AcmeBundle\SomeClass');
    }

    /**
     * @expectedException \Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException
     */
    public function testGetIncorrectEntityDescriptor()
    {
        $this->factory->get('AcmeBundle:SomeEntity');
    }

    /**
     * @expectedException \Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException
     */
    public function testGetWithInvalidEntityName()
    {
        $this->factory->get('entity:AcmeBundle:Entity:SomeEntity');
    }

    /**
     * @expectedException \Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException
     */
    public function testGetIncorrectActionDescriptor()
    {
        $this->factory->get('Some Action');
    }

    public function testFromEntityAclAnnotation()
    {
        $obj = new AclAnnotation(['id' => 'test', 'type' => 'entity', 'class' => 'Acme\SomeEntity']);
        $id = $this->factory->get($obj);
        $this->assertEquals('entity', $id->getIdentifier());
        $this->assertEquals('Acme\SomeEntity', $id->getType());
    }

    public function testFromActionAclAnnotation()
    {
        $obj = new AclAnnotation(['id' => 'test_action', 'type' => 'action']);
        $id = $this->factory->get($obj);
        $this->assertEquals('action', $id->getIdentifier());
        $this->assertEquals('test_action', $id->getType());
    }

    public static function getProvider()
    {
        return [
            'Entity'              => ['Entity:Test:TestEntity', 'entity', TestEntity::class],
            'Entity (whitespace)' => ['Entity: Test:TestEntity', 'entity', TestEntity::class],
            'ENTITY'              => ['ENTITY:Test:TestEntity', 'entity', TestEntity::class],
            'Entity (class name)' => ['Entity: ' . TestEntity::class, 'entity', TestEntity::class],
            'Action'              => ['Action:Some Action', 'action', 'Some Action'],
            'Action (whitespace)' => ['Action: Some Action', 'action', 'Some Action'],
            'ACTION'              => ['ACTION:Some Action', 'action', 'Some Action'],
        ];
    }
}

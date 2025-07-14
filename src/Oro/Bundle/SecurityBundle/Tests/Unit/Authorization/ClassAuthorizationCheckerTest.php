<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Authorization;

use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\SecurityBundle\Attribute\Acl as AclAttribute;
use Oro\Bundle\SecurityBundle\Authorization\ClassAuthorizationChecker;
use Oro\Bundle\SecurityBundle\Metadata\AclAttributeProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ClassAuthorizationCheckerTest extends TestCase
{
    private AuthorizationCheckerInterface&MockObject $authorizationChecker;
    private ObjectIdentityFactory&MockObject $objectIdentityFactory;
    private AclAttributeProvider&MockObject $attributeProvider;
    private LoggerInterface&MockObject $logger;
    private ClassAuthorizationChecker $classAuthorizationChecker;

    #[\Override]
    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->objectIdentityFactory = $this->createMock(ObjectIdentityFactory::class);
        $this->attributeProvider = $this->createMock(AclAttributeProvider::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->classAuthorizationChecker = new ClassAuthorizationChecker(
            $this->authorizationChecker,
            $this->objectIdentityFactory,
            $this->attributeProvider,
            $this->logger
        );
    }

    public function testIsClassMethodGrantedDenyingByMethodAcl(): void
    {
        $oid = new ObjectIdentity('1', 'TestType');
        $attribute = $this->createMock(AclAttribute::class);
        $attribute->expects($this->once())
            ->method('getId')
            ->willReturn('method_attribute');
        $attribute->expects($this->once())
            ->method('getPermission')
            ->willReturn('TEST_PERMISSION');

        $this->attributeProvider->expects($this->once())
            ->method('findAttribute')
            ->with('TestClass', 'TestMethod')
            ->willReturn($attribute);
        $this->logger->expects($this->once())
            ->method('debug');
        $this->objectIdentityFactory->expects($this->once())
            ->method('get')
            ->with($this->identicalTo($attribute))
            ->willReturn($oid);
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('TEST_PERMISSION', $this->identicalTo($oid))
            ->willReturn(false);

        $result = $this->classAuthorizationChecker->isClassMethodGranted('TestClass', 'TestMethod');
        $this->assertFalse($result);
    }

    public function testIsClassMethodGrantedGrantingByMethodAclNoClassAcl(): void
    {
        $oid = new ObjectIdentity('1', 'TestType');
        $attribute = $this->createMock(AclAttribute::class);
        $attribute->expects($this->once())
            ->method('getId')
            ->willReturn('method_attribute');
        $attribute->expects($this->once())
            ->method('getPermission')
            ->willReturn('TEST_PERMISSION');
        $attribute->expects($this->once())
            ->method('getIgnoreClassAcl')
            ->willReturn(false);

        $this->attributeProvider->expects($this->exactly(2))
            ->method('findAttribute')
            ->willReturnMap([
                ['TestClass', 'TestMethod', $attribute],
                ['TestClass', null, null]
            ]);
        $this->logger->expects($this->once())
            ->method('debug');
        $this->objectIdentityFactory->expects($this->once())
            ->method('get')
            ->with($this->identicalTo($attribute))
            ->willReturn($oid);
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('TEST_PERMISSION', $this->identicalTo($oid))
            ->willReturn(true);

        $result = $this->classAuthorizationChecker->isClassMethodGranted('TestClass', 'TestMethod');
        $this->assertTrue($result);
    }

    public function testIsClassMethodGrantedGrantingByMethodAclWithIgnoreClassAcl(): void
    {
        $oid = new ObjectIdentity('1', 'TestType');
        $attribute = $this->createMock(AclAttribute::class);
        $attribute->expects($this->once())
            ->method('getId')
            ->willReturn('method_attribute');
        $attribute->expects($this->once())
            ->method('getPermission')
            ->willReturn('TEST_PERMISSION');
        $attribute->expects($this->once())
            ->method('getIgnoreClassAcl')
            ->willReturn(true);

        $this->attributeProvider->expects($this->once())
            ->method('findAttribute')
            ->with('TestClass', 'TestMethod')
            ->willReturn($attribute);
        $this->logger->expects($this->once())
            ->method('debug');
        $this->objectIdentityFactory->expects($this->once())
            ->method('get')
            ->with($this->identicalTo($attribute))
            ->willReturn($oid);
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('TEST_PERMISSION', $this->identicalTo($oid))
            ->willReturn(true);

        $result = $this->classAuthorizationChecker->isClassMethodGranted('TestClass', 'TestMethod');
        $this->assertTrue($result);
    }

    public function testIsClassMethodGrantedDenyingByClassAcl(): void
    {
        $oid = new ObjectIdentity('1', 'TestType');
        $attribute = $this->createMock(AclAttribute::class);
        $attribute->expects($this->once())
            ->method('getId')
            ->willReturn('method_attribute');
        $attribute->expects($this->once())
            ->method('getPermission')
            ->willReturn('TEST_PERMISSION');
        $attribute->expects($this->once())
            ->method('getIgnoreClassAcl')
            ->willReturn(false);

        $classOid = new ObjectIdentity('2', 'TestType');
        $classAttribute = $this->createMock(AclAttribute::class);
        $classAttribute->expects($this->once())
            ->method('getId')
            ->willReturn('class_attribute');
        $classAttribute->expects($this->once())
            ->method('getPermission')
            ->willReturn('TEST_PERMISSION_CLASS');

        $this->attributeProvider->expects($this->exactly(2))
            ->method('findAttribute')
            ->willReturnMap([
                ['TestClass', 'TestMethod', $attribute],
                ['TestClass', null, $classAttribute]
            ]);
        $this->logger->expects($this->exactly(2))
            ->method('debug');
        $this->objectIdentityFactory->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [$this->identicalTo($attribute)],
                [$this->identicalTo($classAttribute)]
            )
            ->willReturnOnConsecutiveCalls(
                $oid,
                $classOid
            );
        $this->authorizationChecker->expects($this->exactly(2))
            ->method('isGranted')
            ->withConsecutive(
                ['TEST_PERMISSION', $this->identicalTo($oid)],
                ['TEST_PERMISSION_CLASS', $this->identicalTo($classOid)]
            )
            ->willReturnOnConsecutiveCalls(
                true,
                false
            );

        $result = $this->classAuthorizationChecker->isClassMethodGranted('TestClass', 'TestMethod');
        $this->assertFalse($result);
    }

    public function testIsClassMethodGrantedGrantingByMethodAndClassAcls(): void
    {
        $oid = new ObjectIdentity('1', 'TestType');
        $attribute = $this->createMock(AclAttribute::class);
        $attribute->expects($this->once())
            ->method('getId')
            ->willReturn('method_attribute');
        $attribute->expects($this->once())
            ->method('getPermission')
            ->willReturn('TEST_PERMISSION');
        $attribute->expects($this->once())
            ->method('getIgnoreClassAcl')
            ->willReturn(false);

        $classOid = new ObjectIdentity('2', 'TestType');
        $classAttribute = $this->createMock(AclAttribute::class);
        $classAttribute->expects($this->once())
            ->method('getId')
            ->willReturn('class_attribute');
        $classAttribute->expects($this->once())
            ->method('getPermission')
            ->willReturn('TEST_PERMISSION_CLASS');

        $this->attributeProvider->expects($this->exactly(2))
            ->method('findAttribute')
            ->willReturnMap([
                ['TestClass', 'TestMethod', $attribute],
                ['TestClass', null, $classAttribute]
            ]);
        $this->logger->expects($this->exactly(2))
            ->method('debug');
        $this->objectIdentityFactory->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [$this->identicalTo($attribute)],
                [$this->identicalTo($classAttribute)]
            )
            ->willReturnOnConsecutiveCalls(
                $oid,
                $classOid
            );
        $this->authorizationChecker->expects($this->exactly(2))
            ->method('isGranted')
            ->withConsecutive(
                ['TEST_PERMISSION', $this->identicalTo($oid)],
                ['TEST_PERMISSION_CLASS', $this->identicalTo($classOid)]
            )
            ->willReturn(true);

        $result = $this->classAuthorizationChecker->isClassMethodGranted('TestClass', 'TestMethod');
        $this->assertTrue($result);
    }

    public function testGetClassMethodAttribute(): void
    {
        $class = 'TestClass';
        $method = 'TestMethod';
        $attribute = $this->createMock(AclAttribute::class);

        $this->attributeProvider->expects($this->once())
            ->method('findAttribute')
            ->with($class, $method)
            ->willReturn($attribute);

        $this->assertSame(
            $attribute,
            $this->classAuthorizationChecker->getClassMethodAttribute($class, $method)
        );
    }

    public function testGetClassMethodAttributeWhenAttributeWasNotFound(): void
    {
        $class = 'TestClass';
        $method = 'TestMethod';

        $this->attributeProvider->expects($this->once())
            ->method('findAttribute')
            ->with($class, $method)
            ->willReturn(null);

        $this->assertNull(
            $this->classAuthorizationChecker->getClassMethodAttribute($class, $method)
        );
    }
}

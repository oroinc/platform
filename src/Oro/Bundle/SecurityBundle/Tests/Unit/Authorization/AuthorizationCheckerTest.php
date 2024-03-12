<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Authorization;

use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Authorization\AuthorizationChecker;
use Oro\Bundle\SecurityBundle\Metadata\AclAttributeProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class AuthorizationCheckerTest extends \PHPUnit\Framework\TestCase
{
    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $innerAuthorizationChecker;

    /** @var ObjectIdentityFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $objectIdentityFactory;

    /** @var AclAttributeProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $attributeProvider;

    /** @var AclGroupProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $groupProvider;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var AuthorizationChecker */
    private $authorizationChecker;

    protected function setUp(): void
    {
        $this->innerAuthorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->objectIdentityFactory = $this->createMock(ObjectIdentityFactory::class);
        $this->attributeProvider = $this->createMock(AclAttributeProvider::class);
        $this->groupProvider = $this->createMock(AclGroupProviderInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->authorizationChecker = new AuthorizationChecker(
            $this->innerAuthorizationChecker,
            $this->objectIdentityFactory,
            $this->attributeProvider,
            $this->groupProvider,
            $this->logger
        );
    }

    public function testIsGrantedWithAclAttributeIdAndNoObject(): void
    {
        $oid = new ObjectIdentity('1', 'TestType');
        $attribute = $this->createMock(Acl::class);
        $attribute->expects(self::once())
            ->method('getId')
            ->willReturn('method_attribute');
        $attribute->expects(self::once())
            ->method('getPermission')
            ->willReturn('TEST_PERMISSION');
        $this->groupProvider->expects(self::never())
            ->method('getGroup');
        $this->objectIdentityFactory->expects(self::once())
            ->method('get')
            ->with($this->identicalTo($attribute))
            ->willReturn($oid);
        $this->attributeProvider->expects(self::once())
            ->method('findAttributeById')
            ->with('TestAttribute')
            ->willReturn($attribute);
        $this->logger->expects(self::once())
            ->method('debug');
        $this->innerAuthorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('TEST_PERMISSION', $this->identicalTo($oid))
            ->willReturn(true);

        $result = $this->authorizationChecker->isGranted('TestAttribute');
        self::assertTrue($result);
    }

    public function testIsGrantedWithAclAttributeIdAndWithObject(): void
    {
        $obj = new \stdClass();
        $attribute = $this->createMock(Acl::class);
        $attribute->expects(self::once())
            ->method('getId')
            ->willReturn('method_attribute');
        $attribute->expects(self::once())
            ->method('getPermission')
            ->willReturn('TEST_PERMISSION');
        $this->groupProvider->expects(self::never())
            ->method('getGroup');
        $this->objectIdentityFactory->expects(self::never())
            ->method('get');
        $this->attributeProvider->expects(self::once())
            ->method('findAttributeById')
            ->with('TestAttribute')
            ->willReturn($attribute);
        $this->logger->expects(self::once())
            ->method('debug');
        $this->innerAuthorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('TEST_PERMISSION', $this->identicalTo($obj))
            ->willReturn(true);

        $result = $this->authorizationChecker->isGranted('TestAttribute', $obj);
        self::assertTrue($result);
    }

    public function testIsGrantedWithEmptyPermission(): void
    {
        $oid = new ObjectIdentity('test', 'action');
        $this->groupProvider->expects(self::never())
            ->method('getGroup');
        $this->objectIdentityFactory->expects(self::never())
            ->method(self::anything());
        $this->attributeProvider->expects(self::never())
            ->method(self::anything());
        $this->logger->expects(self::never())
            ->method(self::anything());
        $this->innerAuthorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('', self::identicalTo($oid))
            ->willReturn(true);

        self::assertTrue(
            $this->authorizationChecker->isGranted('', $oid)
        );
    }

    public function testIsGrantedWithRoleName(): void
    {
        $this->attributeProvider->expects(self::once())
            ->method('findAttributeById')
            ->with('TestRole')
            ->willReturn(null);
        $this->innerAuthorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('TestRole', $this->isNull())
            ->willReturn(true);

        $result = $this->authorizationChecker->isGranted('TestRole');
        self::assertTrue($result);
    }

    public function testIsGrantedWithString(): void
    {
        $oid = new ObjectIdentity('entity', 'SomeClass');
        $obj = 'entity:SomeClass';
        $this->attributeProvider->expects(self::once())
            ->method('findAttributeById')
            ->with('PERMISSION')
            ->willReturn(null);
        $this->groupProvider->expects(self::once())
            ->method('getGroup')
            ->willReturn('');
        $this->objectIdentityFactory->expects(self::once())
            ->method('get')
            ->with($obj)
            ->willReturn($oid);
        $this->innerAuthorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('PERMISSION', $oid)
            ->willReturn(true);

        $result = $this->authorizationChecker->isGranted('PERMISSION', $obj);
        self::assertTrue($result);
    }

    public function testIsGrantedWithStringAndNotDefaultAclGroup(): void
    {
        $oid = new ObjectIdentity('entity', 'TestType');
        $this->attributeProvider->expects(self::once())
            ->method('findAttributeById')
            ->with('PERMISSION')
            ->willReturn(null);
        $this->groupProvider->expects(self::once())
            ->method('getGroup')
            ->willReturn('group');
        $this->objectIdentityFactory->expects(self::once())
            ->method('get')
            ->with('entity:group@TestType')
            ->willReturn($oid);
        $this->innerAuthorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('PERMISSION', $oid)
            ->willReturn(true);

        $result = $this->authorizationChecker->isGranted('PERMISSION', 'entity:TestType');
        self::assertTrue($result);
    }

    public function testIsGrantedWithStringThatContainsAclGroup(): void
    {
        $oid = new ObjectIdentity('entity', 'SomeClass');
        $obj = 'entity:group@SomeClass';
        $this->attributeProvider->expects(self::once())
            ->method('findAttributeById')
            ->with('PERMISSION')
            ->willReturn(null);
        $this->groupProvider->expects(self::never())
            ->method('getGroup');
        $this->objectIdentityFactory->expects(self::once())
            ->method('get')
            ->with($obj)
            ->willReturn($oid);
        $this->innerAuthorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('PERMISSION', $oid)
            ->willReturn(true);

        $result = $this->authorizationChecker->isGranted('PERMISSION', $obj);
        self::assertTrue($result);
    }

    public function testIsGrantedWithCombinedString(): void
    {
        $oid = new ObjectIdentity('entity', 'TestType');
        $this->groupProvider->expects(self::once())
            ->method('getGroup')
            ->willReturn('');
        $this->objectIdentityFactory->expects(self::once())
            ->method('get')
            ->with('entity:TestType')
            ->willReturn($oid);
        $this->innerAuthorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', self::identicalTo($oid))
            ->willReturn(true);

        $result = $this->authorizationChecker->isGranted('VIEW;entity:TestType');
        self::assertTrue($result);
    }

    public function testIsGrantedWithObject(): void
    {
        $obj = new \stdClass();
        $this->attributeProvider->expects(self::once())
            ->method('findAttributeById')
            ->with('PERMISSION')
            ->willReturn(null);
        $this->innerAuthorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('PERMISSION', $obj)
            ->willReturn(true);

        $result = $this->authorizationChecker->isGranted('PERMISSION', $obj);
        self::assertTrue($result);
    }

    public function testIsGrantedForNotAclProtectedClass(): void
    {
        $obj = 'Test\Class';
        $this->attributeProvider->expects(self::once())
            ->method('findAttributeById')
            ->with('PERMISSION')
            ->willReturn(null);
        $this->groupProvider->expects(self::once())
            ->method('getGroup')
            ->willReturn('');
        $this->objectIdentityFactory->expects(self::once())
            ->method('get')
            ->with($obj)
            ->willThrowException(new InvalidDomainObjectException());
        $this->innerAuthorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('PERMISSION', $obj)
            ->willReturn(true);

        $result = $this->authorizationChecker->isGranted('PERMISSION', $obj);
        self::assertTrue($result);
    }
}

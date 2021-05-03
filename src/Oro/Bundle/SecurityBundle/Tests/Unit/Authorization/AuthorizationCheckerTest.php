<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Authorization;

use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Authorization\AuthorizationChecker;
use Oro\Bundle\SecurityBundle\Metadata\AclAnnotationProvider;
use Oro\Component\DependencyInjection\ServiceLink;
use Oro\Component\Testing\Unit\TestContainerBuilder;
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

    /** @var AclAnnotationProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $annotationProvider;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var AuthorizationChecker */
    private $authorizationChecker;

    protected function setUp(): void
    {
        $this->innerAuthorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->objectIdentityFactory = $this->createMock(ObjectIdentityFactory::class);
        $this->annotationProvider = $this->createMock(AclAnnotationProvider::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $container = TestContainerBuilder::create()
            ->add('authorization_checker', $this->innerAuthorizationChecker)
            ->add('object_identity_factory', $this->objectIdentityFactory)
            ->add('annotation_provider', $this->annotationProvider)
            ->getContainer($this);

        $this->authorizationChecker = new AuthorizationChecker(
            new ServiceLink($container, 'authorization_checker'),
            new ServiceLink($container, 'object_identity_factory'),
            new ServiceLink($container, 'annotation_provider'),
            $this->logger
        );
    }

    public function testIsGrantedWithAclAnnotationIdAndNoObject()
    {
        $oid = new ObjectIdentity('1', 'TestType');
        $annotation = $this->createMock(Acl::class);
        $annotation->expects($this->once())
            ->method('getId')
            ->willReturn('method_annotation');
        $annotation->expects($this->once())
            ->method('getPermission')
            ->willReturn('TEST_PERMISSION');
        $this->objectIdentityFactory->expects($this->once())
            ->method('get')
            ->with($this->identicalTo($annotation))
            ->willReturn($oid);
        $this->annotationProvider->expects($this->once())
            ->method('findAnnotationById')
            ->with('TestAnnotation')
            ->willReturn($annotation);
        $this->logger->expects($this->once())
            ->method('debug');
        $this->innerAuthorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('TEST_PERMISSION', $this->identicalTo($oid))
            ->willReturn(true);

        $result = $this->authorizationChecker->isGranted('TestAnnotation');
        $this->assertTrue($result);
    }

    public function testIsGrantedWithAclAnnotationIdAndWithObject()
    {
        $obj = new \stdClass();
        $annotation = $this->createMock(Acl::class);
        $annotation->expects($this->once())
            ->method('getId')
            ->willReturn('method_annotation');
        $annotation->expects($this->once())
            ->method('getPermission')
            ->willReturn('TEST_PERMISSION');
        $this->objectIdentityFactory->expects($this->never())
            ->method('get');
        $this->annotationProvider->expects($this->once())
            ->method('findAnnotationById')
            ->with('TestAnnotation')
            ->willReturn($annotation);
        $this->logger->expects($this->once())
            ->method('debug');
        $this->innerAuthorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('TEST_PERMISSION', $this->identicalTo($obj))
            ->willReturn(true);

        $result = $this->authorizationChecker->isGranted('TestAnnotation', $obj);
        $this->assertTrue($result);
    }

    public function testIsGrantedWithEmptyPermission()
    {
        $oid = new ObjectIdentity('test', 'action');
        $this->objectIdentityFactory->expects(self::never())
            ->method(self::anything());
        $this->annotationProvider->expects(self::never())
            ->method(self::anything());
        $this->logger->expects(self::never())
            ->method(self::anything());
        $this->innerAuthorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('', self::identicalTo($oid))
            ->willReturn(true);

        $this->assertTrue(
            $this->authorizationChecker->isGranted('', $oid)
        );
    }

    public function testIsGrantedWithRoleName()
    {
        $this->annotationProvider->expects($this->once())
            ->method('findAnnotationById')
            ->with('TestRole')
            ->willReturn(null);
        $this->innerAuthorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('TestRole', $this->isNull())
            ->willReturn(true);

        $result = $this->authorizationChecker->isGranted('TestRole');
        $this->assertTrue($result);
    }

    public function testIsGrantedWithRoleNames()
    {
        $this->annotationProvider->expects($this->never())
            ->method('findAnnotationById');
        $this->innerAuthorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with(['TestRole1', 'TestRole2'], $this->isNull())
            ->willReturn(true);

        $result = $this->authorizationChecker->isGranted(['TestRole1', 'TestRole2']);
        $this->assertTrue($result);
    }

    public function testIsGrantedWithString()
    {
        $oid = new ObjectIdentity('1', 'TestType');
        $obj = 'Entity:SomeClass';
        $this->annotationProvider->expects($this->once())
            ->method('findAnnotationById')
            ->with('PERMISSION')
            ->willReturn(null);
        $this->objectIdentityFactory->expects($this->once())
            ->method('get')
            ->with($obj)
            ->willReturn($oid);
        $this->innerAuthorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('PERMISSION', $oid)
            ->willReturn(true);

        $result = $this->authorizationChecker->isGranted('PERMISSION', $obj);
        $this->assertTrue($result);
    }

    public function testIsGrantedWithCombinedString()
    {
        $this->innerAuthorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('VIEW', 'entity:AcmeDemoBundle:Test')
            ->willReturn(true);

        $result = $this->authorizationChecker->isGranted('VIEW;entity:AcmeDemoBundle:Test');
        $this->assertTrue($result);
    }

    public function testIsGrantedWithObject()
    {
        $obj = new \stdClass();
        $this->annotationProvider->expects($this->once())
            ->method('findAnnotationById')
            ->with('PERMISSION')
            ->willReturn(null);
        $this->innerAuthorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('PERMISSION', $obj)
            ->willReturn(true);

        $result = $this->authorizationChecker->isGranted('PERMISSION', $obj);
        $this->assertTrue($result);
    }

    public function testIsGrantedForNotAclProtectedClass()
    {
        $obj = 'Test\Class';
        $this->annotationProvider->expects($this->once())
            ->method('findAnnotationById')
            ->with('PERMISSION')
            ->willReturn(null);
        $this->objectIdentityFactory->expects($this->once())
            ->method('get')
            ->with($obj)
            ->willThrowException(new InvalidDomainObjectException());
        $this->innerAuthorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('PERMISSION', $obj)
            ->willReturn(true);

        $result = $this->authorizationChecker->isGranted('PERMISSION', $obj);
        $this->assertTrue($result);
    }
}

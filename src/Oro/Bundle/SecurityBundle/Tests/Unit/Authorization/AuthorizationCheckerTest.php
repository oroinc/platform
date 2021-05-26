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

    public function testIsGrantedWithAclAnnotationIdAndNoObject(): void
    {
        $oid = new ObjectIdentity('1', 'TestType');
        $annotation = $this->createMock(Acl::class);
        $annotation->expects(self::once())
            ->method('getId')
            ->willReturn('method_annotation');
        $annotation->expects(self::once())
            ->method('getPermission')
            ->willReturn('TEST_PERMISSION');
        $this->objectIdentityFactory->expects(self::once())
            ->method('get')
            ->with($this->identicalTo($annotation))
            ->willReturn($oid);
        $this->annotationProvider->expects(self::once())
            ->method('findAnnotationById')
            ->with('TestAnnotation')
            ->willReturn($annotation);
        $this->logger->expects(self::once())
            ->method('debug');
        $this->innerAuthorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('TEST_PERMISSION', $this->identicalTo($oid))
            ->willReturn(true);

        $result = $this->authorizationChecker->isGranted('TestAnnotation');
        self::assertTrue($result);
    }

    public function testIsGrantedWithAclAnnotationIdAndWithObject(): void
    {
        $obj = new \stdClass();
        $annotation = $this->createMock(Acl::class);
        $annotation->expects(self::once())
            ->method('getId')
            ->willReturn('method_annotation');
        $annotation->expects(self::once())
            ->method('getPermission')
            ->willReturn('TEST_PERMISSION');
        $this->objectIdentityFactory->expects(self::never())
            ->method('get');
        $this->annotationProvider->expects(self::once())
            ->method('findAnnotationById')
            ->with('TestAnnotation')
            ->willReturn($annotation);
        $this->logger->expects(self::once())
            ->method('debug');
        $this->innerAuthorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('TEST_PERMISSION', $this->identicalTo($obj))
            ->willReturn(true);

        $result = $this->authorizationChecker->isGranted('TestAnnotation', $obj);
        self::assertTrue($result);
    }

    public function testIsGrantedWithEmptyPermission(): void
    {
        $oid = new ObjectIdentity('test', 'action');
        $this->objectIdentityFactory->expects(self::never())
            ->method(self::anything());
        $this->annotationProvider->expects(self::never())
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
        $this->annotationProvider->expects(self::once())
            ->method('findAnnotationById')
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
        $oid = new ObjectIdentity('1', 'TestType');
        $obj = 'Entity:SomeClass';
        $this->annotationProvider->expects(self::once())
            ->method('findAnnotationById')
            ->with('PERMISSION')
            ->willReturn(null);
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
        $this->innerAuthorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', 'entity:AcmeDemoBundle:Test')
            ->willReturn(true);

        $result = $this->authorizationChecker->isGranted('VIEW;entity:AcmeDemoBundle:Test');
        self::assertTrue($result);
    }

    public function testIsGrantedWithObject(): void
    {
        $obj = new \stdClass();
        $this->annotationProvider->expects(self::once())
            ->method('findAnnotationById')
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
        $this->annotationProvider->expects(self::once())
            ->method('findAnnotationById')
            ->with('PERMISSION')
            ->willReturn(null);
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

<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Authorization;

use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
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
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $innerAuthorizationChecker;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $objectIdentityFactory;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $annotationProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var AuthorizationChecker */
    private $authorizationChecker;

    protected function setUp()
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
        $annotation = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Annotation\Acl')
            ->disableOriginalConstructor()
            ->getMock();
        $annotation->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('method_annotation'));
        $annotation->expects($this->once())
            ->method('getPermission')
            ->will($this->returnValue('TEST_PERMISSION'));
        $this->objectIdentityFactory->expects($this->once())
            ->method('get')
            ->with($this->identicalTo($annotation))
            ->will($this->returnValue($oid));
        $this->annotationProvider->expects($this->at(0))
            ->method('findAnnotationById')
            ->with('TestAnnotation')
            ->will($this->returnValue($annotation));
        $this->logger->expects($this->once())
            ->method('debug');
        $this->innerAuthorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with($this->equalTo('TEST_PERMISSION'), $this->identicalTo($oid))
            ->will($this->returnValue(true));

        $result = $this->authorizationChecker->isGranted('TestAnnotation');
        $this->assertTrue($result);
    }

    public function testIsGrantedWithAclAnnotationIdAndWithObject()
    {
        $obj = new \stdClass();
        $annotation = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Annotation\Acl')
            ->disableOriginalConstructor()
            ->getMock();
        $annotation->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('method_annotation'));
        $annotation->expects($this->once())
            ->method('getPermission')
            ->will($this->returnValue('TEST_PERMISSION'));
        $this->objectIdentityFactory->expects($this->never())
            ->method('get');
        $this->annotationProvider->expects($this->at(0))
            ->method('findAnnotationById')
            ->with('TestAnnotation')
            ->will($this->returnValue($annotation));
        $this->logger->expects($this->once())
            ->method('debug');
        $this->innerAuthorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with($this->equalTo('TEST_PERMISSION'), $this->identicalTo($obj))
            ->will($this->returnValue(true));

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
            ->will($this->returnValue(true));

        $this->assertTrue(
            $this->authorizationChecker->isGranted('', $oid)
        );
    }

    public function testIsGrantedWithRoleName()
    {
        $this->annotationProvider->expects($this->once())
            ->method('findAnnotationById')
            ->with('TestRole')
            ->will($this->returnValue(null));
        $this->innerAuthorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with($this->equalTo('TestRole'), $this->equalTo(null))
            ->will($this->returnValue(true));

        $result = $this->authorizationChecker->isGranted('TestRole');
        $this->assertTrue($result);
    }

    public function testIsGrantedWithRoleNames()
    {
        $this->annotationProvider->expects($this->never())
            ->method('findAnnotationById');
        $this->innerAuthorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with($this->equalTo(array('TestRole1', 'TestRole2')), $this->equalTo(null))
            ->will($this->returnValue(true));

        $result = $this->authorizationChecker->isGranted(array('TestRole1', 'TestRole2'));
        $this->assertTrue($result);
    }

    public function testIsGrantedWithString()
    {
        $oid = new ObjectIdentity('1', 'TestType');
        $obj = 'Entity:SomeClass';
        $this->annotationProvider->expects($this->once())
            ->method('findAnnotationById')
            ->with('PERMISSION')
            ->will($this->returnValue(false));
        $this->objectIdentityFactory->expects($this->at(0))
            ->method('get')
            ->with($this->equalTo($obj))
            ->will($this->returnValue($oid));
        $this->innerAuthorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with($this->equalTo('PERMISSION'), $oid)
            ->will($this->returnValue(true));

        $result = $this->authorizationChecker->isGranted('PERMISSION', $obj);
        $this->assertTrue($result);
    }

    public function testIsGrantedWithCombinedString()
    {
        $this->innerAuthorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with($this->equalTo('VIEW'), 'entity:AcmeDemoBundle:Test')
            ->will($this->returnValue(true));

        $result = $this->authorizationChecker->isGranted('VIEW;entity:AcmeDemoBundle:Test');
        $this->assertTrue($result);
    }

    public function testIsGrantedWithObject()
    {
        $obj = new \stdClass();
        $this->annotationProvider->expects($this->once())
            ->method('findAnnotationById')
            ->with('PERMISSION')
            ->will($this->returnValue(false));
        $this->innerAuthorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with($this->equalTo('PERMISSION'), $this->equalTo($obj))
            ->will($this->returnValue(true));

        $result = $this->authorizationChecker->isGranted('PERMISSION', $obj);
        $this->assertTrue($result);
    }

    public function testIsGrantedForNotAclProtectedClass()
    {
        $obj = 'Test\Class';
        $this->annotationProvider->expects($this->once())
            ->method('findAnnotationById')
            ->with('PERMISSION')
            ->willReturn(false);
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

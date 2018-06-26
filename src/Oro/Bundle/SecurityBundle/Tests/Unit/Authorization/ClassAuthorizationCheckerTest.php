<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Authorization;

use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\SecurityBundle\Annotation\Acl as AclAnnotation;
use Oro\Bundle\SecurityBundle\Authorization\ClassAuthorizationChecker;
use Oro\Bundle\SecurityBundle\Metadata\AclAnnotationProvider;
use Oro\Component\DependencyInjection\ServiceLink;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ClassAuthorizationCheckerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $objectIdentityFactory;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $annotationProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var ClassAuthorizationChecker */
    private $classAuthorizationChecker;

    protected function setUp()
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->objectIdentityFactory = $this->createMock(ObjectIdentityFactory::class);
        $this->annotationProvider = $this->createMock(AclAnnotationProvider::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $container = TestContainerBuilder::create()
            ->add('object_identity_factory', $this->objectIdentityFactory)
            ->add('annotation_provider', $this->annotationProvider)
            ->getContainer($this);

        $this->classAuthorizationChecker = new ClassAuthorizationChecker(
            $this->authorizationChecker,
            new ServiceLink($container, 'object_identity_factory'),
            new ServiceLink($container, 'annotation_provider'),
            $this->logger
        );
    }

    public function testIsClassMethodGrantedDenyingByMethodAcl()
    {
        $oid = new ObjectIdentity('1', 'TestType');
        $annotation = $this->createMock(AclAnnotation::class);
        $annotation->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('method_annotation'));
        $annotation->expects($this->once())
            ->method('getPermission')
            ->will($this->returnValue('TEST_PERMISSION'));

        $this->annotationProvider->expects($this->once())
            ->method('findAnnotation')
            ->with('TestClass', 'TestMethod')
            ->will($this->returnValue($annotation));
        $this->logger->expects($this->once())
            ->method('debug');
        $this->objectIdentityFactory->expects($this->once())
            ->method('get')
            ->with($this->identicalTo($annotation))
            ->will($this->returnValue($oid));
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with($this->equalTo('TEST_PERMISSION'), $this->identicalTo($oid))
            ->will($this->returnValue(false));

        $result = $this->classAuthorizationChecker->isClassMethodGranted('TestClass', 'TestMethod');
        $this->assertFalse($result);
    }

    public function testIsClassMethodGrantedGrantingByMethodAclNoClassAcl()
    {
        $oid = new ObjectIdentity('1', 'TestType');
        $annotation = $this->createMock(AclAnnotation::class);
        $annotation->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('method_annotation'));
        $annotation->expects($this->once())
            ->method('getPermission')
            ->will($this->returnValue('TEST_PERMISSION'));
        $annotation->expects($this->once())
            ->method('getIgnoreClassAcl')
            ->will($this->returnValue(false));

        $this->annotationProvider->expects($this->at(0))
            ->method('findAnnotation')
            ->with('TestClass', 'TestMethod')
            ->will($this->returnValue($annotation));
        $this->annotationProvider->expects($this->at(1))
            ->method('findAnnotation')
            ->with('TestClass')
            ->will($this->returnValue(null));
        $this->logger->expects($this->once())
            ->method('debug');
        $this->objectIdentityFactory->expects($this->once())
            ->method('get')
            ->with($this->identicalTo($annotation))
            ->will($this->returnValue($oid));
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with($this->equalTo('TEST_PERMISSION'), $this->identicalTo($oid))
            ->will($this->returnValue(true));

        $result = $this->classAuthorizationChecker->isClassMethodGranted('TestClass', 'TestMethod');
        $this->assertTrue($result);
    }

    public function testIsClassMethodGrantedGrantingByMethodAclWithIgnoreClassAcl()
    {
        $oid = new ObjectIdentity('1', 'TestType');
        $annotation = $this->createMock(AclAnnotation::class);
        $annotation->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('method_annotation'));
        $annotation->expects($this->once())
            ->method('getPermission')
            ->will($this->returnValue('TEST_PERMISSION'));
        $annotation->expects($this->once())
            ->method('getIgnoreClassAcl')
            ->will($this->returnValue(true));

        $this->annotationProvider->expects($this->once())
            ->method('findAnnotation')
            ->with('TestClass', 'TestMethod')
            ->will($this->returnValue($annotation));
        $this->logger->expects($this->once())
            ->method('debug');
        $this->objectIdentityFactory->expects($this->once())
            ->method('get')
            ->with($this->identicalTo($annotation))
            ->will($this->returnValue($oid));
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with($this->equalTo('TEST_PERMISSION'), $this->identicalTo($oid))
            ->will($this->returnValue(true));

        $result = $this->classAuthorizationChecker->isClassMethodGranted('TestClass', 'TestMethod');
        $this->assertTrue($result);
    }

    public function testIsClassMethodGrantedDenyingByClassAcl()
    {
        $oid = new ObjectIdentity('1', 'TestType');
        $annotation = $this->createMock(AclAnnotation::class);
        $annotation->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('method_annotation'));
        $annotation->expects($this->once())
            ->method('getPermission')
            ->will($this->returnValue('TEST_PERMISSION'));
        $annotation->expects($this->once())
            ->method('getIgnoreClassAcl')
            ->will($this->returnValue(false));

        $classOid = new ObjectIdentity('2', 'TestType');
        $classAnnotation = $this->createMock(AclAnnotation::class);
        $classAnnotation->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('class_annotation'));
        $classAnnotation->expects($this->once())
            ->method('getPermission')
            ->will($this->returnValue('TEST_PERMISSION_CLASS'));

        $this->annotationProvider->expects($this->at(0))
            ->method('findAnnotation')
            ->with('TestClass', 'TestMethod')
            ->will($this->returnValue($annotation));
        $this->annotationProvider->expects($this->at(1))
            ->method('findAnnotation')
            ->with('TestClass')
            ->will($this->returnValue($classAnnotation));
        $this->logger->expects($this->exactly(2))
            ->method('debug');
        $this->objectIdentityFactory->expects($this->at(0))
            ->method('get')
            ->with($this->identicalTo($annotation))
            ->will($this->returnValue($oid));
        $this->objectIdentityFactory->expects($this->at(1))
            ->method('get')
            ->with($this->identicalTo($classAnnotation))
            ->will($this->returnValue($classOid));
        $this->authorizationChecker->expects($this->at(0))
            ->method('isGranted')
            ->with($this->equalTo('TEST_PERMISSION'), $this->identicalTo($oid))
            ->will($this->returnValue(true));
        $this->authorizationChecker->expects($this->at(1))
            ->method('isGranted')
            ->with($this->equalTo('TEST_PERMISSION_CLASS'), $this->identicalTo($classOid))
            ->will($this->returnValue(false));

        $result = $this->classAuthorizationChecker->isClassMethodGranted('TestClass', 'TestMethod');
        $this->assertFalse($result);
    }

    public function testIsClassMethodGrantedGrantingByMethodAndClassAcls()
    {
        $oid = new ObjectIdentity('1', 'TestType');
        $annotation = $this->createMock(AclAnnotation::class);
        $annotation->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('method_annotation'));
        $annotation->expects($this->once())
            ->method('getPermission')
            ->will($this->returnValue('TEST_PERMISSION'));
        $annotation->expects($this->once())
            ->method('getIgnoreClassAcl')
            ->will($this->returnValue(false));

        $classOid = new ObjectIdentity('2', 'TestType');
        $classAnnotation = $this->createMock(AclAnnotation::class);
        $classAnnotation->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('class_annotation'));
        $classAnnotation->expects($this->once())
            ->method('getPermission')
            ->will($this->returnValue('TEST_PERMISSION_CLASS'));

        $this->annotationProvider->expects($this->at(0))
            ->method('findAnnotation')
            ->with('TestClass', 'TestMethod')
            ->will($this->returnValue($annotation));
        $this->annotationProvider->expects($this->at(1))
            ->method('findAnnotation')
            ->with('TestClass')
            ->will($this->returnValue($classAnnotation));
        $this->logger->expects($this->exactly(2))
            ->method('debug');
        $this->objectIdentityFactory->expects($this->at(0))
            ->method('get')
            ->with($this->identicalTo($annotation))
            ->will($this->returnValue($oid));
        $this->objectIdentityFactory->expects($this->at(1))
            ->method('get')
            ->with($this->identicalTo($classAnnotation))
            ->will($this->returnValue($classOid));
        $this->authorizationChecker->expects($this->at(0))
            ->method('isGranted')
            ->with($this->equalTo('TEST_PERMISSION'), $this->identicalTo($oid))
            ->will($this->returnValue(true));
        $this->authorizationChecker->expects($this->at(1))
            ->method('isGranted')
            ->with($this->equalTo('TEST_PERMISSION_CLASS'), $this->identicalTo($classOid))
            ->will($this->returnValue(true));

        $result = $this->classAuthorizationChecker->isClassMethodGranted('TestClass', 'TestMethod');
        $this->assertTrue($result);
    }

    public function testGetClassMethodAnnotation()
    {
        $class = 'TestClass';
        $method = 'TestMethod';
        $annotation = $this->createMock(AclAnnotation::class);

        $this->annotationProvider->expects($this->at(0))
            ->method('findAnnotation')
            ->with($class, $method)
            ->willReturn($annotation);

        $this->assertSame(
            $annotation,
            $this->classAuthorizationChecker->getClassMethodAnnotation($class, $method)
        );
    }

    public function testGetClassMethodAnnotationWhenAnnotationWasNotFound()
    {
        $class = 'TestClass';
        $method = 'TestMethod';

        $this->annotationProvider->expects($this->at(0))
            ->method('findAnnotation')
            ->with($class, $method)
            ->willReturn(null);

        $this->assertNull(
            $this->classAuthorizationChecker->getClassMethodAnnotation($class, $method)
        );
    }
}

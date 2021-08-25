<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Authorization;

use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\SecurityBundle\Annotation\Acl as AclAnnotation;
use Oro\Bundle\SecurityBundle\Authorization\ClassAuthorizationChecker;
use Oro\Bundle\SecurityBundle\Metadata\AclAnnotationProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ClassAuthorizationCheckerTest extends \PHPUnit\Framework\TestCase
{
    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var ObjectIdentityFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $objectIdentityFactory;

    /** @var AclAnnotationProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $annotationProvider;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var ClassAuthorizationChecker */
    private $classAuthorizationChecker;

    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->objectIdentityFactory = $this->createMock(ObjectIdentityFactory::class);
        $this->annotationProvider = $this->createMock(AclAnnotationProvider::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->classAuthorizationChecker = new ClassAuthorizationChecker(
            $this->authorizationChecker,
            $this->objectIdentityFactory,
            $this->annotationProvider,
            $this->logger
        );
    }

    public function testIsClassMethodGrantedDenyingByMethodAcl()
    {
        $oid = new ObjectIdentity('1', 'TestType');
        $annotation = $this->createMock(AclAnnotation::class);
        $annotation->expects($this->once())
            ->method('getId')
            ->willReturn('method_annotation');
        $annotation->expects($this->once())
            ->method('getPermission')
            ->willReturn('TEST_PERMISSION');

        $this->annotationProvider->expects($this->once())
            ->method('findAnnotation')
            ->with('TestClass', 'TestMethod')
            ->willReturn($annotation);
        $this->logger->expects($this->once())
            ->method('debug');
        $this->objectIdentityFactory->expects($this->once())
            ->method('get')
            ->with($this->identicalTo($annotation))
            ->willReturn($oid);
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('TEST_PERMISSION', $this->identicalTo($oid))
            ->willReturn(false);

        $result = $this->classAuthorizationChecker->isClassMethodGranted('TestClass', 'TestMethod');
        $this->assertFalse($result);
    }

    public function testIsClassMethodGrantedGrantingByMethodAclNoClassAcl()
    {
        $oid = new ObjectIdentity('1', 'TestType');
        $annotation = $this->createMock(AclAnnotation::class);
        $annotation->expects($this->once())
            ->method('getId')
            ->willReturn('method_annotation');
        $annotation->expects($this->once())
            ->method('getPermission')
            ->willReturn('TEST_PERMISSION');
        $annotation->expects($this->once())
            ->method('getIgnoreClassAcl')
            ->willReturn(false);

        $this->annotationProvider->expects($this->exactly(2))
            ->method('findAnnotation')
            ->willReturnMap([
                ['TestClass', 'TestMethod', $annotation],
                ['TestClass', null, null]
            ]);
        $this->logger->expects($this->once())
            ->method('debug');
        $this->objectIdentityFactory->expects($this->once())
            ->method('get')
            ->with($this->identicalTo($annotation))
            ->willReturn($oid);
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('TEST_PERMISSION', $this->identicalTo($oid))
            ->willReturn(true);

        $result = $this->classAuthorizationChecker->isClassMethodGranted('TestClass', 'TestMethod');
        $this->assertTrue($result);
    }

    public function testIsClassMethodGrantedGrantingByMethodAclWithIgnoreClassAcl()
    {
        $oid = new ObjectIdentity('1', 'TestType');
        $annotation = $this->createMock(AclAnnotation::class);
        $annotation->expects($this->once())
            ->method('getId')
            ->willReturn('method_annotation');
        $annotation->expects($this->once())
            ->method('getPermission')
            ->willReturn('TEST_PERMISSION');
        $annotation->expects($this->once())
            ->method('getIgnoreClassAcl')
            ->willReturn(true);

        $this->annotationProvider->expects($this->once())
            ->method('findAnnotation')
            ->with('TestClass', 'TestMethod')
            ->willReturn($annotation);
        $this->logger->expects($this->once())
            ->method('debug');
        $this->objectIdentityFactory->expects($this->once())
            ->method('get')
            ->with($this->identicalTo($annotation))
            ->willReturn($oid);
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('TEST_PERMISSION', $this->identicalTo($oid))
            ->willReturn(true);

        $result = $this->classAuthorizationChecker->isClassMethodGranted('TestClass', 'TestMethod');
        $this->assertTrue($result);
    }

    public function testIsClassMethodGrantedDenyingByClassAcl()
    {
        $oid = new ObjectIdentity('1', 'TestType');
        $annotation = $this->createMock(AclAnnotation::class);
        $annotation->expects($this->once())
            ->method('getId')
            ->willReturn('method_annotation');
        $annotation->expects($this->once())
            ->method('getPermission')
            ->willReturn('TEST_PERMISSION');
        $annotation->expects($this->once())
            ->method('getIgnoreClassAcl')
            ->willReturn(false);

        $classOid = new ObjectIdentity('2', 'TestType');
        $classAnnotation = $this->createMock(AclAnnotation::class);
        $classAnnotation->expects($this->once())
            ->method('getId')
            ->willReturn('class_annotation');
        $classAnnotation->expects($this->once())
            ->method('getPermission')
            ->willReturn('TEST_PERMISSION_CLASS');

        $this->annotationProvider->expects($this->exactly(2))
            ->method('findAnnotation')
            ->willReturnMap([
                ['TestClass', 'TestMethod', $annotation],
                ['TestClass', null, $classAnnotation]
            ]);
        $this->logger->expects($this->exactly(2))
            ->method('debug');
        $this->objectIdentityFactory->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [$this->identicalTo($annotation)],
                [$this->identicalTo($classAnnotation)]
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

    public function testIsClassMethodGrantedGrantingByMethodAndClassAcls()
    {
        $oid = new ObjectIdentity('1', 'TestType');
        $annotation = $this->createMock(AclAnnotation::class);
        $annotation->expects($this->once())
            ->method('getId')
            ->willReturn('method_annotation');
        $annotation->expects($this->once())
            ->method('getPermission')
            ->willReturn('TEST_PERMISSION');
        $annotation->expects($this->once())
            ->method('getIgnoreClassAcl')
            ->willReturn(false);

        $classOid = new ObjectIdentity('2', 'TestType');
        $classAnnotation = $this->createMock(AclAnnotation::class);
        $classAnnotation->expects($this->once())
            ->method('getId')
            ->willReturn('class_annotation');
        $classAnnotation->expects($this->once())
            ->method('getPermission')
            ->willReturn('TEST_PERMISSION_CLASS');

        $this->annotationProvider->expects($this->exactly(2))
            ->method('findAnnotation')
            ->willReturnMap([
                ['TestClass', 'TestMethod', $annotation],
                ['TestClass', null, $classAnnotation]
            ]);
        $this->logger->expects($this->exactly(2))
            ->method('debug');
        $this->objectIdentityFactory->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [$this->identicalTo($annotation)],
                [$this->identicalTo($classAnnotation)]
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

    public function testGetClassMethodAnnotation()
    {
        $class = 'TestClass';
        $method = 'TestMethod';
        $annotation = $this->createMock(AclAnnotation::class);

        $this->annotationProvider->expects($this->once())
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

        $this->annotationProvider->expects($this->once())
            ->method('findAnnotation')
            ->with($class, $method)
            ->willReturn(null);

        $this->assertNull(
            $this->classAuthorizationChecker->getClassMethodAnnotation($class, $method)
        );
    }
}

<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Layout\DataProvider\AclProvider;
use Symfony\Component\Security\Acl\Util\ClassUtils;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class AclProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $authorizationChecker;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $tokenAccessor;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $doctrine;

    /** @var AclProvider */
    protected $provider;

    protected function setUp()
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->provider = new AclProvider(
            $this->authorizationChecker,
            $this->tokenAccessor,
            $this->doctrine
        );
    }

    public function testIsGrantedByAclAnnotationId()
    {
        $attributes = 'acme_product_view';
        $expectedResult = true;

        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->with()
            ->will($this->returnValue(true));

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with($attributes, null)
            ->will($this->returnValue($expectedResult));

        $this->assertEquals($expectedResult, $this->provider->isGranted($attributes));
    }

    public function testIsGrantedByObjectIdentityDescriptor()
    {
        $attributes = 'VIEW';
        $entity = 'entity:Acme/DemoBundle/Entity/AcmeEntity';
        $expectedResult = true;

        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->with()
            ->will($this->returnValue(true));

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with($attributes, $entity)
            ->will($this->returnValue($expectedResult));

        $this->assertEquals($expectedResult, $this->provider->isGranted($attributes, $entity));
    }

    public function testIsGrantedForNotEntityObject()
    {
        $attributes = 'VIEW';
        $entity = new \stdClass();
        $expectedResult = true;

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(ClassUtils::getRealClass($entity))
            ->will($this->returnValue(null));

        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->with()
            ->will($this->returnValue(true));

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with($attributes, $this->identicalTo($entity))
            ->will($this->returnValue($expectedResult));

        $this->assertEquals($expectedResult, $this->provider->isGranted($attributes, $entity));
    }

    public function testIsGrantedForExistingEntity()
    {
        $attributes = 'VIEW';
        $entity = new \stdClass();
        $expectedResult = true;

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $uow = $this->getMockBuilder('\Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('getUnitOfWork')
            ->will($this->returnValue($uow));
        $uow->expects($this->once())
            ->method('isScheduledForInsert')
            ->with($entity)
            ->will($this->returnValue(false));
        $uow->expects($this->once())
            ->method('isInIdentityMap')
            ->with($entity)
            ->will($this->returnValue(true));

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(ClassUtils::getRealClass($entity))
            ->will($this->returnValue($em));

        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->with()
            ->will($this->returnValue(true));

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with($attributes, $this->identicalTo($entity))
            ->will($this->returnValue($expectedResult));

        $this->assertEquals($expectedResult, $this->provider->isGranted($attributes, $entity));
    }

    public function testIsGrantedForNewEntity()
    {
        $attributes = 'VIEW';
        $entity = new \stdClass();
        $expectedResult = true;

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $uow = $this->getMockBuilder('\Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('getUnitOfWork')
            ->will($this->returnValue($uow));
        $uow->expects($this->once())
            ->method('isScheduledForInsert')
            ->with($entity)
            ->will($this->returnValue(true));
        $uow->expects($this->never())
            ->method('isInIdentityMap');

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(ClassUtils::getRealClass($entity))
            ->will($this->returnValue($em));

        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->with()
            ->will($this->returnValue(true));

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with($attributes, 'entity:' . ClassUtils::getRealClass($entity))
            ->will($this->returnValue($expectedResult));

        $this->assertEquals($expectedResult, $this->provider->isGranted($attributes, $entity));
    }

    public function testIsGrantedForEntityWhichIsNotInUowYet()
    {
        $attributes = 'VIEW';
        $entity = new \stdClass();
        $expectedResult = true;

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $uow = $this->getMockBuilder('\Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('getUnitOfWork')
            ->will($this->returnValue($uow));
        $uow->expects($this->once())
            ->method('isScheduledForInsert')
            ->with($entity)
            ->will($this->returnValue(false));
        $uow->expects($this->once())
            ->method('isInIdentityMap')
            ->with($entity)
            ->will($this->returnValue(false));

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(ClassUtils::getRealClass($entity))
            ->will($this->returnValue($em));

        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->with()
            ->will($this->returnValue(true));

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with($attributes, 'entity:' . ClassUtils::getRealClass($entity))
            ->will($this->returnValue($expectedResult));

        $this->assertEquals($expectedResult, $this->provider->isGranted($attributes, $entity));
    }

    public function testIsGrantedHasNoUser()
    {
        $attributes = 'acme_product_view';

        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->with()
            ->will($this->returnValue(false));

        $this->authorizationChecker->expects($this->never())
            ->method('isGranted');

        $this->assertFalse($this->provider->isGranted($attributes));
    }
}

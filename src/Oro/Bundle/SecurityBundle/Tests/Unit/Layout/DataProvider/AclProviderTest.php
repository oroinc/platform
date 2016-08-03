<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Layout\DataProvider;

use Symfony\Component\Security\Core\Util\ClassUtils;

use Oro\Bundle\SecurityBundle\Layout\DataProvider\AclProvider;

class AclProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrine;

    /** @var AclProvider */
    protected $provider;

    protected function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new AclProvider($this->securityFacade, $this->doctrine);
    }

    public function testIsGrantedByAclAnnotationId()
    {
        $attributes = 'acme_product_view';
        $expectedResult = true;

        $this->setupSecurityFacade($attributes, null, $expectedResult);

        $this->assertEquals($expectedResult, $this->provider->isGranted($attributes));
    }

    public function testIsGrantedByObjectIdentityDescriptor()
    {
        $attributes = 'VIEW';
        $entity = 'entity:Acme/DemoBundle/Entity/AcmeEntity';
        $expectedResult = true;

        $this->setupSecurityFacade($attributes, $entity, $expectedResult);

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

        $this->setupSecurityFacade($attributes, $this->identicalTo($entity), $expectedResult);

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

        $this->setupSecurityFacade($attributes, $this->identicalTo($entity), $expectedResult);

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

        $this->setupSecurityFacade($attributes, 'entity:'.ClassUtils::getRealClass($entity), $expectedResult);

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

        $this->setupSecurityFacade($attributes, 'entity:'.ClassUtils::getRealClass($entity), $expectedResult);

        $this->assertEquals($expectedResult, $this->provider->isGranted($attributes, $entity));
    }

    public function testIsGrantedHasNoUser()
    {
        $attributes = 'acme_product_view';

        $this->securityFacade->expects($this->once())
            ->method('hasLoggedUser')
            ->with()
            ->will($this->returnValue(false));

        $this->securityFacade->expects($this->never())
            ->method('isGranted');

        $this->assertFalse($this->provider->isGranted($attributes));
    }

    /**
     * @param string|string[] $attributes
     * @param mixed           $object
     * @param bool            $expectedResult
     */
    protected function setupSecurityFacade($attributes, $object, $expectedResult)
    {
        $this->securityFacade->expects($this->once())
            ->method('hasLoggedUser')
            ->with()
            ->will($this->returnValue(true));

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with($attributes, $object)
            ->will($this->returnValue($expectedResult));
    }
}

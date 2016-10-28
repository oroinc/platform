<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Provider\Converters;

use Doctrine\ORM\AbstractQuery;

use Oro\Bundle\DashboardBundle\Provider\Converters\WidgetEntitySelectConverter;
use Oro\Bundle\UserBundle\Entity\User;

class WidgetEntitySelectConverterTest extends \PHPUnit_Framework_TestCase
{
    /** @var AbstractQuery|\PHPUnit_Framework_MockObject_MockObject */
    protected $query;

    /** @var  WidgetEntitySelectConverter */
    protected $converter;

    public function setUp()
    {
        $entityNameResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\EntityNameResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $entityNameResolver->expects($this->any())
            ->method('getName')
            ->willReturnCallback(
                function ($object) {
                    /** @var User $object */
                    return $object->getFirstName() . ' ' . $object->getLastName();
                }
            );

        $doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getResult'])
            ->getMockForAbstractClass();

        $aclHelper = $this->getMockBuilder('Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $aclHelper->expects($this->any())
            ->method('apply')
            ->will($this->returnValue($this->query));

        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $expr = $this->getMockBuilder('Doctrine\ORM\Query\Expr')
            ->disableOriginalConstructor()
            ->getMock();
        $expr->expects($this->any())
            ->method('in')
            ->with()
            ->will($this->returnSelf());

        $queryBuilder->expects($this->any())
            ->method('expr')
            ->willReturn($expr);

        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager->expects($this->any())
            ->method('getRepository')
            ->willReturn($repository);

        $repository->expects($this->any())
            ->method('createQueryBuilder')
            ->with('e')
            ->will($this->returnValue($queryBuilder));

        $this->converter = new WidgetEntitySelectConverter(
            $aclHelper,
            $entityNameResolver,
            $doctrineHelper,
            $entityManager,
            'Oro\Bundle\UserBundle\Entity\User'
        );
    }

    public function testGetViewValueWithoutEntities()
    {
        $this->assertNull($this->converter->getViewValue([]));
    }

    public function testGetViewValueWithOneEntity()
    {
        $user1 = new User();
        $user1->setFirstName('Joe');
        $user1->setLastName('Doe');

        $this->query->expects($this->any())
            ->method('getResult')
            ->will(
                $this->returnValue(
                    [
                        $user1,
                    ]
                )
            );

        $this->assertEquals('Joe Doe', $this->converter->getViewValue([1, 2]));
    }

    public function testGetViewValueWithSeveralEntities()
    {
        $user1 = new User();
        $user1->setFirstName('Joe');
        $user1->setLastName('Doe');

        $user2 = new User();
        $user2->setFirstName('Joyce');
        $user2->setLastName('Palmer');

        $this->query->expects($this->any())
            ->method('getResult')
            ->will(
                $this->returnValue(
                    [
                        $user1,
                        $user2
                    ]
                )
            );

        $this->assertEquals('Joe Doe; Joyce Palmer', $this->converter->getViewValue([1, 2]));
    }
}

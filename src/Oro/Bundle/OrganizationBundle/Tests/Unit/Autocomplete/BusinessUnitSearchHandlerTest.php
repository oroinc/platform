<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Autocomplete;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\OrganizationBundle\Autocomplete\BusinessUnitSearchHandler;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;

class BusinessUnitSearchHandlerTest extends \PHPUnit_Framework_TestCase
{
    const BUSINESS_UNIT_NAME = 'Test Business Unit';
    const BUSINESS_UNIT_ID = 2;
    const USER_ID = 3;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityManager;

    /** @var string */
    protected $className = 'Oro\Bundle\OrganizationBundle\Entity\BusinessUnit';

    /** @var array */
    protected $fields = ['id', 'name'];

    /** @var array */
    protected $displayFields = ['name'];

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $serviceLink;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityContext;

    /** @var BusinessUnitSearchHandler */
    protected $handler;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->serviceLink = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityContext = $this->getMockBuilder('Symfony\Component\Security\Core\SecurityContextInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = new BusinessUnitSearchHandler(
            $this->entityManager, $this->className, $this->fields, $this->displayFields, $this->serviceLink
        );
    }

    public function testSearch()
    {
        $businessUnit = new BusinessUnit();
        $class = new \ReflectionClass($businessUnit);
        $prop = $class->getProperty('id');
        $prop->setAccessible(true);
        $prop->setValue($businessUnit, self::BUSINESS_UNIT_ID);
        $businessUnit->setName(self::BUSINESS_UNIT_NAME);
        $user = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->once())
            ->method('getId')
            ->willReturn(self::USER_ID);
        $user->expects($this->once())
            ->method('getBusinessUnits')
            ->willReturn(new ArrayCollection([$businessUnit]));
        $token = $this->getMockBuilder('Symfony\Component\Security\Core\Authentication\Token\TokenInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        $this->securityContext->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
        $this->serviceLink->expects($this->once())
            ->method('getService')
            ->willReturn($this->securityContext);
        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $queryBuilder->expects($this->once())
            ->method('select')
            ->willReturn($queryBuilder);
        $queryBuilder->expects($this->once())
            ->method('from')
            ->willReturn($queryBuilder);
        $expr0 = $this->getMockBuilder('Doctrine\ORM\Query\Expr')
            ->disableOriginalConstructor()
            ->getMock();
        $expr0->expects($this->once())
            ->method('like');
        $queryBuilder->expects($this->at(2))
            ->method('expr')
            ->willReturn($expr0);
        $queryBuilder->expects($this->once())
            ->method('where')
            ->willReturn($queryBuilder);
        $queryBuilder->expects($this->at(4))
            ->method('setParameter');
        $queryBuilder->expects($this->once())
            ->method('where');
        $expr1 = $this->getMockBuilder('Doctrine\ORM\Query\Expr')
            ->disableOriginalConstructor()
            ->getMock();
        $expr1->expects($this->once())
            ->method('in');
        $queryBuilder->expects($this->at(5))
            ->method('expr')
            ->willReturn($expr1);
        $queryBuilder->expects($this->once())
            ->method('andWhere')
            ->willReturn($queryBuilder);
        $queryBuilder->expects($this->at(7))
            ->method('setParameter')
            ->willReturn($queryBuilder);
        $queryBuilder->expects($this->once())
            ->method('setFirstResult')
            ->willReturn($queryBuilder);
        $queryBuilder->expects($this->once())
            ->method('setMaxResults');
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['execute'])
            ->getMockForAbstractClass();
        $query->expects($this->once())
            ->method('execute')
            ->willReturn([$businessUnit]);
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $this->entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);
        $this->assertEquals(
            [
                'results' => [
                    0 => [
                        'id' => self::BUSINESS_UNIT_ID,
                        'name' => self::BUSINESS_UNIT_NAME,
                    ]
                ],
                'more' => false,
            ],
            $this->handler->search('test', 1, 10)
        );
    }

    public function testGetProperties()
    {
        $this->assertEquals($this->displayFields, $this->handler->getProperties());
    }

    public function testGetEntityName()
    {
        $this->assertEquals($this->className, $this->handler->getEntityName());
    }
}

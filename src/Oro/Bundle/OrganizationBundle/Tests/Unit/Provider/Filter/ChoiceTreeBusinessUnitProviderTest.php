<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Provider\Filter;

use Oro\Bundle\OrganizationBundle\Provider\Filter\ChoiceTreeBusinessUnitProvider;

class ChoiceTreeBusinessUnitProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ChoiceTreeBusinessUnitProvider */
    protected $choiceTreeBUProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $aclHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $treeProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $qb;

    public function setUp()
    {
        $this->qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->setMethods(['getArrayResult', 'expr', 'setParameter'])
            ->getMock();
        $this->qb
            ->select('businessUnit')
            ->from('OroOrganizationBundle:BusinessUnit', 'businessUnit');
        $businessUnitRepository =
            $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Repository\BusinessUnitRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $businessUnitRepository->expects($this->any())
            ->method('getQueryBuilder')
            ->willReturn($this->qb);

        $this->registry       = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->registry
            ->expects($this->any())
            ->method('getRepository')
            ->with('OroOrganizationBundle:BusinessUnit')
            ->willReturn($businessUnitRepository);
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $this->aclHelper      = $this->getMockBuilder('Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->aclHelper->expects($this->any())
            ->method('apply')
            ->willReturn($this->qb);

        $this->treeProvider = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\ChainOwnerTreeProvider')
            ->setMethods(['getTree'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->choiceTreeBUProvider = new ChoiceTreeBusinessUnitProvider(
            $this->registry,
            $this->securityFacade,
            $this->aclHelper,
            $this->treeProvider
        );
    }

    /**
     * @dataProvider getListDataProvider
     */
    public function testGetList($userBUIds, $result)
    {
        $treeOwner = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\OwnerTree')
            ->setMethods(['getUserSubordinateBusinessUnitIds'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->treeProvider->expects($this->once())
            ->method('getTree')
            ->willReturn($treeOwner);

        $treeOwner
            ->expects($this->once())
            ->method('getUserSubordinateBusinessUnitIds')
            ->willReturn($userBUIds);

        $expression = $this->getMockBuilder('Doctrine\ORM\Query\Expr')
            ->setMethods(['in'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->qb->expects($this->once())
            ->method('getArrayResult')
            ->willReturn($result);
        $this->qb->expects($this->any())
            ->method('expr')
            ->willReturn($expression);
        $this->qb->expects($this->any())
            ->method('setParameter');

        $tokenStorage = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');

        $user = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')
            ->setMethods(['getId', 'getOrganizations'])
            ->disableOriginalConstructor()
            ->getMock();

        $organization = $this->getMock('Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface');
        $organization
            ->expects($this->once())
            ->method('getId')
            ->willReturn(1);
        $user->expects($this->once())
            ->method('getOrganizations')
            ->willReturn([$organization]);
        $this->securityFacade->expects($this->once())
            ->method('getToken')
            ->willReturn($tokenStorage);
        $tokenStorage->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $resultedUserBUids = $this->choiceTreeBUProvider->getList();

        $this->assertEquals($result, $resultedUserBUids);
    }

    /**
     * @return array
     */
    public function getListDataProvider()
    {
        return [
            'Three elements in the list' => [
                'userBUIds'        => [1, 2, 3],
                'result'           => [
                    [
                        'name'     => 'Main Business Unit 1',
                        'id'       => 1,
                        'owner_id' => null
                    ],
                    [
                        'name'     => 'Business Unit 1',
                        'id'       => 2,
                        'owner_id' => 1
                    ],
                    [
                        'name'     => 'Business Unit 2',
                        'id'       => 3,
                        'owner_id' => 1
                    ],
                ]
            ],
            'Six elements in the list'   => [
                'userBUIds'        => [1, 2, 3, 4, 5, 6],
                'result'           => [
                    [
                        'name'     => 'Main Business Unit 1',
                        'id'       => 1,
                        'owner_id' => null
                    ],
                    [
                        'name'     => 'Main Business Unit 2',
                        'id'       => 2,
                        'owner_id' => null
                    ],
                    [
                        'name'     => 'Business Unit 1',
                        'id'       => 3,
                        'owner_id' => 1
                    ],
                    [
                        'name'     => 'Business Unit 2',
                        'id'       => 4,
                        'owner_id' => 1
                    ],
                    [
                        'name'     => 'Business Unit 3',
                        'id'       => 5,
                        'owner_id' => 2
                    ],
                    [
                        'name'     => 'Business Unit 4',
                        'id'       => 6,
                        'owner_id' => 2
                    ],
                    [
                        'name'     => 'Business Unit 5',
                        'id'       => 7,
                        'owner_id' => 4
                    ]
                ]
            ],
            'empty list'                 => [
                'userBUIds'        => [],
                'result'           => []
            ],
        ];
    }
}

<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Provider\Filter;

use Oro\Bundle\OrganizationBundle\Provider\Filter\ChoiceTreeBusinessUnitProvider;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeInterface;

class ChoiceTreeBusinessUnitProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ChoiceTreeBusinessUnitProvider */
    protected $choiceTreeBUProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $registry;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $tokenAccessor;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $aclHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $treeProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
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
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
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
            $this->tokenAccessor,
            $this->aclHelper,
            $this->treeProvider
        );
    }

    /**
     * @dataProvider getListDataProvider
     */
    public function testGetList($userBUIds, $result)
    {
        $ownerTree = $this->createMock(OwnerTreeInterface::class);

        $this->treeProvider->expects($this->once())
            ->method('getTree')
            ->willReturn($ownerTree);

        $ownerTree
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

        $user = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')
            ->setMethods(['getId', 'getOrganizations'])
            ->disableOriginalConstructor()
            ->getMock();

        $organization = $this->createMock('Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface');
        $organization
            ->expects($this->once())
            ->method('getId')
            ->willReturn(1);
        $user->expects($this->once())
            ->method('getOrganizations')
            ->willReturn([$organization]);
        $this->tokenAccessor->expects($this->once())
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

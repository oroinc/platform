<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Provider\Filter;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\OrganizationBundle\Entity\Repository\BusinessUnitRepository;
use Oro\Bundle\OrganizationBundle\Provider\Filter\ChoiceTreeBusinessUnitProvider;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SecurityBundle\Owner\ChainOwnerTreeProvider;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeInterface;
use Oro\Bundle\UserBundle\Entity\User;

class ChoiceTreeBusinessUnitProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ChoiceTreeBusinessUnitProvider */
    private $choiceTreeBUProvider;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var ChainOwnerTreeProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $treeProvider;

    /** @var QueryBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $qb;

    /** @var AbstractQuery|\PHPUnit\Framework\MockObject\MockObject */
    private $query;

    protected function setUp(): void
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->treeProvider = $this->createMock(ChainOwnerTreeProvider::class);

        $this->query = $this->createMock(AbstractQuery::class);
        $this->qb = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['expr', 'setParameter', 'getQuery'])
            ->getMock();
        $this->qb
            ->select('businessUnit')
            ->from('OroOrganizationBundle:BusinessUnit', 'businessUnit');
        $this->qb->expects($this->any())
            ->method('getQuery')
            ->willReturn($this->query);
        $businessUnitRepository = $this->createMock(BusinessUnitRepository::class);
        $businessUnitRepository->expects($this->any())
            ->method('getQueryBuilder')
            ->willReturn($this->qb);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getRepository')
            ->with('OroOrganizationBundle:BusinessUnit')
            ->willReturn($businessUnitRepository);

        $aclHelper = $this->createMock(AclHelper::class);
        $aclHelper->expects($this->any())
            ->method('apply')
            ->willReturn($this->query);

        $this->choiceTreeBUProvider = new ChoiceTreeBusinessUnitProvider(
            $doctrine,
            $this->tokenAccessor,
            $aclHelper,
            $this->treeProvider
        );
    }

    /**
     * @dataProvider getListDataProvider
     */
    public function testGetList(array $userBUIds, array $result)
    {
        $ownerTree = $this->createMock(OwnerTreeInterface::class);

        $this->treeProvider->expects($this->once())
            ->method('getTree')
            ->willReturn($ownerTree);

        $ownerTree->expects($this->once())
            ->method('getUserSubordinateBusinessUnitIds')
            ->willReturn($userBUIds);

        $expression = $this->getMockBuilder(Expr::class)
            ->onlyMethods(['in'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->query->expects($this->once())
            ->method('getArrayResult')
            ->willReturn($result);
        $this->qb->expects($this->any())
            ->method('expr')
            ->willReturn($expression);
        $this->qb->expects($this->any())
            ->method('setParameter')
            ->willReturnSelf();

        $organization = $this->createMock(OrganizationInterface::class);
        $organization->expects($this->once())
            ->method('getId')
            ->willReturn(1);
        $user = $this->createMock(User::class);
        $user->expects($this->once())
            ->method('getOrganizations')
            ->willReturn([$organization]);
        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $resultedUserBUids = $this->choiceTreeBUProvider->getList();

        $this->assertEquals($result, $resultedUserBUids);
    }

    public function getListDataProvider(): array
    {
        return [
            'Three elements in the list' => [
                'userBUIds' => [1, 2, 3],
                'result'    => [
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
                'userBUIds' => [1, 2, 3, 4, 5, 6],
                'result'    => [
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
                'userBUIds' => [],
                'result'    => []
            ],
        ];
    }
}

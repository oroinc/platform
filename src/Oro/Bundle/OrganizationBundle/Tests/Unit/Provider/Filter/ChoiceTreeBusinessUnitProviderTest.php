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

    public function setUp()
    {
        $this->registry       = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $this->aclHelper      = $this->getMockBuilder('Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper')
            ->disableOriginalConstructor()
            ->getMock();

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
    public function testGetList($userBUIds, $subordinateBUIds, $times, $queryResult, $result)
    {
        $businessUnitRepos = $this->getMockBuilder('BusinessUnitRepository')
            ->disableOriginalConstructor()
            ->setMethods(['getQueryBuilder'])
            ->getMock();

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->setMethods(['getResult', 'expr', 'setParameter'])
            ->disableOriginalConstructor()
            ->getMock();

        $treeOwner = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\OwnerTree')
            ->setMethods(['getUserBusinessUnitIds', 'getSubordinateBusinessUnitIds'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->treeProvider->expects(self::once())->method('getTree')->willReturn($treeOwner);
        $treeOwner->expects(self::once())->method('getUserBusinessUnitIds')->willReturn($userBUIds);
        $treeOwner
            ->expects(self::exactly($times))
            ->method('getSubordinateBusinessUnitIds')
            ->willReturn($subordinateBUIds);

        $this->aclHelper->expects(self::any())->method('apply')->willReturn($qb);
        $businessUnitRepos->expects(self::any())->method('getQueryBuilder')->willReturn($qb);

        $expression = $this->getMockBuilder('Doctrine\ORM\Query\Expr')
            ->setMethods(['in'])
            ->disableOriginalConstructor()
            ->getMock();

        $qb->expects(self::once())->method('getResult')->willReturn($queryResult);
        $qb->expects(self::once())->method('expr')->willReturn($expression);
        $qb->expects(self::once())->method('setParameter');

        $tokenStorage = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');

        $user = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')
            ->setMethods(['getId', 'getOrganization'])
            ->disableOriginalConstructor()
            ->getMock();

        $organization = $this->getMock('Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface');
        $organization->expects(self::once())->method('getId')->willReturn(1);
        $user->expects(self::once())->method('getOrganization')->willReturn($organization);
        $this->securityFacade->expects(self::once())->method('getToken')->willReturn($tokenStorage);
        $tokenStorage->expects(self::once())->method('getUser')->willReturn($user);

        $this->registry
            ->expects(self::once())
            ->method('getRepository')
            ->with('OroOrganizationBundle:BusinessUnit')
            ->willReturn($businessUnitRepos);

        $resultedUserBUids = $this->choiceTreeBUProvider->getList();

        self::assertEquals($result, $resultedUserBUids);
    }

    /**
     * @return array
     */
    public function getListDataProvider()
    {
        return [
            'Three elements in the list' => [
                'userBUIds'        => [1],
                'subordinateBUIds' => [2, 3],
                'times'            => 1,
                'queryResult'      => $this->getBusinessUnits('one'),
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
                'userBUIds'        => [1, 2],
                'subordinateBUIds' => [3, 4, 5, 6],
                'times'            => 2,
                'queryResult'      => $this->getBusinessUnits('two'),
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
                'subordinateBUIds' => [],
                'times'            => 0,
                'queryResult'      => [],
                'result'           => []
            ],
        ];
    }

    /**
     * @param string $name
     *
     * @return array
     */
    protected function getBusinessUnits($name)
    {
        $scheme = [
            'one' => [
                ['name' => 'Main Business Unit 1', 'owner' => null, 'id' => 1],
                ['name' => 'Business Unit 1', 'owner' => 1, 'id' => 2],
                ['name' => 'Business Unit 2', 'owner' => 1, 'id' => 3]
            ],
            'two' => [
                ['name' => 'Main Business Unit 1', 'owner' => null, 'id' => 1],
                ['name' => 'Main Business Unit 2', 'owner' => null, 'id' => 2],
                ['name' => 'Business Unit 1', 'owner' => 1, 'id' => 3],
                ['name' => 'Business Unit 2', 'owner' => 1, 'id' => 4],
                ['name' => 'Business Unit 3', 'owner' => 2, 'id' => 5],
                ['name' => 'Business Unit 4', 'owner' => 2, 'id' => 6],
                ['name' => 'Business Unit 5', 'owner' => 4, 'id' => 7],
            ],
        ];

        $result         = [];
        $schemeSet      = $scheme[$name];
        $schemeSetCount = count($schemeSet);

        for ($i = 0; $i < $schemeSetCount; $i++) {
            $element = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\BusinessUnit')
                ->disableOriginalConstructor()
                ->getMock();

            $owner = (null === $schemeSet[$i]['owner'])
                ? $schemeSet[$i]['owner']
                : $result[$schemeSet[$i]['owner'] - 1];

            $element->expects(self::any())->method('getOwner')->willReturn($owner);
            $element->expects(self::any())->method('getName')->willReturn($schemeSet[$i]['name']);
            $element->expects(self::any())->method('getId')->willReturn($schemeSet[$i]['id']);

            $result[] = $element;
        }

        return $result;
    }
}

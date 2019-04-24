<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Owner;

use Oro\Bundle\SecurityBundle\Owner\OwnerTree;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeBuilderInterface;
use Oro\Bundle\SecurityBundle\Tests\Util\ReflectionUtil;
use Psr\Log\LoggerInterface;

class OwnerTreeTest extends \PHPUnit\Framework\TestCase
{
    /** @var OwnerTreeBuilderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $tree;

    protected function setUp()
    {
        $this->tree = new OwnerTree();
    }

    /**
     * @dataProvider addBusinessUnitRelationProvider
     */
    public function testAddBusinessUnitRelation($src, $expected)
    {
        foreach ($src as $item) {
            $this->tree->addBusinessUnitRelation($item[0], $item[1]);
        }
        $this->tree->buildTree();

        foreach ($expected as $buId => $sBuIds) {
            $this->assertEquals(
                $sBuIds,
                $this->tree->getSubordinateBusinessUnitIds($buId),
                sprintf('Failed for %s', $buId)
            );
        }
    }

    public function testAddBusinessUnitShouldSetOwningOrganizationIdEvenIfItIsNull()
    {
        $this->tree->addBusinessUnit('bu1', null);
        $this->assertNull($this->tree->getBusinessUnitOrganizationId('bu1'));

        $this->tree->addBusinessUnit('bu2', 'org');
        $this->assertEquals('org', $this->tree->getBusinessUnitOrganizationId('bu2'));
    }

    public function testAddBusinessUnitShouldSetOrganizationBusinessUnitIdsOnlyIfOrganizationIsNotNull()
    {
        $this->tree->addBusinessUnit('bu1', null);
        $this->assertEquals(array(), $this->tree->getOrganizationBusinessUnitIds('bu1'));

        $this->tree->addBusinessUnit('bu2', 'org');
        $this->assertEquals(array('bu2'), $this->tree->getOrganizationBusinessUnitIds('org'));

        $this->tree->addBusinessUnit('bu3', 'org');
        $this->assertEquals(array('bu2', 'bu3'), $this->tree->getOrganizationBusinessUnitIds('org'));
    }

    public function testAddBusinessUnitShouldSetUserOwningOrganizationId()
    {
        $this->tree->addUser('user', 'bu');

        $this->tree->addBusinessUnit('bu', 'org');
        $this->assertEquals('org', $this->tree->getUserOrganizationId('user'));
    }

    public function testAddBusinessUnitShouldNotSetUserOrganizationIds()
    {
        $this->tree->addUser('user', 'bu');

        $this->tree->addBusinessUnit('bu', 'org');
        $this->assertEquals(array(), $this->tree->getUserOrganizationIds('user'));
    }

    public function testAddBusinessUnitShouldNotSetUserOrganizationIdsIfOrganizationIdIsNull()
    {
        $this->tree->addUser('user', 'bu');

        $this->tree->addBusinessUnit('bu', null);
        $this->assertEquals(array(), $this->tree->getUserOrganizationIds('user'));
    }

    public function testAddUserShouldSetUserOwningBusinessUnitId()
    {
        $this->tree->addUser('user', 'bu');
        $this->assertEquals('bu', $this->tree->getUserBusinessUnitId('user'));
    }

    public function testAddUserShouldSetUserOwningBusinessUnitIdEvenIfItIsNull()
    {
        $this->tree->addUser('user', null);
        $this->assertNull($this->tree->getUserBusinessUnitId('user'));
    }

    public function testAddUserShouldSetUserBusinessUnitIds()
    {
        $this->tree->addUser('user', null);
        $this->assertEquals(array(), $this->tree->getUserBusinessUnitIds('user'));
    }

    public function testAddUserShouldNotSetUserOrganizationIds()
    {
        $this->tree->addBusinessUnit('bu', 'org');

        $this->tree->addUser('user', 'bu');
        $this->assertEquals(array(), $this->tree->getUserOrganizationIds('user'));
    }

    public function testAddUserShouldNotSetUserOrganizationIdsIfOrganizationIdIsNull()
    {
        $this->tree->addBusinessUnit('bu', null);

        $this->tree->addUser('user', 'bu');
        $this->assertEquals(array(), $this->tree->getUserOrganizationIds('user'));
    }

    public function testAddUserShouldSetUserOwningOrganizationId()
    {
        $this->tree->addBusinessUnit('bu', 'org');

        $this->tree->addUser('user', 'bu');
        $this->assertEquals('org', $this->tree->getUserOrganizationId('user'));
    }

    public function testAddUserShouldSetUserOwningOrganizationIdEvenIfOrganizationIdIsNull()
    {
        $this->tree->addBusinessUnit('bu', null);

        $this->tree->addUser('user', 'bu');
        $this->assertNull($this->tree->getUserOrganizationId('user'));
    }

    public function testAddUserBusinessUnitShouldNotSetUserBusinessUnitIdsIfBusinessUnitIdIsNull()
    {
        $this->tree->addUser('user', null);

        $this->tree->addUserBusinessUnit('user', 'org1', null);
        $this->assertEquals(array(), $this->tree->getUserBusinessUnitIds('user'));
    }

    public function testAddUserBusinessUnitShouldSetUserBusinessUnitIds()
    {
        $this->tree->addUser('user', null);

        $this->tree->addUserBusinessUnit('user', 'org1', 'bu');
        $this->assertEquals(array('bu'), $this->tree->getUserBusinessUnitIds('user'));

        $this->tree->addUserBusinessUnit('user', 'org1', 'bu1');
        $this->assertEquals(array('bu', 'bu1'), $this->tree->getUserBusinessUnitIds('user'));
        $this->assertEquals(array('bu', 'bu1'), $this->tree->getUserBusinessUnitIds('user', 'org1'));
    }

    public function testAddUserBusinessUnitShouldNotSetUserOrganizationIdsIfOrganizationIdIsNull()
    {
        $this->tree->addBusinessUnit('bu', null);
        $this->tree->addUser('user', null);

        $this->tree->addUserBusinessUnit('user', 'org1', 'bu');
        $this->assertEquals(array(), $this->tree->getUserOrganizationIds('user'));
    }

    public function testAddUserBusinessUnitShouldSetUserOrganizationIds()
    {
        $this->tree->addBusinessUnit('bu', 'org');
        $this->tree->addUser('user', null);

        $this->tree->addUserOrganization('user', 'org');
        $this->assertEquals(array('org'), $this->tree->getUserOrganizationIds('user'));
    }

    public function testAddUserBusinessUnitBelongToDifferentOrganizations()
    {
        $this->tree->addUser('user', null);

        $this->tree->addBusinessUnit('bu1', null);
        $this->assertNull($this->tree->getBusinessUnitOrganizationId('bu1'));
        $this->tree->addBusinessUnit('bu2', 'org2');
        $this->assertEquals('org2', $this->tree->getBusinessUnitOrganizationId('bu2'));
        $this->tree->addBusinessUnit('bu3', 'org3');
        $this->assertEquals('org3', $this->tree->getBusinessUnitOrganizationId('bu3'));

        $this->tree->addUserBusinessUnit('user', null, null);
        $this->assertEquals(array(), $this->tree->getUserBusinessUnitIds('user'));
        $this->assertNull($this->tree->getUserOrganizationId('user'));
        $this->assertEquals(array(), $this->tree->getUserOrganizationIds('user'));
        $this->assertEquals(array(), $this->tree->getUserSubordinateBusinessUnitIds('user', 'org1'));
        $this->assertEquals(array(), $this->tree->getBusinessUnitsIdByUserOrganizations('user'));

        $this->tree->addUserBusinessUnit('user', 'org1', 'bu1');
        $this->assertEquals(array('bu1'), $this->tree->getUserBusinessUnitIds('user'));
        $this->assertNull($this->tree->getUserOrganizationId('user'));
        $this->assertEquals(array(), $this->tree->getUserOrganizationIds('user'));
        $this->assertEquals(array('bu1'), $this->tree->getUserSubordinateBusinessUnitIds('user', 'org1'));
        $this->assertEquals(array(), $this->tree->getBusinessUnitsIdByUserOrganizations('user'));

        $this->tree->addUserBusinessUnit('user', 'org2', 'bu2');
        $this->tree->addUserOrganization('user', 'org2');
        $this->assertEquals(array('bu1', 'bu2'), $this->tree->getUserBusinessUnitIds('user'));
        $this->assertEquals(array('bu2'), $this->tree->getUserBusinessUnitIds('user', 'org2'));
        $this->assertNull($this->tree->getUserOrganizationId('user'));
        $this->assertEquals(array('org2'), $this->tree->getUserOrganizationIds('user'));
        $this->assertEquals(array('bu2'), $this->tree->getUserSubordinateBusinessUnitIds('user', 'org2'));
        $this->assertEquals(array('bu1', 'bu2'), $this->tree->getUserSubordinateBusinessUnitIds('user'));
        $this->assertEquals(array('bu2'), $this->tree->getBusinessUnitsIdByUserOrganizations('user', 'org2'));

        $this->tree->addUserBusinessUnit('user', 'org3', 'bu3');
        $this->tree->addUserOrganization('user', 'org3');
        $this->assertEquals(array('bu1', 'bu2', 'bu3'), $this->tree->getUserBusinessUnitIds('user'));
        $this->assertNull($this->tree->getUserOrganizationId('user'));
        $this->assertEquals(array('org2', 'org3'), $this->tree->getUserOrganizationIds('user'));
        $this->assertEquals(array('bu1', 'bu2', 'bu3'), $this->tree->getUserSubordinateBusinessUnitIds('user'));
        $this->assertEquals(array('bu3'), $this->tree->getUserSubordinateBusinessUnitIds('user', 'org3'));
        $this->assertEquals(array('bu2', 'bu3'), $this->tree->getBusinessUnitsIdByUserOrganizations('user'));
    }

    public function testAddBusinessUsersAndGetAllBusinessUnitIds()
    {
        $this->tree->addBusinessUnit('bu1', 1);
        $this->tree->addBusinessUnit('bu2', 2);
        $this->tree->addBusinessUnit('bu3', 2);
        $this->tree->addBusinessUnit('bu4', 3);

        $this->assertEquals(['bu1', 'bu2', 'bu3', 'bu4'], $this->tree->getAllBusinessUnitIds());
    }

    public static function addBusinessUnitRelationProvider()
    {
        return array(
            '1: [null]' => array(
                array(
                    array('1', null),
                ),
                array(
                    '1' => array(),
                )
            ),
            '1: [11, 12]' => array(
                array(
                    array('1', null),
                    array('11', '1'),
                    array('12', '1'),
                ),
                array(
                    '1' => array('11', '12'),
                    '11' => array(),
                    '12' => array(),
                )
            ),
            '1: [11, 12] reverse' => array(
                array(
                    array('1', null),
                    array('12', '1'),
                    array('11', '1'),
                ),
                array(
                    '1' => array('12', '11'),
                    '11' => array(),
                    '12' => array(),
                )
            ),
            '1: [11: [111], 12]' => array(
                array(
                    array('1', null),
                    array('11', '1'),
                    array('12', '1'),
                    array('111', '11'),
                ),
                array(
                    '1' => array('11', '111', '12'),
                    '11' => array('111'),
                    '111' => array(),
                    '12' => array(),
                )
            ),
            '1: [11: [111: [1111, 1112]], 12: [121, 122: [1221]]]' => array(
                array(
                    array('1', null),
                    array('11', '1'),
                    array('12', '1'),
                    array('111', '11'),
                    array('121', '12'),
                    array('122', '12'),
                    array('1111', '111'),
                    array('1112', '111'),
                    array('1221', '122'),
                ),
                array(
                    '1' => array('11', '111', '1111', '1112', '12', '121', '122',  '1221'),
                    '11' => array('111', '1111', '1112'),
                    '111' => array('1111', '1112'),
                    '1111' => array(),
                    '1112' => array(),
                    '12' => array('121', '122', '1221'),
                    '121' => array(),
                    '122' => array('1221'),
                )
            ),
            'unknown parent' => array(
                array(
                    array('1', null),
                    array('11', '1'),
                    array('12', '2'),
                ),
                array(
                    '1' => array('11'),
                    '11' => array(),
                )
            ),
            'child loaded before parent' => array(
                array(
                    array('111', '11'),
                    array('11', '1'),
                    array('12', '1'),
                    array('1', null),
                ),
                array(
                    '1' => array('11', '111', '12'),
                    '11' => array('111'),
                    '111' => array(),
                    '12' => array(),
                )
            ),
        );
    }

    /**
     * @dataProvider getUsersAssignedToBusinessUnitsProvider
     */
    public function testGetUsersAssignedToBusinessUnits(array $businessUnitIds, array $expectedOwnerIds)
    {
        $this->tree->addUserBusinessUnit(1, 1, 1);
        $this->tree->addUserBusinessUnit(1, 1, 2);
        $this->tree->addUserBusinessUnit(2, 1, 3);
        $this->tree->addUserBusinessUnit(3, 1, 3);
        $this->tree->addUserBusinessUnit(4, 1, 3);

        $this->assertEquals($expectedOwnerIds, $this->tree->getUsersAssignedToBusinessUnits($businessUnitIds));
    }

    public function getUsersAssignedToBusinessUnitsProvider()
    {
        return [
            'non existing bu' => [
                [4],
                [],
            ],
            [
                [1],
                [1],
            ],
            [
                [1, 2],
                [1],
            ],
            [
                [3],
                [2, 3, 4],
            ],
            [
                [1, 3],
                [1, 2, 3, 4],
            ],
            'ids without duplicities' => [
                [1, 2, 3],
                [1, 2, 3, 4],
            ],
        ];
    }

    /**
     * @dataProvider addBusinessUnitDirectCyclicRelationProvider
     */
    public function testDirectCyclicRelationshipBetweenBusinessUnits($src, $expected, $criticalMessageArguments)
    {
        $logger = $this->createMock(LoggerInterface::class);
        $this->tree->setLogger($logger);
        $this->tree->setBusinessUnitClass('TestClass');

        $logger->expects($this->once())
            ->method('critical')
            ->with(
                sprintf(
                    'Cyclic relationship in "%s" with problem id "%s"',
                    'TestClass',
                    $criticalMessageArguments['buId']
                )
            );

        foreach ($src as $item) {
            $this->tree->addBusinessUnitRelation($item[0], $item[1]);
        }
        $this->tree->buildTree();

        foreach ($expected as $parentBusinessUnitId => $businessUnitIds) {
            $this->assertEquals($businessUnitIds, $this->tree->getSubordinateBusinessUnitIds($parentBusinessUnitId));
        }
    }
    /**
     * @dataProvider addBusinessUnitNotDirectCyclicRelationProvider
     */
    public function testNotDirectCyclicRelationshipBetweenBusinessUnits($src, $expected, $criticalMessageArguments)
    {
        $logger = $this->createMock(LoggerInterface::class);
        $this->tree->setLogger($logger);
        $this->tree->setBusinessUnitClass('TestClass');

        $logger->expects($this->exactly(count($criticalMessageArguments)))
            ->method('critical')
            ->withConsecutive(
                [sprintf(
                    'Cyclic relationship in "%s" with problem id "%s"',
                    'TestClass',
                    $criticalMessageArguments[0]['buId']
                )],
                [sprintf(
                    'Cyclic relationship in "%s" with problem id "%s"',
                    'TestClass',
                    $criticalMessageArguments[1]['buId']
                )],
                [sprintf(
                    'Cyclic relationship in "%s" with problem id "%s"',
                    'TestClass',
                    $criticalMessageArguments[2]['buId']
                )]
            );

        foreach ($src as $item) {
            $this->tree->addBusinessUnitRelation($item[0], $item[1]);
        }
        $this->tree->buildTree();
        foreach ($expected as $parentBusinessUnitId => $businessUnitIds) {
            $this->assertEquals($businessUnitIds, $this->tree->getSubordinateBusinessUnitIds($parentBusinessUnitId));
        }
    }
    /**
     * @return array
     */
    public function addBusinessUnitDirectCyclicRelationProvider()
    {
        return [
            'direct cyclic relationship' => [
                [
                    [2, 4],
                    [1, null],
                    [3, 1],
                    [4, 2]
                ],
                [
                    1 => [3]
                ],
                [
                    'parentBuId' => 4,
                    'buId' => 2
                ]
            ]
        ];
    }
    /**
     * @return array
     */
    public function addBusinessUnitNotDirectCyclicRelationProvider()
    {
        return [
            'not direct cyclic relationship' => [
                [
                    [1, null],
                    [3, 1],
                    [4, 1],
                    [5, 7],
                    [6, 5],
                    [7, 6],
                    [8, 14],
                    [11, 8],
                    [12, 11],
                    [13, 12],
                    [14, 13]
                ],
                [
                    1 => [3, 4]
                ],
                [
                    [
                        'buId' => 5
                    ],
                    [
                        'buId' => 8
                    ],
                    [
                        'buId' => 12
                    ]
                ]
            ]
        ];
    }
}

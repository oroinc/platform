<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Owner;

use Oro\Bundle\SecurityBundle\Owner\OwnerTree;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeBuilderInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class OwnerTreeTest extends \PHPUnit\Framework\TestCase
{
    /** @var OwnerTreeBuilderInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $tree;

    protected function setUp(): void
    {
        $this->tree = new OwnerTree();
    }

    /**
     * @dataProvider setSubordinateBusinessUnitIdsProvider
     */
    public function testSetSubordinateBusinessUnitIds($src, $expected)
    {
        foreach ($src as $parent => $buIds) {
            $this->tree->setSubordinateBusinessUnitIds($parent, $buIds);
        }

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

    public static function setSubordinateBusinessUnitIdsProvider()
    {
        return [
            '1: [11, 12]' => [
                [
                    '1'  => ['11','12'],
                ],
                [
                    '1' => ['11', '12'],
                    '11' => [],
                    '12' => [],
                ]

            ],
            '1: [11: [111], 12]' => [
                [
                    '1'  => ['11', '12', '111'],
                    '11' => ['111']

                ],
                [
                    '1' => ['11', '12', '111'],
                    '11' => ['111'],
                    '111' => [],
                    '12' => [],
                ]
            ],
            '1: [11: [111: [1111, 1112]], 12: [121, 122: [1221]]]' => [
                [
                    '1'   => ['11', '12', '111', '121', '122', '1111', '1112', '1221'],
                    '11'  => ['111', '1111', '1112'],
                    '12'  => ['121', '122', '1221'],
                    '111' => ['1111', '1112'],
                    '122' => ['1221']
                ],
                [
                    '1'    => ['11', '12', '111', '121', '122', '1111', '1112', '1221'],
                    '11'   => ['111', '1111', '1112'],
                    '12'   => ['121', '122', '1221'],
                    '111'  => ['1111', '1112'],
                    '122'  => ['1221'],
                    '1111' => [],
                    '1112' => [],
                    '121'  => [],
                ]
            ],
            'unknown parent' => [
                [
                    '1'  => ['11'],
                    '2'  => ['12']
                ],
                [
                    '1'  => ['11'],
                    '11' => [],
                ]
            ],
            'child loaded before parent' => [
                [
                    '1'  => ['11','12','111'],
                    '11' => ['111']
                ],
                [

                   '1'   => ['11', '12', '111'],
                   '11'  => ['111'],
                   '111' => [],
                   '12'  => []
                ]
            ]
        ];
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
}

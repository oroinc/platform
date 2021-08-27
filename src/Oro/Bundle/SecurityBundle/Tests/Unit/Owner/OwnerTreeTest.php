<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Owner;

use Oro\Bundle\SecurityBundle\Owner\OwnerTree;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class OwnerTreeTest extends \PHPUnit\Framework\TestCase
{
    private const ORG_ID = 10;
    private const BU_ID = 100;
    private const USER_ID = 10000;

    private OwnerTree $tree;

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
        $this->tree->addBusinessUnit(self::BU_ID + 1, null);
        $this->assertNull($this->tree->getBusinessUnitOrganizationId(self::BU_ID + 1));

        $this->tree->addBusinessUnit(self::BU_ID + 2, self::ORG_ID);
        $this->assertEquals(self::ORG_ID, $this->tree->getBusinessUnitOrganizationId(self::BU_ID + 2));
    }

    public function testAddBusinessUnitShouldSetOrganizationBusinessUnitIdsOnlyIfOrganizationIsNotNull()
    {
        $this->tree->addBusinessUnit(self::BU_ID + 1, null);
        $this->assertEquals([], $this->tree->getOrganizationBusinessUnitIds(self::BU_ID + 1));

        $this->tree->addBusinessUnit(self::BU_ID + 2, self::ORG_ID);
        $this->assertEquals([self::BU_ID + 2], $this->tree->getOrganizationBusinessUnitIds(self::ORG_ID));

        $this->tree->addBusinessUnit(self::BU_ID + 3, self::ORG_ID);
        $this->assertEquals(
            [self::BU_ID + 2, self::BU_ID + 3],
            $this->tree->getOrganizationBusinessUnitIds(self::ORG_ID)
        );
    }

    public function testAddBusinessUnitShouldSetUserOwningOrganizationId()
    {
        $this->tree->addUser(self::USER_ID, self::BU_ID);

        $this->tree->addBusinessUnit(self::BU_ID, self::ORG_ID);
        $this->assertEquals(self::ORG_ID, $this->tree->getUserOrganizationId(self::USER_ID));
    }

    public function testAddBusinessUnitShouldNotSetUserOrganizationIds()
    {
        $this->tree->addUser(self::USER_ID, self::BU_ID);

        $this->tree->addBusinessUnit(self::BU_ID, self::ORG_ID);
        $this->assertEquals([], $this->tree->getUserOrganizationIds(self::USER_ID));
    }

    public function testAddBusinessUnitShouldNotSetUserOrganizationIdsIfOrganizationIdIsNull()
    {
        $this->tree->addUser(self::USER_ID, self::BU_ID);

        $this->tree->addBusinessUnit(self::BU_ID, null);
        $this->assertEquals([], $this->tree->getUserOrganizationIds(self::USER_ID));
    }

    public function testAddUserShouldSetUserOwningBusinessUnitId()
    {
        $this->tree->addUser(self::USER_ID, self::BU_ID);
        $this->assertEquals(self::BU_ID, $this->tree->getUserBusinessUnitId(self::USER_ID));
    }

    public function testAddUserShouldSetUserOwningBusinessUnitIdEvenIfItIsNull()
    {
        $this->tree->addUser(self::USER_ID, null);
        $this->assertNull($this->tree->getUserBusinessUnitId(self::USER_ID));
    }

    public function testAddUserShouldSetUserBusinessUnitIds()
    {
        $this->tree->addUser(self::USER_ID, null);
        $this->assertEquals([], $this->tree->getUserBusinessUnitIds(self::USER_ID));
    }

    public function testAddUserShouldNotSetUserOrganizationIds()
    {
        $this->tree->addBusinessUnit(self::BU_ID, self::ORG_ID);

        $this->tree->addUser(self::USER_ID, self::BU_ID);
        $this->assertEquals([], $this->tree->getUserOrganizationIds(self::USER_ID));
    }

    public function testAddUserShouldNotSetUserOrganizationIdsIfOrganizationIdIsNull()
    {
        $this->tree->addBusinessUnit(self::BU_ID, null);

        $this->tree->addUser(self::USER_ID, self::BU_ID);
        $this->assertEquals([], $this->tree->getUserOrganizationIds(self::USER_ID));
    }

    public function testAddUserShouldSetUserOwningOrganizationId()
    {
        $this->tree->addBusinessUnit(self::BU_ID, self::ORG_ID);

        $this->tree->addUser(self::USER_ID, self::BU_ID);
        $this->assertEquals(self::ORG_ID, $this->tree->getUserOrganizationId(self::USER_ID));
    }

    public function testAddUserShouldSetUserOwningOrganizationIdEvenIfOrganizationIdIsNull()
    {
        $this->tree->addBusinessUnit(self::BU_ID, null);

        $this->tree->addUser(self::USER_ID, self::BU_ID);
        $this->assertNull($this->tree->getUserOrganizationId(self::USER_ID));
    }

    public function testAddUserBusinessUnitShouldNotSetUserBusinessUnitIdsIfBusinessUnitIdIsNull()
    {
        $this->tree->addUser(self::USER_ID, null);

        $this->tree->addUserBusinessUnit(self::USER_ID, self::ORG_ID + 1, null);
        $this->assertEquals([], $this->tree->getUserBusinessUnitIds(self::USER_ID));
    }

    public function testAddUserBusinessUnitShouldSetUserBusinessUnitIds()
    {
        $this->tree->addUser(self::USER_ID, null);

        $this->tree->addUserBusinessUnit(self::USER_ID, self::ORG_ID + 1, self::BU_ID);
        $this->assertEquals([self::BU_ID], $this->tree->getUserBusinessUnitIds(self::USER_ID));

        $this->tree->addUserBusinessUnit(self::USER_ID, self::ORG_ID + 1, self::BU_ID + 1);
        $this->assertEquals([self::BU_ID, self::BU_ID + 1], $this->tree->getUserBusinessUnitIds(self::USER_ID));
        $this->assertEquals(
            [self::BU_ID, self::BU_ID + 1],
            $this->tree->getUserBusinessUnitIds(self::USER_ID, self::ORG_ID + 1)
        );
    }

    public function testAddUserBusinessUnitShouldNotSetUserOrganizationIdsIfOrganizationIdIsNull()
    {
        $this->tree->addBusinessUnit(self::BU_ID, null);
        $this->tree->addUser(self::USER_ID, null);

        $this->tree->addUserBusinessUnit(self::USER_ID, self::ORG_ID + 1, self::BU_ID);
        $this->assertEquals([], $this->tree->getUserOrganizationIds(self::USER_ID));
    }

    public function testAddUserBusinessUnitShouldSetUserOrganizationIds()
    {
        $this->tree->addBusinessUnit(self::BU_ID, self::ORG_ID);
        $this->tree->addUser(self::USER_ID, null);

        $this->tree->addUserOrganization(self::USER_ID, self::ORG_ID);
        $this->assertEquals([self::ORG_ID], $this->tree->getUserOrganizationIds(self::USER_ID));
    }

    public function testAddUserBusinessUnitBelongToDifferentOrganizations()
    {
        $this->tree->addUser(self::USER_ID, null);

        $this->tree->addBusinessUnit(self::BU_ID + 1, null);
        $this->assertNull($this->tree->getBusinessUnitOrganizationId(self::BU_ID + 1));
        $this->tree->addBusinessUnit(self::BU_ID + 2, self::ORG_ID + 2);
        $this->assertEquals(self::ORG_ID + 2, $this->tree->getBusinessUnitOrganizationId(self::BU_ID + 2));
        $this->tree->addBusinessUnit(self::BU_ID + 3, self::ORG_ID + 3);
        $this->assertEquals(self::ORG_ID + 3, $this->tree->getBusinessUnitOrganizationId(self::BU_ID + 3));

        $this->tree->addUserBusinessUnit(self::USER_ID, null, null);
        $this->assertEquals([], $this->tree->getUserBusinessUnitIds(self::USER_ID));
        $this->assertNull($this->tree->getUserOrganizationId(self::USER_ID));
        $this->assertEquals([], $this->tree->getUserOrganizationIds(self::USER_ID));
        $this->assertEquals([], $this->tree->getUserSubordinateBusinessUnitIds(self::USER_ID, self::ORG_ID + 1));
        $this->assertEquals([], $this->tree->getBusinessUnitsIdByUserOrganizations(self::USER_ID));

        $this->tree->addUserBusinessUnit(self::USER_ID, self::ORG_ID + 1, self::BU_ID + 1);
        $this->assertEquals([self::BU_ID + 1], $this->tree->getUserBusinessUnitIds(self::USER_ID));
        $this->assertNull($this->tree->getUserOrganizationId(self::USER_ID));
        $this->assertEquals([], $this->tree->getUserOrganizationIds(self::USER_ID));
        $this->assertEquals(
            [self::BU_ID + 1],
            $this->tree->getUserSubordinateBusinessUnitIds(self::USER_ID, self::ORG_ID + 1)
        );
        $this->assertEquals([], $this->tree->getBusinessUnitsIdByUserOrganizations(self::USER_ID));

        $this->tree->addUserBusinessUnit(self::USER_ID, self::ORG_ID + 2, self::BU_ID + 2);
        $this->tree->addUserOrganization(self::USER_ID, self::ORG_ID + 2);
        $this->assertEquals([self::BU_ID + 1, self::BU_ID + 2], $this->tree->getUserBusinessUnitIds(self::USER_ID));
        $this->assertEquals([self::BU_ID + 2], $this->tree->getUserBusinessUnitIds(self::USER_ID, self::ORG_ID + 2));
        $this->assertNull($this->tree->getUserOrganizationId(self::USER_ID));
        $this->assertEquals([self::ORG_ID + 2], $this->tree->getUserOrganizationIds(self::USER_ID));
        $this->assertEquals(
            [self::BU_ID + 2],
            $this->tree->getUserSubordinateBusinessUnitIds(self::USER_ID, self::ORG_ID + 2)
        );
        $this->assertEquals(
            [self::BU_ID + 1, self::BU_ID + 2],
            $this->tree->getUserSubordinateBusinessUnitIds(self::USER_ID)
        );
        $this->assertEquals(
            [self::BU_ID + 2],
            $this->tree->getBusinessUnitsIdByUserOrganizations(self::USER_ID)
        );

        $this->tree->addUserBusinessUnit(self::USER_ID, self::ORG_ID + 3, self::BU_ID + 3);
        $this->tree->addUserOrganization(self::USER_ID, self::ORG_ID + 3);
        $this->assertEquals(
            [self::BU_ID + 1, self::BU_ID + 2, self::BU_ID + 3],
            $this->tree->getUserBusinessUnitIds(self::USER_ID)
        );
        $this->assertNull($this->tree->getUserOrganizationId(self::USER_ID));
        $this->assertEquals([self::ORG_ID + 2, self::ORG_ID + 3], $this->tree->getUserOrganizationIds(self::USER_ID));
        $this->assertEquals(
            [self::BU_ID + 1, self::BU_ID + 2, self::BU_ID + 3],
            $this->tree->getUserSubordinateBusinessUnitIds(self::USER_ID)
        );
        $this->assertEquals(
            [self::BU_ID + 3],
            $this->tree->getUserSubordinateBusinessUnitIds(self::USER_ID, self::ORG_ID + 3)
        );
        $this->assertEquals(
            [self::BU_ID + 2, self::BU_ID + 3],
            $this->tree->getBusinessUnitsIdByUserOrganizations(self::USER_ID)
        );
    }

    public function testAddBusinessUsersAndGetAllBusinessUnitIds()
    {
        $this->tree->addBusinessUnit(self::BU_ID + 1, 1);
        $this->tree->addBusinessUnit(self::BU_ID + 2, 2);
        $this->tree->addBusinessUnit(self::BU_ID + 3, 2);
        $this->tree->addBusinessUnit(self::BU_ID + 4, 3);

        $this->assertEquals(
            [self::BU_ID + 1, self::BU_ID + 2, self::BU_ID + 3, self::BU_ID + 4],
            $this->tree->getAllBusinessUnitIds()
        );
    }

    public static function setSubordinateBusinessUnitIdsProvider(): array
    {
        return [
            '1: [11, 12]' => [
                [
                    1  => [11,12],
                ],
                [
                    1 => [11, 12],
                    11 => [],
                    12 => [],
                ]

            ],
            '1: [11: [111], 12]' => [
                [
                    1  => [11, 12, 111],
                    11 => [111]

                ],
                [
                    1 => [11, 12, 111],
                    11 => [111],
                    111 => [],
                    12 => [],
                ]
            ],
            '1: [11: [111: [1111, 1112]], 12: [121, 122: [1221]]]' => [
                [
                    1   => [11, 12, 111, 121, 122, 1111, 1112, 1221],
                    11  => [111, 1111, 1112],
                    12  => [121, 122, 1221],
                    111 => [1111, 1112],
                    122 => [1221]
                ],
                [
                    1    => [11, 12, 111, 121, 122, 1111, 1112, 1221],
                    11   => [111, 1111, 1112],
                    12   => [121, 122, 1221],
                    111  => [1111, 1112],
                    122  => [1221],
                    1111 => [],
                    1112 => [],
                    121  => [],
                ]
            ],
            'unknown parent' => [
                [
                    1  => [11],
                    2  => [12]
                ],
                [
                    1  => [11],
                    11 => [],
                ]
            ],
            'child loaded before parent' => [
                [
                    1  => [11,12,111],
                    11 => [111]
                ],
                [

                   1   => [11, 12, 111],
                   11  => [111],
                   111 => [],
                   12  => []
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

    public function getUsersAssignedToBusinessUnitsProvider(): array
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

<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Owner;

use Oro\Bundle\SecurityBundle\Owner\OwnerTree;

class OwnerTreeInterfaceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param mixed $src
     * @param mixed $expected
     *
     * @dataProvider addDeepEntityProvider
     */
    public function testAddDeepEntity($src, $expected)
    {
        $tree = new OwnerTree();
        foreach ($src as $item) {
            $tree->addDeepEntity($item[0], $item[1]);
        }
        $tree->buildTree();

        foreach ($expected as $buId => $sBuIds) {
            $this->assertEquals(
                $sBuIds,
                $tree->getSubordinateBusinessUnitIds($buId),
                sprintf('Failed for %s', $buId)
            );
        }
    }

    public function testAddLocalEntityShouldSetOwningOrganizationIdEvenIfItIsNull()
    {
        $tree = new OwnerTree();

        $tree->addLocalEntity('bu1', null);
        $this->assertNull($tree->getBusinessUnitOrganizationId('bu1'));

        $tree->addLocalEntity('bu2', 'org');
        $this->assertEquals('org', $tree->getBusinessUnitOrganizationId('bu2'));
    }

    public function testAddLocalEntityShouldSetOrganizationBusinessUnitIdsOnlyIfOrganizationIsNotNull()
    {
        $tree = new OwnerTree();

        $tree->addLocalEntity('bu1', null);
        $this->assertEquals([], $tree->getOrganizationBusinessUnitIds('bu1'));

        $tree->addLocalEntity('bu2', 'org');
        $this->assertEquals(['bu2'], $tree->getOrganizationBusinessUnitIds('org'));

        $tree->addLocalEntity('bu3', 'org');
        $this->assertEquals(['bu2', 'bu3'], $tree->getOrganizationBusinessUnitIds('org'));
    }

    public function testAddLocalEntityShouldSetBusinessUnitUserIds()
    {
        $tree = new OwnerTree();

        $tree->addBasicEntity('user1', 'bu');
        $tree->addBasicEntity('user2', 'bu');

        $tree->addLocalEntity('bu', null);
        $this->assertEquals(['user1', 'user2'], $tree->getBusinessUnitUserIds('bu'));
    }

    public function testAddLocalEntityShouldSetUserOwningOrganizationId()
    {
        $tree = new OwnerTree();

        $tree->addBasicEntity('user', 'bu');

        $tree->addLocalEntity('bu', 'org');
        $this->assertEquals('org', $tree->getUserOrganizationId('user'));
    }

    public function testAddLocalEntityShouldNotSetUserOrganizationIds()
    {
        $tree = new OwnerTree();

        $tree->addBasicEntity('user', 'bu');

        $tree->addLocalEntity('bu', 'org');
        $this->assertEquals([], $tree->getUserOrganizationIds('user'));
    }

    public function testAddLocalEntityShouldNotSetUserOrganizationIdsIfOrganizationIdIsNull()
    {
        $tree = new OwnerTree();

        $tree->addBasicEntity('user', 'bu');

        $tree->addLocalEntity('bu', null);
        $this->assertEquals([], $tree->getUserOrganizationIds('user'));
    }

    public function testAddUserShouldSetUserOwningBusinessUnitId()
    {
        $tree = new OwnerTree();

        $tree->addBasicEntity('user', 'bu');
        $this->assertEquals('bu', $tree->getUserBusinessUnitId('user'));
    }

    public function testAddUserShouldSetUserOwningBusinessUnitIdEvenIfItIsNull()
    {
        $tree = new OwnerTree();

        $tree->addBasicEntity('user', null);
        $this->assertNull($tree->getUserBusinessUnitId('user'));
    }

    public function testAddUserShouldSetUserBusinessUnitIds()
    {
        $tree = new OwnerTree();

        $tree->addBasicEntity('user', null);
        $this->assertEquals([], $tree->getUserBusinessUnitIds('user'));
    }

    public function testAddUserShouldSetBusinessUnitUserIds()
    {
        $tree = new OwnerTree();

        $tree->addLocalEntity('bu', null);

        $tree->addBasicEntity('user', 'bu');
        $this->assertEquals(['user'], $tree->getBusinessUnitUserIds('bu'));

        $tree->addBasicEntity('user1', 'bu');
        $this->assertEquals(['user', 'user1'], $tree->getBusinessUnitUserIds('bu'));
    }

    public function testAddUserShouldNotSetUserOrganizationIds()
    {
        $tree = new OwnerTree();

        $tree->addLocalEntity('bu', 'org');

        $tree->addBasicEntity('user', 'bu');
        $this->assertEquals([], $tree->getUserOrganizationIds('user'));
    }

    public function testAddUserShouldNotSetUserOrganizationIdsIfOrganizationIdIsNull()
    {
        $tree = new OwnerTree();

        $tree->addLocalEntity('bu', null);

        $tree->addBasicEntity('user', 'bu');
        $this->assertEquals([], $tree->getUserOrganizationIds('user'));
    }

    public function testAddUserShouldSetUserOwningOrganizationId()
    {
        $tree = new OwnerTree();

        $tree->addLocalEntity('bu', 'org');

        $tree->addBasicEntity('user', 'bu');
        $this->assertEquals('org', $tree->getUserOrganizationId('user'));
    }

    public function testAddUserShouldSetUserOwningOrganizationIdEvenIfOrganizationIdIsNull()
    {
        $tree = new OwnerTree();

        $tree->addLocalEntity('bu', null);

        $tree->addBasicEntity('user', 'bu');
        $this->assertNull($tree->getUserOrganizationId('user'));
    }

    /**
     * @expectedException \LogicException
     */
    public function testAddUserBusinessUnitShouldThrowExceptionIfUserDoesNotSet()
    {
        $tree = new OwnerTree();

        $tree->addLocalEntityToBasic('user', 'org1', null);
    }

    public function testAddUserBusinessUnitShouldNotSetUserBusinessUnitIdsIfBusinessUnitIdIsNull()
    {
        $tree = new OwnerTree();

        $tree->addBasicEntity('user', null);

        $tree->addLocalEntityToBasic('user', null, 'org1');
        $this->assertEquals([], $tree->getUserBusinessUnitIds('user'));
    }

    public function testAddUserBusinessUnitShouldSetUserBusinessUnitIds()
    {
        $tree = new OwnerTree();

        $tree->addBasicEntity('user', null);

        $tree->addLocalEntityToBasic('user', 'bu', 'org1');
        $this->assertEquals(['bu'], $tree->getUserBusinessUnitIds('user'));

        $tree->addLocalEntityToBasic('user', 'bu1', 'org1');
        $this->assertEquals(['bu', 'bu1'], $tree->getUserBusinessUnitIds('user'));
        $this->assertEquals(['bu', 'bu1'], $tree->getUserBusinessUnitIds('user', 'org1'));
    }

    public function testAddUserBusinessUnitShouldNotSetUserOrganizationIdsIfOrganizationIdIsNull()
    {
        $tree = new OwnerTree();

        $tree->addLocalEntity('bu', null);
        $tree->addBasicEntity('user', null);

        $tree->addLocalEntityToBasic('user', 'bu', 'org1');
        $this->assertEquals([], $tree->getUserOrganizationIds('user'));
    }

    public function testAddUserBusinessUnitShouldSetUserOrganizationIds()
    {
        $tree = new OwnerTree();

        $tree->addLocalEntity('bu', 'org');
        $tree->addBasicEntity('user', null);

        $tree->addGlobalEntity('user', 'org');
        $this->assertEquals(['org'], $tree->getUserOrganizationIds('user'));
    }

    public function testAddUserBusinessUnitBelongToDifferentOrganizations()
    {
        $tree = new OwnerTree();

        $tree->addBasicEntity('user', null);

        $tree->addLocalEntity('bu1', null);
        $this->assertNull($tree->getBusinessUnitOrganizationId('bu1'));
        $tree->addLocalEntity('bu2', 'org2');
        $this->assertEquals('org2', $tree->getBusinessUnitOrganizationId('bu2'));
        $tree->addLocalEntity('bu3', 'org3');
        $this->assertEquals('org3', $tree->getBusinessUnitOrganizationId('bu3'));

        $tree->addLocalEntityToBasic('user', null, null);
        $this->assertEquals([], $tree->getUserBusinessUnitIds('user'));
        $this->assertNull($tree->getUserOrganizationId('user'));
        $this->assertEquals([], $tree->getUserOrganizationIds('user'));
        $this->assertEquals([], $tree->getUserSubordinateBusinessUnitIds('user', 'org1'));
        $this->assertEquals([], $tree->getBusinessUnitsIdByUserOrganizations('user'));

        $tree->addLocalEntityToBasic('user', 'bu1', 'org1');
        $this->assertEquals(['bu1'], $tree->getUserBusinessUnitIds('user'));
        $this->assertNull($tree->getUserOrganizationId('user'));
        $this->assertEquals([], $tree->getUserOrganizationIds('user'));
        $this->assertEquals(['bu1'], $tree->getUserSubordinateBusinessUnitIds('user', 'org1'));
        $this->assertEquals([], $tree->getBusinessUnitsIdByUserOrganizations('user'));

        $tree->addLocalEntityToBasic('user', 'bu2', 'org2');
        $tree->addGlobalEntity('user', 'org2');
        $this->assertEquals(['bu1', 'bu2'], $tree->getUserBusinessUnitIds('user'));
        $this->assertEquals(['bu2'], $tree->getUserBusinessUnitIds('user', 'org2'));
        $this->assertNull($tree->getUserOrganizationId('user'));
        $this->assertEquals(['org2'], $tree->getUserOrganizationIds('user'));
        $this->assertEquals(['bu2'], $tree->getUserSubordinateBusinessUnitIds('user', 'org2'));
        $this->assertEquals(['bu1', 'bu2'], $tree->getUserSubordinateBusinessUnitIds('user'));
        $this->assertEquals(['bu2'], $tree->getBusinessUnitsIdByUserOrganizations('user'));

        $tree->addLocalEntityToBasic('user', 'bu3', 'org3');
        $tree->addGlobalEntity('user', 'org3');
        $this->assertEquals(['bu1', 'bu2', 'bu3'], $tree->getUserBusinessUnitIds('user'));
        $this->assertNull($tree->getUserOrganizationId('user'));
        $this->assertEquals(['org2', 'org3'], $tree->getUserOrganizationIds('user'));
        $this->assertEquals(['bu1', 'bu2', 'bu3'], $tree->getUserSubordinateBusinessUnitIds('user'));
        $this->assertEquals(['bu3'], $tree->getUserSubordinateBusinessUnitIds('user', 'org3'));
        $this->assertEquals(['bu2', 'bu3'], $tree->getBusinessUnitsIdByUserOrganizations('user'));
    }

    /**
     * @return array
     */
    public static function addDeepEntityProvider()
    {
        return [
            '1: [null]' => [
                [
                    ['1', null],
                ],
                [
                    '1' => [],
                ],
            ],
            '1: [11, 12]' => [
                [
                    ['1', null],
                    ['11', '1'],
                    ['12', '1'],
                ],
                [
                    '1' => ['11', '12'],
                    '11' => [],
                    '12' => [],
                ],
            ],
            '1: [11, 12] reverse' => [
                [
                    ['1', null],
                    ['12', '1'],
                    ['11', '1'],
                ],
                [
                    '1' => ['12', '11'],
                    '11' => [],
                    '12' => [],
                ],
            ],
            '1: [11: [111], 12]' => [
                [
                    ['1', null],
                    ['11', '1'],
                    ['12', '1'],
                    ['111', '11'],
                ],
                [
                    '1' => ['11', '111', '12'],
                    '11' => ['111'],
                    '111' => [],
                    '12' => [],
                ],
            ],
            '1: [11: [111: [1111, 1112]], 12: [121, 122: [1221]]]' => [
                [
                    ['1', null],
                    ['11', '1'],
                    ['111', '11'],
                    ['1111', '111'],
                    ['1112', '111'],
                    ['12', '1'],
                    ['121', '12'],
                    ['122', '12'],
                    ['1221', '122'],
                ],
                [
                    '1' => ['11', '111', '1111', '1112', '12', '121', '122', '1221'],
                    '11' => ['111', '1111', '1112'],
                    '111' => ['1111', '1112'],
                    '1111' => [],
                    '1112' => [],
                    '12' => ['121', '122', '1221'],
                    '121' => [],
                    '122' => ['1221'],
                ],
            ],
        ];
    }
}

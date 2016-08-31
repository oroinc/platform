<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Permission;

use Oro\Bundle\SecurityBundle\Acl\Permission\PermissionMap;
use Oro\Bundle\SecurityBundle\Acl\Extension\ActionMaskBuilder;
use Oro\Bundle\SecurityBundle\Tests\Unit\TestHelper;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\TestEntity;

class PermissionMapTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PermissionMap
     */
    private $map;

    protected function setUp()
    {
        $this->map = new PermissionMap(
            TestHelper::get($this)->createAclExtensionSelector()
        );
    }

    public function testGetMasksReturnsNullWhenNotSupportedMask()
    {
        $this->assertNull($this->map->getMasks('IS_AUTHENTICATED_REMEMBERED', null));
    }

    /**
     * @dataProvider getMasksProvider
     */
    public function testGetMasks($object, $name, $mask)
    {
        $this->assertEquals($mask, $this->map->getMasks($name, $object));
    }

    /**
     * @dataProvider containsProvider
     */
    public function testContains($name, $expectedResult)
    {
        $this->assertEquals($expectedResult, $this->map->contains($name));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public static function getMasksProvider()
    {
        return array(
            array(new TestEntity(), 'VIEW', array(
                1 << 0,
                1 << 1,
                1 << 2,
                1 << 3,
                1 << 4
            )),
            array(new TestEntity(), 'CREATE', array(
                1 << 5,
                1 << 6,
                1 << 7,
                1 << 8,
                1 << 9
            )),
            array(new TestEntity(), 'EDIT', array(
                1 << 10,
                1 << 11,
                1 << 12,
                1 << 13,
                1 << 14
            )),
            array(new TestEntity(), 'DELETE', array(
                32768 + (1 << 0),
                32768 + (1 << 1),
                32768 + (1 << 2),
                32768 + (1 << 3),
                32768 + (1 << 4)
            )),
            array(new TestEntity(), 'ASSIGN', array(
                32768 + (1 << 5),
                32768 + (1 << 6),
                32768 + (1 << 7),
                32768 + (1 << 8),
                32768 + (1 << 9)
            )),
            array(new TestEntity(), 'PERMIT', array(
                32768 + (1 << 10),
                32768 + (1 << 11),
                32768 + (1 << 12),
                32768 + (1 << 13),
                32768 + (1 << 14)
            )),
            array('action: test', 'EXECUTE', array(
                ActionMaskBuilder::MASK_EXECUTE,
            )),
        );
    }

    /**
     * @return array
     */
    public static function containsProvider()
    {
        return array(
            array('VIEW', true),
            array('EDIT', true),
            array('CREATE', true),
            array('DELETE', true),
            array('ASSIGN', true),
            array('PERMIT', true),
            array('EXECUTE', true),
            array('OTHER', false),
        );
    }
}

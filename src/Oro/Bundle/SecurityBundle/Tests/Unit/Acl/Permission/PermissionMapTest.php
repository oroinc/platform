<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Permission;

use Oro\Bundle\SecurityBundle\Acl\Extension\ActionMaskBuilder;
use Oro\Bundle\SecurityBundle\Acl\Permission\PermissionMap;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\TestEntity;
use Oro\Bundle\SecurityBundle\Tests\Unit\TestHelper;

class PermissionMapTest extends \PHPUnit\Framework\TestCase
{
    /** @var PermissionMap */
    private $map;

    protected function setUp(): void
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
    public function testGetMasks(object|string $object, string $name, array $mask)
    {
        $this->assertEquals($mask, $this->map->getMasks($name, $object));
    }

    /**
     * @dataProvider containsProvider
     */
    public function testContains(string $name, bool $expectedResult)
    {
        $this->assertEquals($expectedResult, $this->map->contains($name));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public static function getMasksProvider(): array
    {
        return [
            'VIEW' => [
                new TestEntity(),
                'VIEW',
                [
                    1 << 0,
                    1 << 1,
                    1 << 2,
                    1 << 3,
                    1 << 4
                ]
            ],
            'CREATE' => [
                new TestEntity(),
                'CREATE',
                [
                    1 << 5,
                    1 << 6,
                    1 << 7,
                    1 << 8,
                    1 << 9
                ]
            ],
            'EDIT' => [
                new TestEntity(),
                'EDIT',
                [
                    1 << 10,
                    1 << 11,
                    1 << 12,
                    1 << 13,
                    1 << 14
                ]
            ],
            'DELETE' => [
                new TestEntity(),
                'DELETE',
                [
                    1 << 15,
                    1 << 16,
                    1 << 17,
                    1 << 18,
                    1 << 19
                ]
            ],
            'ASSIGN' => [
                new TestEntity(),
                'ASSIGN',
                [
                    1 << 20,
                    1 << 21,
                    1 << 22,
                    1 << 23,
                    1 << 24
                ]
            ],
            'PERMIT' => [
                new TestEntity(),
                'PERMIT',
                [
                    33554432 + (1 << 0),
                    33554432 + (1 << 1),
                    33554432 + (1 << 2),
                    33554432 + (1 << 3),
                    33554432 + (1 << 4)
                ]
            ],
            'EXECUTE' => [
                'action: test',
                'EXECUTE',
                [
                    ActionMaskBuilder::MASK_EXECUTE,
                ]
            ],
        ];
    }

    public static function containsProvider(): array
    {
        return [
            ['VIEW', true],
            ['EDIT', true],
            ['CREATE', true],
            ['DELETE', true],
            ['ASSIGN', true],
            ['PERMIT', true],
            ['EXECUTE', true],
            ['OTHER', false],
        ];
    }
}

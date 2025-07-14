<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Helper;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\Helper\RelationHelper;
use Oro\Bundle\EntityBundle\Provider\VirtualRelationProviderInterface;
use Oro\Bundle\EntityBundle\Tests\Unit\Helper\Stub\Entity1;
use Oro\Bundle\EntityBundle\Tests\Unit\Helper\Stub\Entity2;
use Oro\Bundle\EntityBundle\Tests\Unit\Helper\Stub\Entity3;
use Oro\Bundle\EntityBundle\Tests\Unit\Helper\Stub\Entity4;
use Oro\Bundle\EntityBundle\Tests\Unit\Helper\Stub\Entity5;
use Oro\Bundle\EntityBundle\Tests\Unit\Helper\Stub\Entity6;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RelationHelperTest extends TestCase
{
    private VirtualRelationProviderInterface&MockObject $relationProvider;
    private RelationHelper $helper;

    #[\Override]
    protected function setUp(): void
    {
        $this->relationProvider = $this->createMock(VirtualRelationProviderInterface::class);

        $this->helper = new RelationHelper($this->relationProvider);
    }

    public function testHasVirtualRelations(): void
    {
        $this->relationProvider->expects($this->any())
            ->method('getVirtualRelations')
            ->willReturnMap([
                [Entity1::class, $this->getRelations()],
                [Entity2::class, []],
            ]);

        $this->assertTrue($this->helper->hasVirtualRelations(Entity1::class));
        $this->assertFalse($this->helper->hasVirtualRelations(Entity2::class));
    }

    /**
     * @dataProvider getMetadataTypeForVirtualJoin
     */
    public function testGetMetadataTypeForVirtualJoin(string $targetEntityClass, int $expectedResult): void
    {
        $this->relationProvider->expects($this->once())
            ->method('getVirtualRelations')
            ->with(Entity1::class)
            ->willReturn($this->getRelations());

        $this->assertEquals(
            $expectedResult,
            $this->helper->getMetadataTypeForVirtualJoin(Entity1::class, $targetEntityClass)
        );
    }

    public function getMetadataTypeForVirtualJoin(): array
    {
        return [
            'unknown target class' => [
                'target' => Entity1::class,
                'result' => 0,
            ],
            'unknown type' => [
                'target' => Entity2::class,
                'result' => 0,
            ],
            'one-to-one' => [
                'target' => Entity3::class,
                'result' => ClassMetadata::ONE_TO_ONE,
            ],
            'many-to-one' => [
                'target' => Entity4::class,
                'result' => ClassMetadata::MANY_TO_ONE,
            ],
            'one-to-many' => [
                'target' => Entity5::class,
                'result' => ClassMetadata::ONE_TO_MANY,
            ],
            'many-to-many' => [
                'target' => Entity6::class,
                'result' => ClassMetadata::MANY_TO_MANY,
            ],
        ];
    }

    private function getRelations(): array
    {
        return [
            'r1' => [],
            'r2' => [
                'relation_type' => 'unknown',
                'query' => [
                    'join' => [
                        'left' => [
                            ['join' => Entity2::class],
                        ],
                    ],
                ],
            ],
            'r3' => [
                'relation_type' => 'OneToOne',
                'query' => [
                    'join' => [
                        'left' => [
                            ['join' => Entity3::class],
                        ],
                    ],
                ],
            ],
            'r4' => [
                'relation_type' => 'ManyToOne',
                'query' => [
                    'join' => [
                        'left' => [
                            ['join' => Entity4::class],
                        ],
                    ],
                ],
            ],
            'r5' => [
                'relation_type' => 'OneToMany',
                'query' => [
                    'join' => [
                        'left' => [
                            ['join' => Entity5::class],
                        ],
                    ],
                ],
            ],
            'r6' => [
                'relation_type' => 'ManyToMany',
                'query' => [
                    'join' => [
                        'left' => [
                            ['join' => Entity6::class],
                        ],
                    ],
                ],
            ],
        ];
    }
}

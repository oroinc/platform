<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Helper;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\Helper\RelationHelper;
use Oro\Bundle\EntityBundle\Provider\VirtualRelationProviderInterface;
use Oro\Bundle\EntityBundle\Tests\Unit\Helper\Stub;

class RelationHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var VirtualRelationProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $relationProvider;

    /** @var RelationHelper */
    protected $helper;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->relationProvider = $this->createMock(VirtualRelationProviderInterface::class);

        $this->helper = new RelationHelper($this->relationProvider);
    }

    public function testHasVirtualRelations()
    {
        $this->relationProvider->expects($this->any())
            ->method('getVirtualRelations')
            ->will($this->returnValueMap([
                [Stub\Entity1::class, $this->getRelations()],
                [Stub\Entity2::class, []],
            ]));

        $this->assertTrue($this->helper->hasVirtualRelations(Stub\Entity1::class));
        $this->assertFalse($this->helper->hasVirtualRelations(Stub\Entity2::class));
    }

    /**
     * @param string $targetEntityClass
     * @param int $expectedResult
     *
     * @dataProvider getMetadataTypeForVirtualJoin
     */
    public function testGetMetadataTypeForVirtualJoin(string $targetEntityClass, int $expectedResult)
    {
        $this->relationProvider->expects($this->once())
            ->method('getVirtualRelations')
            ->with(Stub\Entity1::class)
            ->willReturn($this->getRelations());

        $this->assertEquals(
            $expectedResult,
            $this->helper->getMetadataTypeForVirtualJoin(Stub\Entity1::class, $targetEntityClass)
        );
    }

    /**
     * @return array
     */
    public function getMetadataTypeForVirtualJoin(): array
    {
        return [
            'unknown target class' => [
                'target' => Stub\Entity1::class,
                'result' => 0,
            ],
            'unknown type' => [
                'target' => Stub\Entity2::class,
                'result' => 0,
            ],
            'one-to-one' => [
                'target' => Stub\Entity3::class,
                'result' => ClassMetadata::ONE_TO_ONE,
            ],
            'many-to-one' => [
                'target' => Stub\Entity4::class,
                'result' => ClassMetadata::MANY_TO_ONE,
            ],
            'one-to-many' => [
                'target' => Stub\Entity5::class,
                'result' => ClassMetadata::ONE_TO_MANY,
            ],
            'many-to-many' => [
                'target' => Stub\Entity6::class,
                'result' => ClassMetadata::MANY_TO_MANY,
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getRelations(): array
    {
        return [
            'r1' => [],
            'r2' => [
                'relation_type' => 'unknown',
                'query' => [
                    'join' => [
                        'left' => [
                            ['join' => Stub\Entity2::class],
                        ],
                    ],
                ],
            ],
            'r3' => [
                'relation_type' => 'OneToOne',
                'query' => [
                    'join' => [
                        'left' => [
                            ['join' => Stub\Entity3::class],
                        ],
                    ],
                ],
            ],
            'r4' => [
                'relation_type' => 'ManyToOne',
                'query' => [
                    'join' => [
                        'left' => [
                            ['join' => Stub\Entity4::class],
                        ],
                    ],
                ],
            ],
            'r5' => [
                'relation_type' => 'OneToMany',
                'query' => [
                    'join' => [
                        'left' => [
                            ['join' => Stub\Entity5::class],
                        ],
                    ],
                ],
            ],
            'r6' => [
                'relation_type' => 'ManyToMany',
                'query' => [
                    'join' => [
                        'left' => [
                            ['join' => Stub\Entity6::class],
                        ],
                    ],
                ],
            ],
        ];
    }
}

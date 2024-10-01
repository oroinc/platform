<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityBundle\Provider\ChainDictionaryValueListProvider;
use Oro\Bundle\EntityBundle\Provider\DictionaryValueListProviderInterface;

class ChainDictionaryValueListProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var DictionaryValueListProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $provider1;

    /** @var DictionaryValueListProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $provider2;

    /** @var ChainDictionaryValueListProvider */
    private $chainProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->provider1 = $this->createMock(DictionaryValueListProviderInterface::class);
        $this->provider2 = $this->createMock(DictionaryValueListProviderInterface::class);

        $this->chainProvider = new ChainDictionaryValueListProvider([$this->provider1, $this->provider2]);
    }

    public function testIsSupportedEntityClassWhenThereIsProviderThatSupportsEntityClass(): void
    {
        $class = 'Test\Class';
        $this->provider1->expects(self::once())
            ->method('supports')
            ->with($class)
            ->willReturn(false);
        $this->provider2->expects(self::once())
            ->method('supports')
            ->with($class)
            ->willReturn(true);

        self::assertTrue($this->chainProvider->isSupportedEntityClass($class));
    }

    public function testIsSupportedEntityClassWhenThereIsNoProviderThatSupportsEntityClass(): void
    {
        $class = 'Test\Class';
        $this->provider1->expects(self::once())
            ->method('supports')
            ->with($class)
            ->willReturn(false);
        $this->provider2->expects(self::once())
            ->method('supports')
            ->with($class)
            ->willReturn(false);

        self::assertFalse($this->chainProvider->isSupportedEntityClass($class));
    }

    public function testIsSupportedEntityClassWithoutChildProviders(): void
    {
        $chainProvider = new ChainDictionaryValueListProvider([]);
        self::assertFalse($chainProvider->isSupportedEntityClass('Test\Class'));
    }

    public function testGetSerializationConfig(): void
    {
        $class = 'Test\Class';
        $expected = ['fields' => 'field'];

        $this->provider1->expects(self::once())
            ->method('supports')
            ->with($class)
            ->willReturn(true);
        $this->provider1->expects(self::once())
            ->method('getSerializationConfig')
            ->with($class)
            ->willReturn($expected);
        $this->provider2->expects(self::never())
            ->method('supports');

        self::assertEquals($expected, $this->chainProvider->getSerializationConfig($class));
    }

    public function testGetSerializationConfigWithoutChildProviders(): void
    {
        $chainProvider = new ChainDictionaryValueListProvider([]);
        self::assertNull($chainProvider->getSerializationConfig('Test\Class'));
    }

    public function testGetValueListQueryBuilder(): void
    {
        $class = 'Test\Class';
        $qb = $this->createMock(QueryBuilder::class);

        $this->provider1->expects(self::once())
            ->method('supports')
            ->with($class)
            ->willReturn(false);
        $this->provider2->expects(self::once())
            ->method('supports')
            ->with($class)
            ->willReturn(true);
        $this->provider2->expects(self::once())
            ->method('getValueListQueryBuilder')
            ->with($class)
            ->willReturn($qb);

        self::assertEquals($qb, $this->chainProvider->getValueListQueryBuilder($class));
    }

    public function testGetValueListQueryBuilderWithoutChildProviders(): void
    {
        $chainProvider = new ChainDictionaryValueListProvider([]);
        self::assertNull($chainProvider->getValueListQueryBuilder('Test\Class'));
    }

    /**
     * @dataProvider entityProvider
     */
    public function testGetSupportedEntityClasses(array $supported1, array $supported2, array $expected): void
    {
        $this->provider1->expects(self::once())
            ->method('getSupportedEntityClasses')
            ->willReturn($supported1);
        $this->provider2->expects(self::once())
            ->method('getSupportedEntityClasses')
            ->willReturn($supported2);

        self::assertEquals($expected, $this->chainProvider->getSupportedEntityClasses());
    }

    public function entityProvider(): array
    {
        return [
            [
                [],
                [],
                [],
            ],
            [
                ['Test\Status',],
                ['Test\Priority'],
                ['Test\Status', 'Test\Priority'],
            ],
            [
                ['Test\Status', 'Test\Priority'],
                ['Test\Source', 'Test\Status'],
                ['Test\Status', 'Test\Priority', 'Test\Source'],
            ],
        ];
    }

    public function testGetSupportedEntityClassesWithoutChildProviders(): void
    {
        $chainProvider = new ChainDictionaryValueListProvider([]);
        self::assertSame([], $chainProvider->getSupportedEntityClasses());
    }
}

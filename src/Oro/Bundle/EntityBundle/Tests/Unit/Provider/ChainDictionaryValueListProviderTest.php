<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityBundle\Provider\ChainDictionaryValueListProvider;
use Oro\Bundle\EntityBundle\Provider\DictionaryValueListProviderInterface;

class ChainDictionaryValueListProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var  ChainDictionaryValueListProvider */
    protected $chainProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $provider1;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $provider2;

    protected function setUp()
    {
        $this->provider1 = $this->createMock(DictionaryValueListProviderInterface::class);
        $this->provider2 = $this->createMock(DictionaryValueListProviderInterface::class);

        $this->chainProvider = new ChainDictionaryValueListProvider([$this->provider1, $this->provider2]);
    }

    public function testGetSerializationConfigForNullClassName()
    {
        $this->provider1->expects($this->never())
            ->method('supports');
        $this->provider2->expects($this->never())
            ->method('supports');

        $this->assertNull($this->chainProvider->getSerializationConfig(null));
    }

    public function testGetSerializationConfig()
    {
        $class = 'Test\Class';
        $expected = ['fields' => 'field'];

        $this->provider1->expects($this->once())
            ->method('supports')
            ->with($class)
            ->willReturn(true);
        $this->provider1->expects($this->once())
            ->method('getSerializationConfig')
            ->with($class)
            ->willReturn($expected);
        $this->provider2->expects($this->never())
            ->method('supports');

        $this->assertEquals($expected, $this->chainProvider->getSerializationConfig($class));
    }

    public function testGetSerializationConfigWithoutChildProviders()
    {
        $chainProvider = new ChainDictionaryValueListProvider([]);
        $this->assertNull($chainProvider->getSerializationConfig('Test\Class'));
    }

    public function testGetValueListQueryBuilderForNullClassName()
    {
        $this->provider1->expects($this->never())
            ->method('supports');
        $this->provider2->expects($this->never())
            ->method('supports');

        $this->assertNull($this->chainProvider->getValueListQueryBuilder(null));
    }

    public function testGetValueListQueryBuilder()
    {
        $class = 'Test\Class';
        $qb = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor();

        $this->provider1->expects($this->once())
            ->method('supports')
            ->with($class)
            ->willReturn(false);
        $this->provider2->expects($this->once())
            ->method('supports')
            ->with($class)
            ->willReturn(true);
        $this->provider2->expects($this->once())
            ->method('getValueListQueryBuilder')
            ->with($class)
            ->willReturn($qb);

        $this->assertEquals($qb, $this->chainProvider->getValueListQueryBuilder($class));
    }

    public function testGetValueListQueryBuilderWithoutChildProviders()
    {
        $chainProvider = new ChainDictionaryValueListProvider([]);
        $this->assertNull($chainProvider->getValueListQueryBuilder('Test\Class'));
    }

    /**
     * @dataProvider entityProvider
     */
    public function testGetSupportedEntityClasses($supported1, $supported2, $expected)
    {
        $this->provider1->expects($this->once())
            ->method('getSupportedEntityClasses')
            ->willReturn($supported1);
        $this->provider2->expects($this->once())
            ->method('getSupportedEntityClasses')
            ->willReturn($supported2);

        $this->assertEquals($expected, $this->chainProvider->getSupportedEntityClasses());
    }

    public function entityProvider()
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

    public function testGetSupportedEntityClassesWithoutChildProviders()
    {
        $chainProvider = new ChainDictionaryValueListProvider([]);
        $this->assertSame([], $chainProvider->getSupportedEntityClasses());
    }
}

<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Provider\ChainDictionaryValueListProvider;

class ChainDictionaryValueListProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var  ChainDictionaryValueListProvider */
    protected $chainProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $provider1;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $provider2;

    protected function setUp()
    {
        $this->provider1 = $this->getMock('Oro\Bundle\EntityBundle\Provider\DictionaryValueListProviderInterface');
        $this->provider2 = $this->getMock('Oro\Bundle\EntityBundle\Provider\DictionaryValueListProviderInterface');

        $this->chainProvider = new ChainDictionaryValueListProvider();
        $this->chainProvider->addProvider($this->provider1);
        $this->chainProvider->addProvider($this->provider2, -100);
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
        $class    = 'Test\Class';
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
        $qb    = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')->disableOriginalConstructor();

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
}

<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Oro\Bundle\ApiBundle\Filter\ChainFilterFactory;

class ChainFilterFactoryTest extends \PHPUnit_Framework_TestCase
{

    public function testChainFactory()
    {
        $chainFactory = new ChainFilterFactory();
        $childFactory1 = $this->getMock('Oro\Bundle\ApiBundle\Filter\FilterFactoryInterface');
        $childFactory2 = $this->getMock('Oro\Bundle\ApiBundle\Filter\FilterFactoryInterface');
        $chainFactory->addFilterFactory($childFactory1);
        $chainFactory->addFilterFactory($childFactory2);

        $knownFilter1 = $this->getMock('Oro\Bundle\ApiBundle\Filter\FilterInterface');
        $knownFilter2 = $this->getMock('Oro\Bundle\ApiBundle\Filter\FilterInterface');
        $knownFilter31 = $this->getMock('Oro\Bundle\ApiBundle\Filter\FilterInterface');
        $knownFilter32 = $this->getMock('Oro\Bundle\ApiBundle\Filter\FilterInterface');

        $childFactory1->expects($this->any())
            ->method('createFilter')
            ->willReturnMap(
                [
                    ['known1', $knownFilter1],
                    ['known3', $knownFilter31],
                    ['unknown1', null],
                ]
            );
        $childFactory2->expects($this->any())
            ->method('createFilter')
            ->willReturnMap(
                [
                    ['known2', $knownFilter2],
                    ['known3', $knownFilter32],
                    ['unknown2', null],
                ]
            );

        $this->assertSame($knownFilter1, $chainFactory->createFilter('known1'));
        $this->assertSame($knownFilter2, $chainFactory->createFilter('known2'));
        $this->assertSame($knownFilter31, $chainFactory->createFilter('known3'));
        $this->assertNull($chainFactory->createFilter('unknown1'));
        $this->assertNull($chainFactory->createFilter('unknown2'));
    }
}

<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Assetic;

use Assetic\Factory\Resource\ResourceInterface;

use Oro\Bundle\LayoutBundle\Assetic\LayoutFormulaLoader;

class LayoutFormulaLoaderTest extends \PHPUnit_Framework_TestCase
{
    public function testLoad()
    {
        $formulae = [1, 2, 3];
        $loader = new LayoutFormulaLoader();

        /** @var ResourceInterface|\PHPUnit_Framework_MockObject_MockObject $layoutResource */
        $layoutResource = $this->getMockBuilder('Oro\Bundle\LayoutBundle\Assetic\LayoutResource')
            ->disableOriginalConstructor()
            ->getMock();
        $layoutResource->expects($this->once())
            ->method('getContent')
            ->willReturn($formulae);

        $this->assertEquals($formulae, $loader->load($layoutResource));
    }
}

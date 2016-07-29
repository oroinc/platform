<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\AbstractServerRenderDataProvider;
use Oro\Component\Layout\LayoutContext;

class AbstractServerRenderDataProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Not implemented
     */
    public function testGetIdentifier()
    {
        /** @var AbstractServerRenderDataProvider $dataProvider */
        $dataProvider = $this->getMockForAbstractClass('Oro\Component\Layout\AbstractServerRenderDataProvider');
        $dataProvider->getIdentifier();
    }

    public function testGetData()
    {
        /** @var AbstractServerRenderDataProvider $dataProvider */
        $dataProvider = $this->getMockForAbstractClass('Oro\Component\Layout\AbstractServerRenderDataProvider');
        $this->assertEquals($dataProvider, $dataProvider->getData(new LayoutContext()));
    }
}

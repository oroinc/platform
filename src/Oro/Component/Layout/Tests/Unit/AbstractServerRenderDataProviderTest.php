<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\AbstractServerRenderDataProvider;

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
}

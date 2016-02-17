<?php

namespace Oro\Component\Layout\Tests\Unit;

class AbstractDataProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Not implemented
     */
    public function testGetIdentifier()
    {
        /** @var AbstractDataProvider $dataProvider */
        $dataProvider = $this->getMockForAbstractClass('Oro\Component\Layout\AbstractDataProvider');
        $dataProvider->getIdentifier();
    }
}

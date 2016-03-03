<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Cache;

use Oro\Bundle\ActionBundle\Configuration\ActionConfigurationProvider;

abstract class AbstractCacheServiceTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|ActionConfigurationProvider */
    protected $provider;

    protected function setUp()
    {
        $this->provider = $this->getMockBuilder('Oro\Bundle\ActionBundle\Configuration\ActionConfigurationProvider')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function tearDown()
    {
        unset($this->provider);
    }
}

<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Cache;

use Oro\Bundle\ActionBundle\Cache\CacheClearer;
use Oro\Bundle\ActionBundle\Configuration\ConfigurationProviderInterface;

class CacheClearerTest extends \PHPUnit_Framework_TestCase
{
    /** @var CacheClearer */
    protected $clearer;

    protected function setUp()
    {
        $this->clearer = new CacheClearer();
    }

    protected function tearDown()
    {
        unset($this->clearer);
    }

    public function testClear()
    {
        $this->clearer->addProvider($this->getProviderMock());
        $this->clearer->addProvider($this->getProviderMock());

        $this->clearer->clear(null);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ConfigurationProviderInterface
     */
    protected function getProviderMock()
    {
        $provider = $this->getMock('Oro\Bundle\ActionBundle\Configuration\ConfigurationProviderInterface');
        $provider->expects($this->once())->method('clearCache');

        return $provider;
    }
}

<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Provider;

use Oro\Bundle\IntegrationBundle\Provider\SimpleChannelType;

class SimpleChannelTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var SimpleChannelType */
    protected $channelType;

    public function setUp()
    {
        $this->channelType = new SimpleChannelType();
    }

    public function tearDown()
    {
        unset($this->channelType);
    }

    /**
     * Test
     */
    public function testTransport()
    {
        $this->assertInstanceOf('Oro\Bundle\IntegrationBundle\Provider\ChannelTypeInterface', $this->channelType);
        $this->assertNotEmpty($this->channelType->getLabel());
    }
}

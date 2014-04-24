<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Manager;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Manager\ChannelDeleteManager;

use Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture\TestChannelDeleteProvider;

class ChannelDeleteManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ChannelDeleteManager
     */
    protected $deleteManager;

    /**
     * @var Channel
     */
    protected $testChannel;

    public function setUp()
    {
        $this->deleteManager = new ChannelDeleteManager();
        $this->deleteManager->addProvider(new TestChannelDeleteProvider());
        $this->testChannel = new Channel();
    }

    public function testDeleteSupportedChannel()
    {
        $this->testChannel->setType('test');
        $this->assertTrue($this->deleteManager->deleteChannel($this->testChannel));
    }

    public function testDeleteUnSupportedChannel()
    {
        $this->testChannel->setType('unsupportedType');
        $this->assertFalse($this->deleteManager->deleteChannel($this->testChannel));
    }
}

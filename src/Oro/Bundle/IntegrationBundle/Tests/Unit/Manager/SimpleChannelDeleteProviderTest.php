<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Manager;

use Oro\Bundle\IntegrationBundle\Manager\SimpleChannelDeleteProvider;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

class SimpleChannelDeleteProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SimpleChannelDeleteProvider
     */
    protected $simpleDeleteProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $em;

    /**
     * @var Channel
     */
    protected $channel;

    public function setUp()
    {
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->simpleDeleteProvider = new SimpleChannelDeleteProvider($this->em);
        $this->channel = new Channel();
    }

    public function testGetSupportedChannelType()
    {
        $this->assertEquals('simple', $this->simpleDeleteProvider->getSupportedChannelType());
    }

    public function testCompleteProcessDelete()
    {
        $this->em->expects($this->once())
            ->method('remove')
            ->with($this->equalTo($this->channel));
        $this->em->expects($this->once())
            ->method('flush');
        $this->assertTrue($this->simpleDeleteProvider->processDelete($this->channel));
    }

    public function testWrongProcessDelete()
    {
        $this->em->expects($this->once())
            ->method('remove')
            ->will($this->throwException(new \Exception()));
        $this->assertFalse($this->simpleDeleteProvider->processDelete($this->channel));
    }
}

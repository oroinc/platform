<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Manager;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Manager\DeleteManager;

use Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture\TestChannelDeleteProvider;

class ChannelDeleteManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DeleteManager
     */
    protected $deleteManager;

    /**
     * @var Channel
     */
    protected $testChannel;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $em;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $connection;

    protected function setUp()
    {
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em->expects($this->any())
            ->method('getConnection')
            ->will($this->returnValue($this->connection));
        $this->connection->expects($this->any())
            ->method('beginTransaction');
        $this->deleteManager = new DeleteManager($this->em);
        $this->deleteManager->addProvider(new TestChannelDeleteProvider());
        $this->testChannel = new Channel();
        $this->testChannel->setType('test');
    }

    public function testDeleteChannelWithoutErrors()
    {
        $this->connection->expects($this->once())
            ->method('commit');
        $this->em->expects($this->any())
            ->method('remove')
            ->with($this->equalTo($this->testChannel));
        $this->em->expects($this->any())
            ->method('flush');

        $this->assertTrue($this->deleteManager->delete($this->testChannel));
    }

    public function testDeleteChannelWithErrors()
    {
        $this->em->expects($this->any())
            ->method('remove')
            ->with($this->equalTo($this->testChannel))
            ->will($this->throwException(new \Exception()));
        $this->connection->expects($this->once())
            ->method('rollback');
        $this->assertFalse($this->deleteManager->delete($this->testChannel));
    }
}

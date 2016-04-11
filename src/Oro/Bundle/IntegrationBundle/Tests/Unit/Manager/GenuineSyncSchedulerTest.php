<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\IntegrationBundle\Command\SyncCommand;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Manager\GenuineSyncScheduler;

class GenuineSyncSchedulerTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRegistryAsFirstArgument()
    {
        new GenuineSyncScheduler($this->createRegistryStub());
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage The integration is not active.
     */
    public function testThrowIfChannelNotActive()
    {
        $channel = new Channel();
        $channel->setEnabled(false);

        $scheduler = new GenuineSyncScheduler($this->createRegistryStub());

        $scheduler->schedule($channel);
    }

    public function testShouldCreateJobAndStoreToDatabase()
    {
        $channel = new Channel();
        $channel->setEnabled(true);

        $managerMock = $this->createEntityManagerMock();
        $managerMock
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf('JMS\JobQueueBundle\Entity\Job'))
        ;
        $managerMock
            ->expects($this->once())
            ->method('flush')
        ;

        $scheduler = new GenuineSyncScheduler($this->createRegistryStub($managerMock));

        $scheduler->schedule($channel);
    }

    public function testShouldReturnCreatedJob()
    {
        $channel = new Channel();
        $channel->setEnabled(true);

        $this->writeIdProperty($channel, 123);

        $managerMock = $this->createEntityManagerMock();

        $scheduler = new GenuineSyncScheduler($this->createRegistryStub($managerMock));

        $job = $scheduler->schedule($channel);

        $this->assertInstanceOf('JMS\JobQueueBundle\Entity\Job', $job);
        $this->assertEquals(SyncCommand::COMMAND_NAME, $job->getCommand());
        $this->assertEquals(['--integration-id=123', '-v'], $job->getArgs());
    }

    public function testShouldAllowPassTheForceCliOption()
    {
        $channel = new Channel();
        $channel->setEnabled(true);

        $this->writeIdProperty($channel, 123);

        $managerMock = $this->createEntityManagerMock();

        $scheduler = new GenuineSyncScheduler($this->createRegistryStub($managerMock));

        $job = $scheduler->schedule($channel, true);

        $this->assertInstanceOf('JMS\JobQueueBundle\Entity\Job', $job);
        $this->assertEquals(SyncCommand::COMMAND_NAME, $job->getCommand());
        $this->assertEquals(['--integration-id=123', '-v', '--force'], $job->getArgs());
    }

    /**
     * @param EntityManagerInterface $em
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected function createRegistryStub(EntityManagerInterface $em = null)
    {
        $registryMock = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $registryMock
            ->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em)
        ;

        return $registryMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EntityManagerInterface
     */
    protected function createEntityManagerMock()
    {
        return $this->getMock('Doctrine\ORM\EntityManagerInterface');
    }

    /**
     * @param object $object
     * @param int $id
     */
    protected function writeIdProperty($object, $id)
    {
        $rp = new \ReflectionProperty($object, 'id');
        $rp->setAccessible(true);
        $rp->setValue($object, $id);
        $rp->setAccessible(false);
    }
}

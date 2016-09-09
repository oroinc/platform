<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Manager;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Manager\DeleteManager;

use Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture\TestIntegrationDeleteProvider;

class ChannelDeleteManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DeleteManager
     */
    protected $deleteManager;

    /**
     * @var Integration
     */
    protected $testIntegration;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $em;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ClassMetadata
     */
    protected $entityMetadata;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $connection;

    protected function setUp()
    {
        $this->entityMetadata = $this->getMockBuilder('\Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()->getMock();

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
        $this->deleteManager->addProvider(new TestIntegrationDeleteProvider());
        $this->testIntegration = new Integration();
        $this->testIntegration->setType('test');
    }

    public function testDeleteChannelWithoutErrors()
    {
        $this->entityMetadata->expects(self::once())->method('getTableName')->willReturn('table');
        $this->em->expects(self::once())->method('getClassMetadata')->with('OroIntegrationBundle:Status')
            ->willReturn($this->entityMetadata);
        $this->connection->expects($this->once())
            ->method('commit');
        $this->em->expects($this->any())
            ->method('remove')
            ->with($this->equalTo($this->testIntegration));
        $this->em->expects($this->any())
            ->method('flush');

        $this->assertTrue($this->deleteManager->delete($this->testIntegration));
    }

    public function testDeleteIntegrationWithErrors()
    {
        $this->entityMetadata->expects(self::once())->method('getTableName')->willReturn('table');
        $this->em->expects(self::once())->method('getClassMetadata')->with('OroIntegrationBundle:Status')
            ->willReturn($this->entityMetadata);
        $this->em->expects($this->any())
            ->method('remove')
            ->with($this->equalTo($this->testIntegration))
            ->will($this->throwException(new \Exception()));
        $this->connection->expects($this->once())
            ->method('rollback');
        $this->assertFalse($this->deleteManager->delete($this->testIntegration));
    }
}

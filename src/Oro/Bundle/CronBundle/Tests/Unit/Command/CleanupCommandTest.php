<?php

namespace Oro\Bundle\CronBundle\Command;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\CronBundle\Tests\Unit\Stub\MemoryOutput;

class CleanupCommandTest extends \PHPUnit_Framework_TestCase
{
    /** @var CleanupCommand */
    protected $command;

    /** @var ContainerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $container;

    public function setUp()
    {
        $this->command = new CleanupCommand();

        $this->container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $this->command->setContainer($this->container);
    }

    public function tearDown()
    {
        unset($this->container, $this->command);
    }

    public function testConfiguration()
    {
        $this->command->configure();

        $this->assertNotEmpty($this->command->getDescription());
        $this->assertNotEmpty($this->command->getName());
        $this->assertTrue($this->command->getDefinition()->hasOption('dry-run'));
    }

    public function testIsValidCronCommand()
    {
        $this->assertInstanceOf('Oro\Bundle\CronBundle\Command\CronCommandInterface', $this->command);

        $this->assertContains('oro:cron:', $this->command->getName(), 'name should start with oro:cron');
        $this->assertInternalType('string', $this->command->getDefaultDefinition());
    }

    public function testDryExecution()
    {
        $params    = ['-d' => true];
        $input     = new ArrayInput($params, $this->command->getDefinition());
        $output    = new MemoryOutput();

        $queryMock = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getSingleScalarResult'])
            ->getMockForAbstractClass();

        $queryMock->expects($this->once())->method('getSingleScalarResult')
            ->will($this->returnValue('0'));

        $qbMock = $this->getQueryBuilderMock();
        $qbMock->expects($this->once())
            ->method('select')
            ->with('COUNT(j.id)')
            ->will($this->returnSelf());

        $qbMock->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($queryMock));

        $this->command->execute($input, $output);
    }

    public function testExecution()
    {
        $params    = [];
        $input     = new ArrayInput($params, $this->command->getDefinition());
        $output    = new MemoryOutput();

        $queryMock = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['execute'])
            ->getMockForAbstractClass();

        $queryMock->expects($this->once())
            ->method('execute')
            ->will($this->returnValue(1));

        $qbMock = $this->getQueryBuilderMock();
        $qbMock->expects($this->once())
            ->method('delete')
            ->will($this->returnSelf());

        $qbMock->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($queryMock));

        $this->command->execute($input, $output);
    }

    protected function getQueryBuilderMock()
    {
        $qbMock = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $qbMock->expects($this->once())
            ->method('from')
            ->will($this->returnSelf());

        $qbMock->expects($this->once())
            ->method('where')
            ->will($this->returnSelf());

        $qbMock->expects($this->once())
            ->method('andWhere')
            ->will($this->returnSelf());

        $qbMock->expects($this->once())
            ->method('setParameters')
            ->will($this->returnSelf());

        $emMock = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();
        $emMock->expects($this->once())
            ->method('createQueryBuilder')
            ->will($this->returnValue($qbMock));


        $this->container->expects($this->once())
            ->method('get')
            ->with('doctrine.orm.entity_manager')
            ->will($this->returnValue($emMock));

        return $qbMock;
    }
}

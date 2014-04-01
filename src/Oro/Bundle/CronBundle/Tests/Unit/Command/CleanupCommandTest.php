<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\Command;

use Doctrine\ORM\EntityManager;

use JMS\JobQueueBundle\Entity\Job;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\CronBundle\Command\CleanupCommand;
use Oro\Bundle\CronBundle\Tests\Unit\Stub\MemoryOutput;
use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\OrmTestCase;

class CleanupCommandTest extends OrmTestCase
{
    /** @var CleanupCommand */
    protected $command;

    /** @var ContainerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $container;

    /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $emMock;

    public function setUp()
    {
        $this->command = new CleanupCommand();

        $this->container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $this->command->setContainer($this->container);

        $this->emMock = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();
    }

    public function tearDown()
    {
        unset($this->container, $this->command, $this->emMock);
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
        $params = ['-d' => true];
        $input  = new ArrayInput($params, $this->command->getDefinition());
        $output = new MemoryOutput();

        $stm = $this->getStatementMock();
        $stm->expects($this->once())
            ->method('fetchColumn')
            ->will($this->returnValue(1));

        $this->command->execute($input, $output);
    }

    protected function getStatementMock()
    {
        $statement = $this->getMock('\Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\Mocks\StatementMock');
        $statement->expects($this->exactly(2))
            ->method('bindValue');

        $statement->expects($this->once())
            ->method('execute');

        $conn = $this->getMockBuilder('\Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->setMethods(['prepare'])
            ->getMock();

        $conn->expects($this->once())
            ->method('prepare')
            ->will($this->returnValue($statement));

        $this->emMock->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($conn));

        $this->container->expects($this->once())
            ->method('get')
            ->with('doctrine.orm.entity_manager')
            ->will($this->returnValue($this->emMock));

        return $statement;
    }

    public function testExecution()
    {
        $params = [];
        $input  = new ArrayInput($params, $this->command->getDefinition());
        $output = new MemoryOutput();

        $stm = $this->getStatementMock();
        $stm->expects($this->at(3))
            ->method('fetchColumn')
            ->will($this->returnValue(1));
        $stm->expects($this->at(4))
            ->method('fetchColumn')
            ->will($this->returnValue(false));


        $this->emMock->expects($this->any())
            ->method('beginTransaction');

        $this->emMock->expects($this->any())
            ->method('commit');

        $this->container->expects($this->once())
            ->method('get')
            ->with('doctrine.orm.entity_manager')
            ->will($this->returnValue($this->emMock));


        $command = $this->getMock(
            'Oro\Bundle\CronBundle\Command\CleanupCommand',
            ['processBuff'],
            [CleanupCommand::COMMAND_NAME]
        );
        $command->setContainer($this->container);

        $command->expects($this->exactly(2))
            ->method('processBuff')
            ->will($this->returnValue([]));

        $command->execute($input, $output);
    }

    protected function getQueryBuilderMock()
    {
        $qbMock = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $qbMock->expects($this->any())
            ->method('from')
            ->will($this->returnSelf());

        $qbMock->expects($this->any())
            ->method('where')
            ->will($this->returnSelf());

        $qbMock->expects($this->any())
            ->method('andWhere')
            ->will($this->returnSelf());

        $qbMock->expects($this->any())
            ->method('setParameters')
            ->will($this->returnSelf());


        $this->emMock->expects($this->any())
            ->method('createQueryBuilder')
            ->will($this->returnValue($qbMock));


        $this->container->expects($this->once())
            ->method('get')
            ->with('doctrine.orm.entity_manager')
            ->will($this->returnValue($this->emMock));

        return $qbMock;
    }
}

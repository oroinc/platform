<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\Command;

use Oro\Bundle\CronBundle\Command\JobsCountCommand;

use Oro\Bundle\CronBundle\Helper\JmsJobHelper;

use JMS\JobQueueBundle\Entity\Job;

class JobsCountCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var JobsCountCommand
     */
    protected $command;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|JmsJobHelper
     */
    protected $helper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->command = new JobsCountCommand();

        $this->helper = $this->getMockBuilder('Oro\Bundle\CronBundle\Helper\JmsJobHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');

        $container->expects($this->any())
            ->method('get')
            ->with('oro_cron.jms_job_helper')
            ->willReturn($this->helper);

        $this->command->setContainer($container);
    }

    /**
     * Test configure method
     */
    public function testConfigure()
    {
        $this->command->configure();

        $this->assertNotEmpty($this->command->getDescription());
        $this->assertNotEmpty($this->command->getName());
        $this->assertTrue($this->command->getDefinition()->hasOption('state'));
    }

    /**
     * @param mixed $optionalState
     * @param mixed $expectedState
     * @param mixed $count
     * @param mixed $expectedMessageContent
     *
     * @dataProvider providerForExecute
     */
    public function testExecute($optionalState, $expectedState, $count, $expectedMessageContent)
    {
        $input  = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $input->expects($this->once())
            ->method('getOption')
            ->with('state')
            ->willReturn($optionalState);

        if ($expectedState) {
            $this->helper->expects($this->once())
                ->method('getPendingJobsCount')
                ->with($expectedState)
                ->willReturn($count);
        }

        $output->expects($this->once())
            ->method('writeln')
            ->with($this->stringContains($expectedMessageContent));

        $this->command->execute($input, $output);
    }

    /**
     * @return array
     */
    public function providerForExecute()
    {
        return [
            [null,           Job::STATE_PENDING, 42,   '42'           ],
            [Job::STATE_NEW, Job::STATE_NEW,     42,   '42'           ],
            ['unknown',      null,               null, 'Invalid state'],
        ];
    }
}

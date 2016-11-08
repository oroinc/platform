<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Command;

use Oro\Bundle\EmailBundle\Command\EmailBodySyncCommand;

class EmailBodySyncCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EmailBodySyncCommand
     */
    private $command;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $container;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $input;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $output;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $featureChecker;

    protected function setUp()
    {
        $this->command = new EmailBodySyncCommand();

        $this->container = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');
        $this->command->setContainer($this->container);

        $this->input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $this->output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $this->featureChecker = $this->getMockBuilder('Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testConfiguration()
    {
        $this->command->configure();

        $this->assertNotEmpty($this->command->getDescription());
        $this->assertNotEmpty($this->command->getName());
    }

    /**
     * @dataProvider provideMethod
     */
    public function testExecute()
    {
        $this->container
            ->expects($this->once())
            ->method('get')
            ->with('oro_featuretoggle.checker.feature_checker')
            ->willReturn($this->featureChecker);

        $this->command->execute($this->input, $this->output);
    }

    public function testExecuteReturns0()
    {
        $this->container
            ->expects($this->once())
            ->method('get')
            ->with('oro_featuretoggle.checker.feature_checker')
            ->willReturn($this->featureChecker);

        $this->assertSame(0, $this->command->execute($this->input, $this->output));
    }

    /**
     * Data provider
     *
     * @return array
     */
    public function provideMethod()
    {
        return array(
            array('GET'),
            array('ANY'),
            array(
                array('POST', 'GET')
            ),
        );
    }
}

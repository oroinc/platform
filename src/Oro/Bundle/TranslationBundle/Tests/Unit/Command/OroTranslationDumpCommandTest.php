<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\TranslationBundle\Command\OroTranslationDumpCommand;
use Oro\Bundle\TranslationBundle\Provider\JsTranslationDumper;
use Oro\Component\Log\OutputLogger;

class OroTranslationDumpCommandTest extends \PHPUnit_Framework_TestCase
{
    /** @var ContainerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $container;

    /** @var JsTranslationDumper|\PHPUnit_Framework_MockObject_MockObject */
    protected $jsDumper;

    /** @var InputInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $input;

    /** @var OutputInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $output;

    /** @var OroTranslationDumpCommand */
    protected $command;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->jsDumper = $this->getMockBuilder(JsTranslationDumper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->container = $this->getMock(ContainerInterface::class);
        $this->container->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap([
                ['oro_translation.js_dumper', 1, $this->jsDumper],
            ]));

        $this->input = $this->getMock(InputInterface::class);
        $this->output = $this->getMock(OutputInterface::class);

        $this->command = new OroTranslationDumpCommand();
        $this->command->setContainer($this->container);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->jsDumper, $this->container, $this->input, $this->output, $this->command);
    }

    public function testConfigure()
    {
        $this->assertNotEmpty($this->command->getDescription());
        $this->assertNotEmpty($this->command->getName());
        $this->assertTrue($this->command->getDefinition()->hasArgument('locale'));
        $this->assertTrue($this->command->getDefinition()->hasOption('debug'));
    }

    public function testExecute()
    {
        $locales = ['locale1', 'locale2'];

        $this->input->expects($this->once())
            ->method('getArgument')
            ->with('locale')
            ->willReturn($locales);

        $this->jsDumper->expects($this->once())
            ->method('setLogger')
            ->with(new OutputLogger($this->output));

        $this->jsDumper->expects($this->once())->method('dumpTranslations')->with($locales);
        $this->command->run($this->input, $this->output);
    }
}

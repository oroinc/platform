<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Command;

use Oro\Bundle\TranslationBundle\Command\OroTranslationDumpCommand;
use Oro\Bundle\TranslationBundle\Provider\JsTranslationDumper;
use Oro\Component\Log\OutputLogger;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OroTranslationDumpCommandTest extends \PHPUnit\Framework\TestCase
{
    /** @var JsTranslationDumper|\PHPUnit\Framework\MockObject\MockObject */
    protected $jsDumper;

    /** @var InputInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $input;

    /** @var OutputInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $output;

    /** @var OroTranslationDumpCommand */
    protected $command;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->jsDumper = $this->createMock(JsTranslationDumper::class);
        $this->input = $this->createMock(InputInterface::class);
        $this->output = $this->createMock(OutputInterface::class);

        $this->command = new OroTranslationDumpCommand($this->jsDumper);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        unset($this->jsDumper, $this->input, $this->output, $this->command);
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

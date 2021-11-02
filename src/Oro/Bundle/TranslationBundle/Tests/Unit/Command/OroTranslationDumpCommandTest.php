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
    private $jsDumper;

    /** @var InputInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $input;

    /** @var OutputInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $output;

    /** @var OroTranslationDumpCommand */
    private $command;

    protected function setUp(): void
    {
        $this->jsDumper = $this->createMock(JsTranslationDumper::class);
        $this->input = $this->createMock(InputInterface::class);
        $this->output = $this->createMock(OutputInterface::class);

        $this->command = new OroTranslationDumpCommand($this->jsDumper);
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

        $this->jsDumper->expects($this->once())
            ->method('dumpTranslations')
            ->with($locales);

        $this->command->run($this->input, $this->output);
    }
}

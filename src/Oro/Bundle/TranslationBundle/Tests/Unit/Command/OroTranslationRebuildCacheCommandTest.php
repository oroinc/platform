<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Command;

use Oro\Bundle\TranslationBundle\Cache\RebuildTranslationCacheProcessor;
use Oro\Bundle\TranslationBundle\Command\OroTranslationRebuildCacheCommand;
use Oro\Component\Testing\Command\CommandTestingTrait;

class OroTranslationRebuildCacheCommandTest extends \PHPUnit\Framework\TestCase
{
    use CommandTestingTrait;

    /** @var RebuildTranslationCacheProcessor|\PHPUnit\Framework\MockObject\MockObject */
    private $rebuildTranslationCacheProcessor;

    /** @var OroTranslationRebuildCacheCommand */
    private $command;

    protected function setUp(): void
    {
        $this->rebuildTranslationCacheProcessor = $this->createMock(RebuildTranslationCacheProcessor::class);

        $this->command = new OroTranslationRebuildCacheCommand($this->rebuildTranslationCacheProcessor);
    }

    public function testExecuteSuccess()
    {
        $this->rebuildTranslationCacheProcessor->expects(self::once())
            ->method('rebuildCache')
            ->willReturn(true);

        $commandTester = $this->doExecuteCommand($this->command);

        $this->assertSuccessReturnCode($commandTester);
        $this->assertOutputContains($commandTester, 'Rebuilding the translation cache ...');
        $this->assertOutputContains($commandTester, 'The rebuild complete.');
    }

    public function testExecuteFailed()
    {
        $this->rebuildTranslationCacheProcessor->expects(self::once())
            ->method('rebuildCache')
            ->willReturn(false);

        $commandTester = $this->doExecuteCommand($this->command);

        $this->assertOutputContains($commandTester, 'Rebuilding the translation cache ...');
        $this->assertProducedError($commandTester, 'The rebuild failed.');
    }
}

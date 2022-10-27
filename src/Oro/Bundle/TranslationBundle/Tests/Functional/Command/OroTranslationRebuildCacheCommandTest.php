<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Command;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Command\CommandTestingTrait;

class OroTranslationRebuildCacheCommandTest extends WebTestCase
{
    use CommandTestingTrait;

    public function testExecute()
    {
        $commandTester = $this->doExecuteCommand('oro:translation:rebuild-cache');

        $this->assertSuccessReturnCode($commandTester);
        $this->assertOutputContains($commandTester, 'Rebuilding the translation cache ...');
        $this->assertOutputContains($commandTester, 'The rebuild complete.');
    }
}

<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Command;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadLanguages;

/**
 * @dbIsolation
 */
class OroTranslationLoadCommandTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadLanguages::class]);
    }

    public function testExecuteDefaultLanguage()
    {
        $result = $this->runCommand('oro:translation:load', ['--languages=en']);

        $this->assertNotEmpty($result);
        $this->assertContains('Loading translations', $result);
        $this->assertContains('loading [jsmessages]', $result);
        $this->assertContains('loading [entities]', $result);
        $this->assertContains('loading [workflows]', $result);
        $this->assertContains('All messages successfully loaded.', $result);
        $this->assertNotContains('Rebuilding cache', $result);
    }

    public function testExecuteEmptyLanguage()
    {
        $result = $this->runCommand(
            'oro:translation:load',
            ['--languages=' . LoadLanguages::LANGUAGE1, '--rebuild-cache=0']
        );

        $this->assertNotEmpty($result);
        $this->assertContains('All messages successfully loaded.', $result);
        $this->assertNotContains('Rebuilding cache', $result);
    }
}

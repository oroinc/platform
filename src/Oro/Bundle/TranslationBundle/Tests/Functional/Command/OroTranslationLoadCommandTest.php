<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Command;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadLanguages;

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
        $this->assertContains('loading [messages]', $result);
        $this->assertContains('loading [jsmessages]', $result);
        $this->assertContains('All messages successfully processed.', $result);
        $this->assertNotContains('Rebuilding cache', $result);
    }

    public function testExecuteEmptyLanguage()
    {
        $result = $this->runCommand('oro:translation:load', ['--languages=' . LoadLanguages::LANGUAGE1]);

        $this->assertNotEmpty($result);
        $this->assertContains('All messages successfully processed.', $result);
        $this->assertNotContains('Rebuilding cache', $result);
    }

    public function testExecuteWithNotExistedLanguage()
    {
        $result = $this->runCommand('oro:translation:load', ['--languages=NotExisted']);

        $this->assertNotEmpty($result);
        $this->assertContains('Language "NotExisted" not found', $result);
        $this->assertNotContains('Rebuilding cache', $result);
    }

    public function testExecuteWithRebuildCache()
    {
        $this->markTestSkipped('Skipped due takes much time to rebuild translation cache');

        $result = $this->runCommand('oro:translation:load', [
            '--languages=' . LoadLanguages::LANGUAGE1,
            '--rebuild-cache'
        ]);

        $this->assertNotEmpty($result);
        $this->assertContains('All messages successfully processed.', $result);
        $this->assertContains('Rebuilding cache', $result);
    }
}

<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Command;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadLanguages;

class OroTranslationLoadCommandTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadLanguages::class]);
    }

    public function testExecuteDefaultLanguage()
    {
        $result = $this->runCommand('oro:translation:load', ['--languages=en']);

        $this->assertNotEmpty($result);
        static::assertStringContainsString('Loading translations', $result);
        static::assertStringContainsString('loading [messages]', $result);
        static::assertStringContainsString('loading [jsmessages]', $result);
        static::assertStringContainsString('All messages successfully processed.', $result);
        static::assertStringNotContainsString('Rebuilding cache', $result);
    }

    public function testExecuteEmptyLanguage()
    {
        $result = $this->runCommand('oro:translation:load', ['--languages=' . LoadLanguages::LANGUAGE1]);

        $this->assertNotEmpty($result);
        static::assertStringContainsString('All messages successfully processed.', $result);
        static::assertStringNotContainsString('Rebuilding cache', $result);
    }

    public function testExecuteWithNotExistedLanguage()
    {
        $result = $this->runCommand('oro:translation:load', ['--languages=NotExisted']);

        $this->assertNotEmpty($result);
        static::assertStringContainsString('Language "NotExisted" not found', $result);
        static::assertStringNotContainsString('Rebuilding cache', $result);
    }

    public function testExecuteWithRebuildCache()
    {
        $this->markTestSkipped('Skipped due takes much time to rebuild translation cache');

        $result = $this->runCommand('oro:translation:load', [
            '--languages=' . LoadLanguages::LANGUAGE1,
            '--rebuild-cache'
        ]);

        $this->assertNotEmpty($result);
        static::assertStringContainsString('All messages successfully processed.', $result);
        static::assertStringContainsString('Rebuilding cache', $result);
    }
}

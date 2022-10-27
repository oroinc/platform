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
        self::assertStringContainsString('Loading translations', $result);
        self::assertStringContainsString('loading [messages]', $result);
        self::assertStringContainsString('loading [jsmessages]', $result);
        self::assertStringContainsString('All messages successfully processed.', $result);
        self::assertStringNotContainsString('Rebuilding cache', $result);
    }

    public function testExecuteEmptyLanguage()
    {
        $result = $this->runCommand('oro:translation:load', ['--languages=' . LoadLanguages::LANGUAGE1]);

        $this->assertNotEmpty($result);
        self::assertStringContainsString('All messages successfully processed.', $result);
        self::assertStringNotContainsString('Rebuilding cache', $result);
    }

    public function testExecuteWithNotExistedLanguage()
    {
        $result = $this->runCommand('oro:translation:load', ['--languages=NotExisted']);

        $this->assertNotEmpty($result);
        self::assertStringContainsString('Language "NotExisted" not found', $result);
        self::assertStringNotContainsString('Rebuilding cache', $result);
    }

    public function testExecuteWithRebuildCache()
    {
        $result = $this->runCommand('oro:translation:load', [
            '--languages=' . LoadLanguages::LANGUAGE1,
            '--rebuild-cache'
        ]);

        $this->assertNotEmpty($result);
        self::assertStringContainsString('All messages successfully processed.', $result);
        self::assertStringContainsString('Rebuilding cache', $result);
    }
}

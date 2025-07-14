<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Helper;

use Oro\Bundle\TranslationBundle\Helper\FileBasedLanguageHelper;
use PHPUnit\Framework\TestCase;

class FileBasedLanguageHelperTest extends TestCase
{
    private $helper;

    #[\Override]
    protected function setUp(): void
    {
        $this->helper = new FileBasedLanguageHelper(realpath(__DIR__ . '/translations'));
    }

    public function testIsFileBasedLocaleForFileBasedLocale(): void
    {
        self::assertTrue($this->helper->isFileBasedLocale('fr_FR'));
    }

    public function testIsFileBasedLocaleForNotFileBasedLocale(): void
    {
        self::assertfalse($this->helper->isFileBasedLocale('ua_UA'));
    }
}

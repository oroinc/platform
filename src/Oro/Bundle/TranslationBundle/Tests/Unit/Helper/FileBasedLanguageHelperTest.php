<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Helper;

use Oro\Bundle\TranslationBundle\Helper\FileBasedLanguageHelper;

class FileBasedLanguageHelperTest extends \PHPUnit\Framework\TestCase
{
    private $helper;

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

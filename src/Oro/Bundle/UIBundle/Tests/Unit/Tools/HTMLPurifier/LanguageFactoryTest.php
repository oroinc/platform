<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Tools\HTMLPurifier;

use Oro\Bundle\UIBundle\Tools\HTMLPurifier\Language;
use Oro\Bundle\UIBundle\Tools\HTMLPurifier\LanguageFactory;
use Symfony\Contracts\Translation\TranslatorInterface;

class LanguageFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testInstance(): void
    {
        $this->assertInstanceOf(LanguageFactory::class, LanguageFactory::instance());
    }

    public function testCreate(): void
    {
        $config = $this->createMock(\HTMLPurifier_Config::class);
        $context = $this->createMock(\HTMLPurifier_Context::class);
        $translator = $this->createMock(TranslatorInterface::class);

        $languageFactory = LanguageFactory::instance();
        $languageFactory->setTranslator($translator);
        $language = $languageFactory->create($config, $context);

        $expectedLanguage = new Language($config, $context);
        $expectedLanguage->setTranslator($translator);

        $this->assertEquals($expectedLanguage, $language);
    }

    public function testLoadLanguage(): void
    {
        $translatedValue = 'translated value';
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->exactly(38))
            ->method('trans')
            ->with($this->stringContains('oro.htmlpurifier.messages'))
            ->willReturn($translatedValue);

        $languageFactory = LanguageFactory::instance();
        $languageFactory->setTranslator($translator);

        $this->assertEmpty($languageFactory->cache);

        $languageFactory->loadLanguage('en');

        $this->assertNotEmpty($languageFactory->cache);
        $this->assertNotEmpty($languageFactory->cache['en']['errorNames']);

        $messages = $languageFactory->cache['en']['messages'];
        $this->assertNotEmpty($messages);
        foreach ($messages as $message) {
            $this->assertEquals($translatedValue, $message);
        }
    }
}

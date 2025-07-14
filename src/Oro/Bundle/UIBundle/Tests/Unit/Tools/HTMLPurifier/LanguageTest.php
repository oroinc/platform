<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Tools\HTMLPurifier;

use Oro\Bundle\UIBundle\Tools\HTMLPurifier\Language;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class LanguageTest extends TestCase
{
    private \HTMLPurifier_Config&MockObject $config;
    private \HTMLPurifier_Context&MockObject $context;
    private Language $language;

    #[\Override]
    protected function setUp(): void
    {
        $this->config = $this->createMock(\HTMLPurifier_Config::class);
        $this->context = $this->createMock(\HTMLPurifier_Context::class);

        $this->language = new Language($this->config, $this->context);
    }

    public function testNotLoaded(): void
    {
        $this->assertFalse($this->language->_loaded);
        $this->assertEmpty($this->language->messages);
        $this->assertEmpty($this->language->errorNames);
    }

    public function testLoad(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $this->language->setTranslator($translator);

        $this->language->load();

        $this->assertTrue($this->language->_loaded);
        $this->assertNotEmpty($this->language->messages);
        $this->assertNotEmpty($this->language->errorNames);
    }
}

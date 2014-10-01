<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Twig;

use Oro\Bundle\TranslationBundle\Twig\TranslationExtension;

class TranslationExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param bool $debugTranslator
     * @return TranslationExtension
     */
    protected function createExtension($debugTranslator = false)
    {
        return new TranslationExtension($debugTranslator);
    }

    public function testGetName()
    {
        $extension = $this->createExtension();
        $this->assertEquals(TranslationExtension::NAME, $extension->getName());
    }

    public function testFunctions()
    {
        $extension = $this->createExtension(true);
        $functions = $extension->getFunctions();
        $this->assertCount(1, $functions);

        /** @var \Twig_SimpleFunction $debugTranslator */
        $debugTranslator = current($functions);
        $this->assertInstanceOf('\Twig_SimpleFunction', $debugTranslator);
        $this->assertEquals('oro_translation_debug_translator', $debugTranslator->getName());
        $this->assertTrue(call_user_func($debugTranslator->getCallable()));
    }
}

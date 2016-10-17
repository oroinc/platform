<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Twig;

use Oro\Bundle\TranslationBundle\Helper\TranslationRouteHelper;
use Oro\Bundle\TranslationBundle\Twig\TranslationExtension;

class TranslationExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var TranslationRouteHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $translationRouteHelper;

    protected function setUp()
    {
        $this->translationRouteHelper = $this->getMockBuilder(TranslationRouteHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function tearDown()
    {
        unset($this->translationRouteHelper);
    }

    /**
     * @param bool $debugTranslator
     *
     * @return TranslationExtension
     */
    protected function createExtension($debugTranslator = false)
    {
        return new TranslationExtension($debugTranslator, $this->translationRouteHelper);
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
        $this->assertCount(2, $functions);

        /** @var \Twig_SimpleFunction $debugTranslator */
        $debugTranslator = current($functions);
        $this->assertInstanceOf('\Twig_SimpleFunction', $debugTranslator);
        $this->assertEquals('oro_translation_debug_translator', $debugTranslator->getName());
        $this->assertTrue(call_user_func($debugTranslator->getCallable()));

        $this->translationRouteHelper->expects($this->once())->method('generate')->willReturn("");

        /** @var \Twig_SimpleFunction $translationGridLink */
        $translationGridLink = next($functions);
        $this->assertInstanceOf('\Twig_SimpleFunction', $translationGridLink);
        $this->assertEquals('translation_grid_link', $translationGridLink->getName());
        $this->assertInternalType(
            "string",
            call_user_func_array(
                $translationGridLink->getCallable(),
                [[]]
            )
        );
    }
}

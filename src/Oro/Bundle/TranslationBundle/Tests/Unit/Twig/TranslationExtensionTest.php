<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Twig;

use Oro\Bundle\TranslationBundle\Helper\TranslationsDatagridRouteHelper;
use Oro\Bundle\TranslationBundle\Twig\TranslationExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TranslationExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private TranslationsDatagridRouteHelper&MockObject $translationRouteHelper;
    private TranslationExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->translationRouteHelper = $this->createMock(TranslationsDatagridRouteHelper::class);

        $container = self::getContainerBuilder()
            ->add(TranslationsDatagridRouteHelper::class, $this->translationRouteHelper)
            ->getContainer($this);

        $this->extension = new TranslationExtension($container, true, true);
    }

    public function testIsDebugTranslator(): void
    {
        $this->assertTrue(
            self::callTwigFunction($this->extension, 'oro_translation_debug_translator', [])
        );
    }

    public function testIsDebugJsTranslations(): void
    {
        $this->assertTrue(
            self::callTwigFunction($this->extension, 'oro_translation_debug_js_translations', [])
        );
    }

    public function testGetTranslationGridLink(): void
    {
        $filters = ['key' => 'val'];
        $referenceType = UrlGeneratorInterface::RELATIVE_PATH;
        $link = 'test link';

        $this->translationRouteHelper->expects(self::once())
            ->method('generate')
            ->with($filters, $referenceType)
            ->willReturn($link);

        $this->assertEquals(
            $link,
            self::callTwigFunction($this->extension, 'translation_grid_link', [$filters, $referenceType])
        );
    }

    public function testGetTranslationGridLinkWhenReferenceTypeNotSpecified(): void
    {
        $filters = ['key' => 'val'];
        $link = 'test link';

        $this->translationRouteHelper->expects(self::once())
            ->method('generate')
            ->with($filters, UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn($link);

        $this->assertEquals(
            $link,
            self::callTwigFunction($this->extension, 'translation_grid_link', [$filters])
        );
    }
}

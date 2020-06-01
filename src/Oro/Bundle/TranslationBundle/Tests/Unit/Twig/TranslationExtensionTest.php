<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Twig;

use Oro\Bundle\TranslationBundle\Helper\TranslationsDatagridRouteHelper;
use Oro\Bundle\TranslationBundle\Twig\TranslationExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class TranslationExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var TranslationsDatagridRouteHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $translationRouteHelper;

    /** @var TranslationExtension */
    protected $extension;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->translationRouteHelper = $this->createMock(TranslationsDatagridRouteHelper::class);

        $container = self::getContainerBuilder()
            ->add('oro_translation.helper.translation_route', $this->translationRouteHelper)
            ->getContainer($this);

        $this->extension = new TranslationExtension($container, true, true);
    }

    public function testGetName(): void
    {
        $this->assertEquals(TranslationExtension::NAME, $this->extension->getName());
    }

    public function testIsDebugTranslator(): void
    {
        $this->assertTrue($this->extension->isDebugTranslator());
    }

    public function testIsDebugJsTranslations(): void
    {
        $this->assertTrue($this->extension->isDebugJsTranslations());
    }
}

<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Twig;

use Oro\Bundle\TranslationBundle\Helper\TranslationsDatagridRouteHelper;
use Oro\Bundle\TranslationBundle\Twig\TranslationExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class TranslationExtensionTest extends \PHPUnit_Framework_TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var TranslationsDatagridRouteHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $translationRouteHelper;

    /** @var TranslationExtension */
    protected $extension;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->translationRouteHelper = $this->getMockBuilder(TranslationsDatagridRouteHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container = self::getContainerBuilder()
            ->add('oro_translation.helper.translation_route', $this->translationRouteHelper)
            ->getContainer($this);

        $this->extension = new TranslationExtension($container, true);
    }

    public function testGetName()
    {
        $this->assertEquals(TranslationExtension::NAME, $this->extension->getName());
    }
}

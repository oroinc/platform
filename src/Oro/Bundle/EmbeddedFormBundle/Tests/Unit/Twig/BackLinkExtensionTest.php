<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Twig;

use Oro\Bundle\EmbeddedFormBundle\Twig\BackLinkExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

class BackLinkExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $router;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    /** @var BackLinkExtension */
    protected $extension;

    protected function setUp()
    {
        $this->router = $this->createMock(RouterInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $container = self::getContainerBuilder()
            ->add('router', $this->router)
            ->add('translator', $this->translator)
            ->getContainer($this);

        $this->extension = new BackLinkExtension($container);
    }

    public function testShouldReturnName()
    {
        $this->assertEquals(
            'oro_embedded_form_back_link_extension',
            $this->extension->getName()
        );
    }

    public function testShouldReplacePlaceholderWithProvidedUrlAndLinkText()
    {
        $id = uniqid('id');
        $url = uniqid('url');
        $text = uniqid('text');
        $translatedText = uniqid('translatedText');
        $originalString = 'Before link {back_link|' . $text . '} After link';
        $expectedString = 'Before link <a href="' . $url . '">' . $translatedText . '</a> After link';

        $this->router->expects($this->once())
            ->method('generate')
            ->with('oro_embedded_form_submit', ['id' => $id])
            ->will($this->returnValue($url));
        $this->translator->expects($this->once())
            ->method('trans')
            ->with($text)
            ->will($this->returnValue($translatedText));

        $this->assertEquals(
            $expectedString,
            self::callTwigFilter($this->extension, 'back_link', [$originalString, $id])
        );
    }

    public function testShouldReplacePlaceholderWithReloadLinkAndLinkText()
    {
        $text = uniqid('text');
        $translatedText = uniqid('translatedText');
        $originalString = 'Before link {back_link|' . $text . '} After link';
        $expectedLink = '<a href="#" onclick="window.location.reload(true); return false;">'
                        . $translatedText
                        . '</a>';
        $expectedString = 'Before link ' . $expectedLink . ' After link';

        $this->translator->expects($this->once())
            ->method('trans')
            ->with($text)
            ->will($this->returnValue($translatedText));

        $this->assertEquals(
            $expectedString,
            self::callTwigFilter($this->extension, 'back_link', [$originalString, null])
        );
    }

    public function testShouldReplacePlaceholderWithProvidedUrlAndDefaultLinkText()
    {
        $id = uniqid('id');
        $url = uniqid('url');
        $originalString = 'Before link {back_link} After link';
        $expectedString = 'Before link <a href="' . $url . '">Back</a> After link';

        $this->router->expects($this->once())
            ->method('generate')
            ->with('oro_embedded_form_submit', ['id' => $id])
            ->will($this->returnValue($url));
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('oro.embeddedform.back_link_default_text')
            ->will($this->returnValue('Back'));

        $this->assertEquals(
            $expectedString,
            self::callTwigFilter($this->extension, 'back_link', [$originalString, $id])
        );
    }

    public function testShouldReturnOriginalStringWhenNoPlaceholderProvided()
    {
        $originalString = uniqid('any string');

        $this->assertEquals(
            $originalString,
            self::callTwigFilter($this->extension, 'back_link', [$originalString, uniqid('id')])
        );
    }
}

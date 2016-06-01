<?php
namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Twig;

use Oro\Bundle\EmbeddedFormBundle\Twig\BackLinkExtension;

class BackLinkExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function shouldBeConstructed()
    {
        $this->createBackLinkExtension();
    }

    /**
     * @test
     */
    public function shouldReturnName()
    {
        $this->assertEquals(
            'oro_embedded_form_back_link_extension',
            $this->createBackLinkExtension()->getName()
        );
    }

    /**
     * @test
     */
    public function shouldReturnTwigFilter()
    {
        $extension = $this->createBackLinkExtension();
        $filters = $extension->getFilters();

        $this->assertCount(1, $filters);

        $backLinkFilter = $filters[0];

        $this->assertInstanceOf('Twig_SimpleFilter', $backLinkFilter);

        $this->assertEquals('back_link', $backLinkFilter->getName());
        $this->assertSame([$extension, 'backLinkFilter'], $backLinkFilter->getCallable());
    }

    /**
     * @test
     */
    public function shouldReplacePlaceholderWithProvidedUrlAndLinkText()
    {
        $id = uniqid('id');
        $url = uniqid('url');
        $text = uniqid('text');
        $translatedText = uniqid('translatedText');
        $originalString = 'Before link {back_link|' . $text . '} After link';
        $expectedString = 'Before link <a href="' . $url . '">' . $translatedText . '</a> After link';

        $router = $this->getMock('Symfony\Component\Routing\Router', [], [], '', false);
        $router->expects($this->once())
            ->method('generate')
            ->with('oro_embedded_form_submit', ['id' => $id])
            ->will($this->returnValue($url));

        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface', [], [], '', false);
        $translator->expects($this->once())
            ->method('trans')
            ->with($text)
            ->will($this->returnValue($translatedText));

        $extension = $this->createBackLinkExtension($router, $translator);
        $this->assertEquals(
            $expectedString,
            $extension->backLinkFilter($originalString, $id)
        );
    }

    public function shouldReplacePlaceholderWithReloadLinkAndLinkText()
    {
        $text = uniqid('text');
        $translatedText = uniqid('translatedText');
        $originalString = 'Before link {back_link|' . $text . '} After link';
        $expectedLink = '<a href="#" onclick="window.location.reload(true); return false;">'
                        . $translatedText
                        . '</a>';
        $expectedString = 'Before link ' . $expectedLink . ' After link';

        $router = $this->getMock('Symfony\Component\Routing\Router', [], [], '', false);

        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface', [], [], '', false);
        $translator->expects($this->once())
                   ->method('trans')
                   ->with($text)
                   ->will($this->returnValue($translatedText));

        $extension = $this->createBackLinkExtension($router, $translator);
        $this->assertEquals(
            $expectedString,
            $extension->backLinkFilter($originalString, null)
        );
    }

    /**
     * @test
     */
    public function shouldReplacePlaceholderWithProvidedUrlAndDefaultLinkText()
    {
        $id = uniqid('id');
        $url = uniqid('url');
        $originalString = 'Before link {back_link} After link';
        $expectedString = 'Before link <a href="' . $url . '">Back</a> After link';

        $router = $this->getMock('Symfony\Component\Routing\Router', [], [], '', false);
        $router->expects($this->once())
            ->method('generate')
            ->with('oro_embedded_form_submit', ['id' => $id])
            ->will($this->returnValue($url));

        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface', [], [], '', false);
        $translator->expects($this->once())
            ->method('trans')
            ->with('oro.embeddedform.back_link_default_text')
            ->will($this->returnValue('Back'));

        $extension = $this->createBackLinkExtension($router, $translator);
        $this->assertEquals(
            $expectedString,
            $extension->backLinkFilter($originalString, $id)
        );
    }

    /**
     * @test
     */
    public function shouldReturnOriginalStringWhenNoPlaceholderProvided()
    {
        $originalString = uniqid('any string');

        $extension = $this->createBackLinkExtension();
        $this->assertEquals(
            $originalString,
            $extension->backLinkFilter($originalString, uniqid('id'))
        );
    }

    /**
     * @param $router
     * @param $translator
     * @return BackLinkExtension
     */
    protected function createBackLinkExtension($router = null, $translator = null)
    {
        if (!$router) {
            $router = $this->getMock('Symfony\Component\Routing\Router', [], [], '', false);
        }
        if (!$translator) {
            $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface', [], [], '', false);
        }

        return new BackLinkExtension($router, $translator);
    }
}

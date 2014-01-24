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
    public function shouldReplacePlaceholderWithLink()
    {
        $id = uniqid('id');
        $url = uniqid('url');
        $text = uniqid('text');
        $originalString = 'Before link {back_link} After link';
        $expectedString = 'Before link <a href="' . $url . '">' . $text . '</a> After link';

        $router = $this->getMock('Symfony\Component\Routing\Router', [], [], '', false);
        $router->expects($this->once())
            ->method('generate')
            ->with('oro_embedded_form_submit', ['id' => $id])
            ->will($this->returnValue($url));

        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface', [], [], '', false);
        $translator->expects($this->once())
            ->method('trans')
            ->with('oro.embedded_form.back_link_text')
            ->will($this->returnValue($text));

        $extension = new BackLinkExtension($router, $translator);
        $this->assertEquals(
            $expectedString,
            $extension->backLinkFilter($originalString, $id)
        );
    }

    /**
     * @return BackLinkExtension
     */
    protected function createBackLinkExtension()
    {
        $router = $this->getMock('Symfony\Component\Routing\Router', [], [], '', false);
        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface', [], [], '', false);

        return new BackLinkExtension($router, $translator);
    }


}

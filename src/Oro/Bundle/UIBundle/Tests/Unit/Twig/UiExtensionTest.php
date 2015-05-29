<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Twig;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\UIBundle\Twig\UiExtension;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

class UiExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var \Oro\Bundle\UIBundle\Twig\UiExtension
     */
    protected $extension;

    protected function setUp()
    {
        $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $this->extension = new UiExtension($this->eventDispatcher);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_ui', $this->extension->getName());
    }

    public function testGetTokenParsers()
    {
        $parsers = $this->extension->getTokenParsers();
        $this->assertTrue($parsers[0] instanceof \Oro\Bundle\UIBundle\Twig\Parser\PlaceholderTokenParser);
    }

    public function getFunctions()
    {
        $functions = $this->extension->getFunctions();
        $this->assertCount(1, $functions);

        /** @var \Twig_SimpleFunction $scrollDataBefore */
        $scrollDataBefore = reset($functions);
        $this->assertInstanceOf('Twig_SimpleFunction', $scrollDataBefore);
        $this->assertEquals('oro_ui_scroll_data_before', $scrollDataBefore->getName());
        $this->assertEquals([$this, 'onScrollDataBefore'], $scrollDataBefore->getCallable());
        $this->assertTrue($scrollDataBefore->needsEnvironment());
    }

    public function testOnScrollDataBefore()
    {
        $environment = $this->getMock('\Twig_Environment');
        $pageIdentifier = 'test-page';
        $data = ['fields'];
        $alteredData = array_merge($data, ['altered']);
        $formView = $this->getMock('Symfony\Component\Form\FormView');

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                'oro_ui.scroll_data.before.' . $pageIdentifier,
                $this->isInstanceOf('Oro\Bundle\UIBundle\Event\BeforeListRenderEvent')
            )->willReturnCallback(
                function ($name, BeforeListRenderEvent $event) use ($environment, $data, $alteredData, $formView) {
                    $this->assertEquals($environment, $event->getEnvironment());
                    $this->assertEquals($data, $event->getData());
                    $this->assertEquals($formView, $event->getFormView());
                    $event->setData($alteredData);
                }
            );

        $this->assertEquals(
            $alteredData,
            $this->extension->scrollDataBefore($environment, $pageIdentifier, $data, $formView)
        );
    }
}

<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\EventListener;

use Oro\Bundle\UIBundle\EventListener\TemplateListener;
use Psr\Container\ContainerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Templating\DelegatingEngine;
use Symfony\Bundle\FrameworkBundle\Templating\TemplateReference;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\Templating\TemplateNameParserInterface;

class TemplateListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var GetResponseForControllerResultEvent|\PHPUnit\Framework\MockObject\MockObject */
    protected $event;

    /** @var ContainerInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $container;

    /** @var TemplateListener */
    protected $listener;

    protected function setUp()
    {
        $this->event = $this->createMock(GetResponseForControllerResultEvent::class);
        $this->container = $this->createMock(ContainerInterface::class);
        $this->listener = new TemplateListener($this->container);
    }


    public function testOnKernelViewNoContainer()
    {
        $request = Request::create('/test/url');
        $request->attributes = $this->createMock('Symfony\Component\HttpFoundation\ParameterBag');

        $request->attributes->expects($this->never())
            ->method('get')
            ->with('_template');

        $this->event->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($request));

        $this->listener->onKernelView($this->event);
    }

    /**
     * @dataProvider templateDataProvider
     * @param bool $containerExists
     * @param bool $widgetExists
     * @param mixed $inputTemplate
     * @param string $expectedTemplate
     * @param string $requestAttribute
     */
    public function testOnKernelViewWidgetTemplateExists(
        $containerExists,
        $widgetExists,
        $inputTemplate,
        $expectedTemplate,
        $requestAttribute
    ) {
        $request = Request::create('/test/url');
        $request->$requestAttribute->set('_widgetContainer', 'container');
        $request->attributes->set('_template', $inputTemplate);

        $this->event->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($request));

        $templating = $this->createMock(DelegatingEngine::class);
        $templating->expects($this->any())
            ->method('exists')
            ->willReturnMap([
                [(string)$this->createTemplateReference('container'), $containerExists],
                [(string)$this->createTemplateReference('widget'), $widgetExists],
                ['@TestBundle/Default/container/test.html.twig', $containerExists],
                ['@TestBundle/Default/widget/test.html.twig', $widgetExists],
            ]);

        $templateNameParser = $this->createMock(TemplateNameParserInterface::class);
        $templateNameParser->expects($this->any())
            ->method('parse')
            ->with('TestBundle:Default:test.html.twig')
            ->willReturn($this->createTemplateReference());

        $this->container->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['templating', $templating],
                ['templating.name_parser', $templateNameParser]
            ]);

        $this->listener->onKernelView($this->event);
        $this->assertEquals($expectedTemplate, $request->attributes->get('_template'));
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function templateDataProvider()
    {
        return [
            'container yes, widget yes' => [
                'containerExists' => true,
                'widgetExists' => true,
                'inputTemplate' => $this->createTemplateReference(),
                'expectedTemplate' => $this->createTemplateReference('container'),
                'requestAttribute' => 'query'
            ],
            'container yes, widget no' => [
                'containerExists' => true,
                'widgetExists' => false,
                'inputTemplate' => $this->createTemplateReference(),
                'expectedTemplate' => $this->createTemplateReference('container'),
                'requestAttribute' => 'query'
            ],
            'container no, widget yes' => [
                'containerExists' => false,
                'widgetExists' => true,
                'inputTemplate' => $this->createTemplateReference(),
                'expectedTemplate' => $this->createTemplateReference('widget'),
                'requestAttribute' => 'query'
            ],
            'container no, widget no' => [
                'containerExists' => false,
                'widgetExists' => false,
                'inputTemplate' => $this->createTemplateReference(),
                'expectedTemplate' => $this->createTemplateReference(),
                'requestAttribute' => 'query'
            ],
            'post container yes, widget yes' => [
                'containerExists' => true,
                'widgetExists' => true,
                'inputTemplate' => $this->createTemplateReference(),
                'expectedTemplate' => $this->createTemplateReference('container'),
                'requestAttribute' => 'request'
            ],
            'post container yes, widget no' => [
                'containerExists' => true,
                'widgetExists' => false,
                'inputTemplate' => $this->createTemplateReference(),
                'expectedTemplate' => $this->createTemplateReference('container'),
                'requestAttribute' => 'request'
            ],
            'post container no, widget yes' => [
                'containerExists' => false,
                'widgetExists' => true,
                'inputTemplate' => $this->createTemplateReference(),
                'expectedTemplate' => $this->createTemplateReference('widget'),
                'requestAttribute' => 'request'
            ],
            'post container no, widget no' => [
                'containerExists' => false,
                'widgetExists' => false,
                'inputTemplate' => $this->createTemplateReference(),
                'expectedTemplate' => $this->createTemplateReference(),
                'requestAttribute' => 'request'],
            'template name as string' => [
                'containerExists' => true,
                'widgetExists' => false,
                'inputTemplate' => 'TestBundle:Default:test.html.twig',
                'expectedTemplate' => $this->createTemplateReference('container'),
                'requestAttribute' => 'query'
            ],
            'template object' => [
                'containerExists' => true,
                'widgetExists' => false,
                'inputTemplate' => new Template(['template' => $this->createTemplateReference('container')]),
                'expectedTemplate' => new Template(['template' => $this->createTemplateReference('container')]),
                'requestAttribute' => 'query'
            ],
            'template object with template name as string' => [
                'containerExists' => true,
                'widgetExists' => false,
                'inputTemplate' => new Template(['template' => 'TestBundle:Default:test.html.twig']),
                'expectedTemplate' => new Template(['template' => $this->createTemplateReference('container')]),
                'requestAttribute' => 'query'
            ],
            'basic template reference' => [
                'containerExists' => true,
                'widgetExists' => false,
                'inputTemplate' => new \Symfony\Component\Templating\TemplateReference(
                    'TestBundle:Default:test.html.twig',
                    'twig'
                ),
                'expectedTemplate' => new \Symfony\Component\Templating\TemplateReference(
                    'TestBundle:Default:container/test.html.twig',
                    'twig'
                ),
                'requestAttribute' => 'query'
            ],
            'new format for basic template reference' => [
                'containerExists' => true,
                'widgetExists' => false,
                'inputTemplate' => new \Symfony\Component\Templating\TemplateReference(
                    '@TestBundle/Default/test.html.twig',
                    'twig'
                ),
                'expectedTemplate' => new \Symfony\Component\Templating\TemplateReference(
                    '@TestBundle/Default/container/test.html.twig',
                    'twig'
                ),
                'requestAttribute' => 'query'
            ],
        ];
    }

    /**
     * @param string|null $container
     * @return TemplateReference
     */
    protected function createTemplateReference($container = null): TemplateReference
    {
        return new TemplateReference(
            'TestBundle',
            'Default',
            ($container ? $container . '/'  : '') . 'test',
            'html',
            'twig'
        );
    }
}

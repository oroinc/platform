<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\EventListener;

use Doctrine\Inflector\Rules\English\InflectorFactory;
use Oro\Bundle\UIBundle\EventListener\TemplateListener;
use Psr\Container\ContainerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Templating\DelegatingEngine;
use Symfony\Bundle\FrameworkBundle\Templating\TemplateReference;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\Templating\TemplateNameParserInterface;
use Twig\Loader\FilesystemLoader;

class TemplateListenerTest extends \PHPUnit\Framework\TestCase
{
    private Request $request;

    private ViewEvent|\PHPUnit\Framework\MockObject\MockObject $event;

    private DelegatingEngine|\PHPUnit\Framework\MockObject\MockObject $templating;

    private TemplateNameParserInterface|\PHPUnit\Framework\MockObject\MockObject $templateNameParser;

    private TemplateListener $listener;

    protected function setUp(): void
    {
        $this->request = Request::create('/test/url');

        $this->event = $this->createMock(ViewEvent::class);
        $this->event->expects(self::any())
            ->method('getRequest')
            ->willReturn($this->request);

        $this->templating = $this->createMock(DelegatingEngine::class);
        $this->templateNameParser = $this->createMock(TemplateNameParserInterface::class);

        $loader = $this->createMock(FilesystemLoader::class);
        $loader->expects(self::any())
            ->method('getPaths')
            ->with('TestBundle')
            ->willReturn([realpath(__DIR__  . '/fixtures')]);

        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::any())
            ->method('get')
            ->willReturnMap([
                ['templating', $this->templating],
                ['templating.name_parser', $this->templateNameParser],
                ['twig.loader.native_filesystem', $loader]
            ]);

        $this->listener = new TemplateListener($container, (new InflectorFactory())->build());
    }

    /**
     * @dataProvider controllerDataProvider
     *
     * @param TemplateReference|string $inputTemplate
     * @param TemplateReference|string $expectedTemplate
     * @param TemplateReference|null $parsedTemplate
     */
    public function testOnKernelControllerPath($inputTemplate, $expectedTemplate, $parsedTemplate = null): void
    {
        $this->request->attributes->set('_template', $inputTemplate);

        $this->templateNameParser->expects($parsedTemplate ? self::once() : self::never())
            ->method('parse')
            ->with($inputTemplate)
            ->willReturn($parsedTemplate);

        $this->listener->onKernelView($this->event);
        self::assertEquals($expectedTemplate, $this->request->attributes->get('_template'));
    }

    public function controllerDataProvider(): array
    {
        return [
            'exist legacy controller' => [
                'inputTemplate' => $this->templateWithController('legacy-controller'),
                'expectedTemplate' => $this->templateWithController('LegacyController'),
            ],
            'exist legacy controller with legacy action (underscore)' => [
                'inputTemplate' => $this->templateWithController('legacy-controller', 'legacy_action'),
                'expectedTemplate' => $this->templateWithController('LegacyController', 'legacyAction'),
            ],
            'exist legacy controller with legacy action (hyphen)' => [
                'inputTemplate' => $this->templateWithController('legacy_controller', 'legacy-action'),
                'expectedTemplate' => $this->templateWithController('LegacyController', 'legacyAction'),
            ],
            'manual template reference' => [
                'inputTemplate' => $this->templateWithController('LegacyController', 'legacy_action'),
                'expectedTemplate' => $this->templateWithController('LegacyController', 'legacy_action'),
            ],
            'exist legacy controller for another reference object' => [
                'inputTemplate' => new \Symfony\Component\Templating\TemplateReference(
                    '@TestBundle/legacy-controller/test_action.html.twig',
                    'twig'
                ),
                'expectedTemplate' => new \Symfony\Component\Templating\TemplateReference(
                    '@TestBundle/LegacyController/testAction.html.twig',
                    'twig'
                ),
            ],
            'exist legacy controller in string' => [
                'inputTemplate' => '@TestBundle/legacy-controller/test.html.twig',
                'expectedTemplate' => $this->templateWithController('LegacyController'),
                'parsedTemplate' => $this->templateWithController('legacy-controller'),
            ],
            'exist legacy controller in formatted string' => [
                'inputTemplate' => 'TestBundle:legacy-controller:test.html.twig',
                'expectedTemplate' => $this->templateWithController('LegacyController'),
                'parsedTemplate' => $this->templateWithController('legacy-controller'),
            ],
            'exist new controller' => [
                'inputTemplate' => $this->templateWithController('new-controller'),
                'expectedTemplate' => $this->templateWithController('new-controller'),
            ],
            'exist new controller with action' => [
                'inputTemplate' => $this->templateWithController('new-controller', 'new_action'),
                'expectedTemplate' => $this->templateWithController('new-controller', 'new_action'),
            ],
            'exist new controller in string' => [
                'inputTemplate' => '@TestBundle/new-controller/test.html.twig',
                'expectedTemplate' => $this->templateWithController('new-controller'),
                'parsedTemplate' => $this->templateWithController('new-controller'),
            ],
            'both controllers' => [
                'inputTemplate' => $this->templateWithController('both-controller'),
                'expectedTemplate' => $this->templateWithController('both-controller'),
            ],
            'not exist controller' => [
                'inputTemplate' => $this->templateWithController('not-exist-controller'),
                'expectedTemplate' => $this->templateWithController('not-exist-controller'),
            ],
        ];
    }

    /**
     * @dataProvider templateDataProvider
     * @param bool $containerExists
     * @param bool $widgetExists
     * @param mixed $inputTemplate
     * @param TemplateReference|string $expectedTemplate
     * @param string $requestAttribute
     */
    public function testOnKernelViewWidgetTemplate(
        bool $containerExists,
        bool $widgetExists,
        $inputTemplate,
        $expectedTemplate,
        string $requestAttribute
    ): void {
        $this->request->$requestAttribute->set('_widgetContainer', 'container');
        $this->request->attributes->set('_template', $inputTemplate);

        $this->templating->expects(self::atLeastOnce())
            ->method('exists')
            ->willReturnMap([
                [(string)$this->templateWithContainer('container'), $containerExists],
                [(string)$this->templateWithContainer('widget'), $widgetExists],
                ['@TestBundle/Default/container/test.html.twig', $containerExists],
                ['@TestBundle/Default/widget/test.html.twig', $widgetExists],
            ]);

        $this->templateNameParser->expects(self::any())
            ->method('parse')
            ->with('TestBundle:Default:test.html.twig')
            ->willReturn($this->templateWithContainer());

        $this->listener->onKernelView($this->event);
        self::assertEquals($expectedTemplate, $this->request->attributes->get('_template'));
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function templateDataProvider(): array
    {
        return [
            'container yes, widget yes' => [
                'containerExists' => true,
                'widgetExists' => true,
                'inputTemplate' => $this->templateWithContainer(),
                'expectedTemplate' => $this->templateWithContainer('container'),
                'requestAttribute' => 'query'
            ],
            'container yes, widget no' => [
                'containerExists' => true,
                'widgetExists' => false,
                'inputTemplate' => $this->templateWithContainer(),
                'expectedTemplate' => $this->templateWithContainer('container'),
                'requestAttribute' => 'query'
            ],
            'container no, widget yes' => [
                'containerExists' => false,
                'widgetExists' => true,
                'inputTemplate' => $this->templateWithContainer(),
                'expectedTemplate' => $this->templateWithContainer('widget'),
                'requestAttribute' => 'query'
            ],
            'container no, widget no' => [
                'containerExists' => false,
                'widgetExists' => false,
                'inputTemplate' => $this->templateWithContainer(),
                'expectedTemplate' => $this->templateWithContainer(),
                'requestAttribute' => 'query'
            ],
            'post container yes, widget yes' => [
                'containerExists' => true,
                'widgetExists' => true,
                'inputTemplate' => $this->templateWithContainer(),
                'expectedTemplate' => $this->templateWithContainer('container'),
                'requestAttribute' => 'request'
            ],
            'post container yes, widget no' => [
                'containerExists' => true,
                'widgetExists' => false,
                'inputTemplate' => $this->templateWithContainer(),
                'expectedTemplate' => $this->templateWithContainer('container'),
                'requestAttribute' => 'request'
            ],
            'post container no, widget yes' => [
                'containerExists' => false,
                'widgetExists' => true,
                'inputTemplate' => $this->templateWithContainer(),
                'expectedTemplate' => $this->templateWithContainer('widget'),
                'requestAttribute' => 'request'
            ],
            'post container no, widget no' => [
                'containerExists' => false,
                'widgetExists' => false,
                'inputTemplate' => $this->templateWithContainer(),
                'expectedTemplate' => $this->templateWithContainer(),
                'requestAttribute' => 'request'],
            'template name as string' => [
                'containerExists' => true,
                'widgetExists' => false,
                'inputTemplate' => 'TestBundle:Default:test.html.twig',
                'expectedTemplate' => $this->templateWithContainer('container'),
                'requestAttribute' => 'query'
            ],
            'template object' => [
                'containerExists' => true,
                'widgetExists' => false,
                'inputTemplate' => new Template(['template' => $this->templateWithContainer('container')]),
                'expectedTemplate' => new Template(['template' => $this->templateWithContainer('container')]),
                'requestAttribute' => 'query'
            ],
            'template object with template name as string' => [
                'containerExists' => true,
                'widgetExists' => false,
                'inputTemplate' => new Template(['template' => 'TestBundle:Default:test.html.twig']),
                'expectedTemplate' => new Template(['template' => $this->templateWithContainer('container')]),
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
    private function templateWithContainer(?string $container = null): TemplateReference
    {
        return new TemplateReference(
            'TestBundle',
            'Default',
            ($container ? $container . '/'  : '') . 'test',
            'html',
            'twig'
        );
    }

    /**
     * @param string $controller
     * @param string $action
     * @return TemplateReference
     */
    private function templateWithController(string $controller, string $action = 'test'): TemplateReference
    {
        return new TemplateReference('TestBundle', $controller, $action, 'html', 'twig');
    }
}

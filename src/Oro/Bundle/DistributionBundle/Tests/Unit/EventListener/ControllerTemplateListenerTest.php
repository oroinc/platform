<?php

declare(strict_types=1);

namespace Oro\Bundle\DistributionBundle\Tests\Unit\EventListener;

use Oro\Bundle\DistributionBundle\EventListener\ControllerTemplateListener;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class ControllerTemplateListenerTest extends TestCase
{
    private Request $request;
    private \Closure $controller;
    private ControllerEvent $event;
    private ControllerTemplateListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $httpKernel = $this->createMock(HttpKernelInterface::class);
        $this->request = new Request();
        $this->controller = static fn () => new Response();

        $this->event = new ControllerEvent(
            $httpKernel,
            $this->controller,
            $this->request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->listener = new ControllerTemplateListener();
    }

    public function testOnKernelControllerWithoutTemplateOverride(): void
    {
        $this->listener->onKernelController($this->event);

        $template = $this->request->attributes->get('_template');
        self::assertNull($template);
    }

    public function testOnKernelControllerWithTemplateOverrideAsString(): void
    {
        $templateOverride = 'override_template.html.twig';
        $this->request->attributes->set('_template_override', $templateOverride);

        $this->listener->onKernelController($this->event);

        $template = $this->request->attributes->get('_template');
        self::assertInstanceOf(Template::class, $template);
        self::assertEquals($templateOverride, $template->template);
    }

    public function testOnKernelControllerWithTemplateOverrideAsTemplateInstance(): void
    {
        $templateOverride = new Template('override_template.html.twig');
        $this->request->attributes->set('_template_override', $templateOverride);

        $this->listener->onKernelController($this->event);

        $template = $this->request->attributes->get('_template');
        self::assertInstanceOf(Template::class, $template);
        self::assertSame($templateOverride, $template);
    }

    public function testSetAttributeName(): void
    {
        $this->listener->setAttributeName('custom_attribute');

        $templateOverride = 'override_template.html.twig';
        $this->request->attributes->set('custom_attribute', $templateOverride);

        $this->listener->onKernelController($this->event);

        $template = $this->request->attributes->get('_template');
        self::assertInstanceOf(Template::class, $template);
        self::assertEquals($templateOverride, $template->template);
    }
}

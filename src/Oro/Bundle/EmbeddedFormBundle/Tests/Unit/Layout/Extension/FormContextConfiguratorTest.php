<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Layout\Extension;

use Oro\Bundle\EmbeddedFormBundle\Layout\Extension\FormContextConfigurator;
use Oro\Bundle\EmbeddedFormBundle\Layout\Form\DependencyInjectionFormAccessor;
use Oro\Bundle\EmbeddedFormBundle\Layout\Form\FormAccessor;
use Oro\Bundle\EmbeddedFormBundle\Layout\Form\FormAccessorInterface;
use Oro\Bundle\EmbeddedFormBundle\Layout\Form\FormAction;
use Oro\Component\Layout\LayoutContext;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FormContextConfiguratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContainerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $container;

    /** @var FormContextConfigurator */
    private $contextConfigurator;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);

        $this->contextConfigurator = new FormContextConfigurator($this->container);
    }

    public function testCreateDIFormAccessor()
    {
        $context = new LayoutContext();

        $form = $this->createMock(FormInterface::class);
        $this->container->expects($this->once())
            ->method('get')
            ->with('form_service_id')
            ->willReturn($form);

        $context['form'] = 'form_service_id';
        $context['form_action'] = 'action';
        $context['form_method'] = 'method';
        $context['form_enctype'] = 'enctype';

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertInstanceOf(DependencyInjectionFormAccessor::class, $context['form']);

        /** @var DependencyInjectionFormAccessor $formAccessor */
        $formAccessor = $context['form'];
        $this->assertSame($form, $formAccessor->getForm());
        $this->assertEquals('action', $formAccessor->getAction()->getPath());
        $this->assertEquals('METHOD', $formAccessor->getMethod());
        $this->assertEquals('enctype', $formAccessor->getEnctype());
    }

    public function testCreateFormAccessor()
    {
        $context = new LayoutContext();

        $form = $this->createMock(FormInterface::class);

        $context['form'] = $form;
        $context['form_action'] = 'action';
        $context['form_method'] = 'method';
        $context['form_enctype'] = 'enctype';

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertInstanceOf(FormAccessor::class, $context['form']);

        /** @var FormAccessor $formAccessor */
        $formAccessor = $context['form'];
        $this->assertSame($form, $formAccessor->getForm());
        $this->assertEquals('action', $formAccessor->getAction()->getPath());
        $this->assertEquals('METHOD', $formAccessor->getMethod());
        $this->assertEquals('enctype', $formAccessor->getEnctype());
    }

    public function testCreateDIFormAccessorByRoute()
    {
        $context = new LayoutContext();

        $form = $this->createMock(FormInterface::class);
        $this->container->expects($this->once())
            ->method('get')
            ->with('form_service_id')
            ->willReturn($form);

        $context['form'] = 'form_service_id';
        $context['form_route_name'] = 'route';
        $context['form_route_parameters'] = ['foo' => 'bar'];
        $context['form_method'] = 'method';
        $context['form_enctype'] = 'enctype';

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertInstanceOf(DependencyInjectionFormAccessor::class, $context['form']);

        /** @var DependencyInjectionFormAccessor $formAccessor */
        $formAccessor = $context['form'];
        $this->assertSame($form, $formAccessor->getForm());
        $this->assertEquals('route', $formAccessor->getAction()->getRouteName());
        $this->assertEquals(['foo' => 'bar'], $formAccessor->getAction()->getRouteParameters());
        $this->assertEquals('METHOD', $formAccessor->getMethod());
        $this->assertEquals('enctype', $formAccessor->getEnctype());
    }

    public function testCreateFormAccessorByRoute()
    {
        $context = new LayoutContext();

        $form = $this->createMock(FormInterface::class);

        $context['form'] = $form;
        $context['form_route_name'] = 'route';
        $context['form_route_parameters'] = ['foo' => 'bar'];
        $context['form_method'] = 'method';
        $context['form_enctype'] = 'enctype';

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertInstanceOf(FormAccessor::class, $context['form']);

        /** @var FormAccessor $formAccessor */
        $formAccessor = $context['form'];
        $this->assertSame($form, $formAccessor->getForm());
        $this->assertEquals('route', $formAccessor->getAction()->getRouteName());
        $this->assertEquals(['foo' => 'bar'], $formAccessor->getAction()->getRouteParameters());
        $this->assertEquals('METHOD', $formAccessor->getMethod());
        $this->assertEquals('enctype', $formAccessor->getEnctype());
    }

    public function testShouldThrowExceptionIfInvalidFormType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'The "form" must be a string, "%s" or "%s", but "integer" given.',
            FormInterface::class,
            FormAccessorInterface::class
        ));

        $context = new LayoutContext();

        $context['form'] = 123;

        $this->contextConfigurator->configureContext($context);
        $context->resolve();
    }

    public function testFormIsOptional()
    {
        $context = new LayoutContext();

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertFalse(isset($context['form']));
    }

    public function testDoNothingIfFormAccessorIsAlreadySet()
    {
        $context = new LayoutContext();

        $formAccessor = $this->createMock(FormAccessorInterface::class);
        $context['form'] = $formAccessor;

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertSame($formAccessor, $context['form']);
    }

    public function testCreateFormAccessorByFormActionObject()
    {
        $context = new LayoutContext();

        $form = $this->createMock(FormInterface::class);
        $formAction = FormAction::createEmpty();

        $context['form'] = $form;
        $context['form_action'] = $formAction;
        $context['form_method'] = 'method';
        $context['form_enctype'] = 'enctype';

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertInstanceOf(FormAccessor::class, $context['form']);

        /** @var FormAccessor $formAccessor */
        $formAccessor = $context['form'];
        $this->assertSame($form, $formAccessor->getForm());
        $this->assertSame($formAction, $formAccessor->getAction());
        $this->assertEquals('METHOD', $formAccessor->getMethod());
        $this->assertEquals('enctype', $formAccessor->getEnctype());
    }

    public function testShouldThrowExceptionIfInvalidFormAction()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'The "form_action" must be a string or instance of "%s", but "integer" given.',
            FormAction::class
        ));

        $context = new LayoutContext();

        $context['form'] = $this->createMock(FormInterface::class);
        $context['form_action'] = 123;

        $this->contextConfigurator->configureContext($context);
        $context->resolve();
    }

    public function testShouldThrowExceptionIfInvalidFormRoute()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "form_route_name" must be a string, but "integer" given.');

        $context = new LayoutContext();

        $context['form'] = $this->createMock(FormInterface::class);
        $context['form_route_name'] = 123;

        $this->contextConfigurator->configureContext($context);
        $context->resolve();
    }

    public function testShouldThrowExceptionIfInvalidFormMethod()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "form_method" must be a string, but "integer" given.');

        $context = new LayoutContext();

        $context['form'] = $this->createMock(FormInterface::class);
        $context['form_method'] = 123;

        $this->contextConfigurator->configureContext($context);
        $context->resolve();
    }

    public function testShouldThrowExceptionIfInvalidFormEnctype()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "form_enctype" must be a string, but "integer" given.');

        $context = new LayoutContext();

        $context['form'] = $this->createMock(FormInterface::class);
        $context['form_enctype'] = 123;

        $this->contextConfigurator->configureContext($context);
        $context->resolve();
    }
}

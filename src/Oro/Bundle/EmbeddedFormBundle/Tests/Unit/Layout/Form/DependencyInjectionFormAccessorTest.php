<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Layout\Form;

use Oro\Bundle\EmbeddedFormBundle\Layout\Form\DependencyInjectionFormAccessor;
use Oro\Bundle\EmbeddedFormBundle\Layout\Form\FormAction;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormInterface;

class DependencyInjectionFormAccessorTest extends \PHPUnit\Framework\TestCase
{
    private const FORM_SERVICE_ID = 'test_service_id';

    /** @var ContainerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $container;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
    }

    public function testGetForm()
    {
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('getName')
            ->willReturn('form_name');

        $this->container->expects($this->once())
            ->method('get')
            ->with(self::FORM_SERVICE_ID)
            ->willReturn($form);

        $formAccessor = new DependencyInjectionFormAccessor($this->container, self::FORM_SERVICE_ID);
        $this->assertSame($form, $formAccessor->getForm());
        $this->assertEquals('form_name', $formAccessor->getName());
    }

    public function testToString()
    {
        $formAccessor = new DependencyInjectionFormAccessor($this->container, self::FORM_SERVICE_ID);
        $this->assertEquals('form_service_id:'.self::FORM_SERVICE_ID, $formAccessor->toString());
    }

    public function testGetHash()
    {
        $formAccessor = new DependencyInjectionFormAccessor(
            $this->container,
            self::FORM_SERVICE_ID,
            FormAction::createByRoute('test_route', ['foo' => 'bar']),
            'post',
            'multipart/form-data'
        );
        $this->assertEquals(
            self::FORM_SERVICE_ID . ';action_route:test_route;method:post;enctype:multipart/form-data',
            $formAccessor->getHash()
        );
    }

    public function testParamsInitializer()
    {
        $formAccessor = new DependencyInjectionFormAccessor($this->container, self::FORM_SERVICE_ID);

        $formAction = 'test_action';
        $formMethod = 'test_method';

        $form = $this->createMock(FormInterface::class);
        $formConfig = $this->createMock(FormConfigInterface::class);
        $formView = new FormView();

        $formView->vars['multipart'] = false;

        $this->container->expects($this->once())
            ->method('get')
            ->with(self::FORM_SERVICE_ID)
            ->willReturn($form);
        $form->expects($this->once())
            ->method('createView')
            ->willReturn($formView);
        $form->expects($this->any())
            ->method('getConfig')
            ->willReturn($formConfig);
        $formConfig->expects($this->once())
            ->method('getAction')
            ->willReturn($formAction);
        $formConfig->expects($this->once())
            ->method('getMethod')
            ->willReturn($formMethod);

        $this->assertEquals($formAction, $formAccessor->getAction()->getPath());
        $this->assertEquals(strtoupper($formMethod), $formAccessor->getMethod());
        $this->assertNull($formAccessor->getEnctype());
        $this->assertEquals('form_service_id:'.self::FORM_SERVICE_ID, $formAccessor->toString());
    }

    public function testParamsInitializerForMultipartForm()
    {
        $formAccessor = new DependencyInjectionFormAccessor($this->container, self::FORM_SERVICE_ID);

        $formAction = 'test_action';
        $formMethod = 'test_method';

        $form = $this->createMock(FormInterface::class);
        $formConfig = $this->createMock(FormConfigInterface::class);
        $formView = new FormView();

        $formView->vars['multipart'] = true;

        $this->container->expects($this->once())
            ->method('get')
            ->with(self::FORM_SERVICE_ID)
            ->willReturn($form);
        $form->expects($this->once())
            ->method('createView')
            ->willReturn($formView);
        $form->expects($this->any())
            ->method('getConfig')
            ->willReturn($formConfig);
        $formConfig->expects($this->once())
            ->method('getAction')
            ->willReturn($formAction);
        $formConfig->expects($this->once())
            ->method('getMethod')
            ->willReturn($formMethod);

        $this->assertEquals($formAction, $formAccessor->getAction()->getPath());
        $this->assertEquals(strtoupper($formMethod), $formAccessor->getMethod());
        $this->assertEquals('multipart/form-data', $formAccessor->getEnctype());
        $this->assertEquals('form_service_id:'.self::FORM_SERVICE_ID, $formAccessor->toString());
    }

    public function testGetView()
    {
        // form
        //   field1
        //     field2
        $formView = new FormView();
        $formView->vars['id'] = self::FORM_SERVICE_ID;
        $field1View = new FormView($formView);
        $formView->children['field1'] = $field1View;
        $field2View = new FormView($field1View);
        $field1View->children['field2'] = $field2View;

        $form = $this->createMock(FormInterface::class);
        $this->container->expects($this->once())
            ->method('get')
            ->with(self::FORM_SERVICE_ID)
            ->willReturn($form);
        $form->expects($this->once())
            ->method('createView')
            ->willReturn($formView);

        $formAccessor = new DependencyInjectionFormAccessor($this->container, self::FORM_SERVICE_ID);
        $this->assertSame($formView, $formAccessor->getView());
        $this->assertSame($field1View, $formAccessor->getView('field1'));
        $this->assertSame($field2View, $formAccessor->getView('field1.field2'));
        $this->assertSame($formView->vars['id'], $formAccessor->getId());
    }

    public function testProcessedFields()
    {
        $formAccessor = new DependencyInjectionFormAccessor($this->container, self::FORM_SERVICE_ID);

        $this->assertNull($formAccessor->getProcessedFields());

        $processedFields = ['field' => 'block_id'];
        $formAccessor->setProcessedFields($processedFields);
        $this->assertSame($processedFields, $formAccessor->getProcessedFields());
    }

    public function testSetFormData()
    {
        $data = ['test'];

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('setData')
            ->with($data);
        $form->expects($this->once())
            ->method('getData')
            ->willReturn($data);

        $this->container->expects($this->once())
            ->method('get')
            ->with(self::FORM_SERVICE_ID)
            ->willReturn($form);

        $formAccessor = new DependencyInjectionFormAccessor($this->container, self::FORM_SERVICE_ID);

        $formAccessor->setFormData($data);
        $this->assertEquals($data, $formAccessor->getForm()->getData());
    }

    public function testSetters()
    {
        $formConfig = $this->createMock(FormConfigInterface::class);
        $formView = new FormView();
        $formView->vars['multipart'] = true;

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->any())
            ->method('getConfig')
            ->willReturn($formConfig);
        $form->expects($this->once())
            ->method('createView')
            ->willReturn($formView);

        $this->container->expects($this->once())
            ->method('get')
            ->with(self::FORM_SERVICE_ID)
            ->willReturn($form);

        $formAccessor = new DependencyInjectionFormAccessor($this->container, self::FORM_SERVICE_ID);

        $action = FormAction::createByRoute('test_route', ['foo' => 'bar']);
        $formAccessor->setAction($action);
        $this->assertEquals($action, $formAccessor->getAction());

        $formAccessor->setActionRoute('test_route', []);
        $this->assertEquals(FormAction::createByRoute('test_route', []), $formAccessor->getAction());

        $formAccessor->setMethod('post');
        $this->assertEquals('post', $formAccessor->getMethod());

        $formAccessor->setEnctype('multipart/form-data');
        $this->assertEquals('multipart/form-data', $formAccessor->getEnctype());
    }
}

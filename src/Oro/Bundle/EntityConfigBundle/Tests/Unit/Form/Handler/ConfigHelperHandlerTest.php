<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Form\Handler;

use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Form\Handler\ConfigHelperHandler;
use Oro\Component\Testing\Unit\EntityTrait;

use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\Test\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class ConfigHelperHandlerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var FormFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $formFactory;

    /**
     * @var FormInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $form;

    /**
     * @var Request|\PHPUnit_Framework_MockObject_MockObject
     */
    private $request;

    /**
     * @var ConfigHelperHandler
     */
    private $handler;

    protected function setUp()
    {
        $this->formFactory = $this->getMockBuilder(FormFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->form = $this->createMock(FormInterface::class);
        $this->request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = new ConfigHelperHandler($this->formFactory);
    }

    public function testCreateFirstStepFieldForm()
    {
        $entityClassName = 'someClassName';
        $entityConfigModel = $this->getEntity(EntityConfigModel::class, ['className' => $entityClassName]);
        /** @var FieldConfigModel $fieldConfigModel */
        $fieldConfigModel = $this->getEntity(FieldConfigModel::class, ['entity' => $entityConfigModel]);

        $this->formFactory
            ->expects($this->once())
            ->method('create')
            ->with(
                'oro_entity_extend_field_type',
                $fieldConfigModel,
                ['class_name' => $entityClassName]
            )
            ->willReturn($this->form);

        $this->assertEquals($this->form, $this->handler->createFirstStepFieldForm($fieldConfigModel));
    }

    public function testCreateSecondStepFieldForm()
    {
        /** @var FieldConfigModel $fieldConfigModel */
        $fieldConfigModel = $this->getEntity(FieldConfigModel::class);


        $this->formFactory
            ->expects($this->once())
            ->method('create')
            ->with(
                'oro_entity_config_type',
                null,
                ['config_model' => $fieldConfigModel]
            )
            ->willReturn($this->form);

        $this->assertEquals($this->form, $this->handler->createSecondStepFieldForm($fieldConfigModel));
    }

    public function testIsFormValidAfterSubmitWhenMethodIsNotPost()
    {
        $this->request
            ->expects($this->once())
            ->method('isMethod')
            ->with('POST')
            ->willReturn(false);

        $this->form
            ->expects($this->never())
            ->method('submit');

        $this->assertFalse($this->handler->isFormValidAfterSubmit($this->request, $this->form));
    }

    public function testIsFormValidAfterSubmitWhenMethodIsPostAndFormIsNotValid()
    {
        $this->request
            ->expects($this->once())
            ->method('isMethod')
            ->with('POST')
            ->willReturn(true);

        $this->form
            ->expects($this->once())
            ->method('submit')
            ->with($this->request);

        $this->form
            ->expects($this->once())
            ->method('isValid')
            ->willReturn(false);

        $this->assertFalse($this->handler->isFormValidAfterSubmit($this->request, $this->form));
    }

    public function testIsFormValidAfterSubmitWhenMethodIsPostAndFormIsValid()
    {
        $this->request
            ->expects($this->once())
            ->method('isMethod')
            ->with('POST')
            ->willReturn(true);

        $this->form
            ->expects($this->once())
            ->method('submit')
            ->with($this->request);

        $this->form
            ->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->assertTrue($this->handler->isFormValidAfterSubmit($this->request, $this->form));
    }
}

<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Form\Handler;

use Oro\Bundle\EntityConfigBundle\Config\ConfigHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Form\Handler\ConfigHelperHandler;
use Oro\Bundle\UIBundle\Route\Router;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;

class ConfigHelperHandlerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var FormFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $formFactory;

    /**
     * @var Session|\PHPUnit_Framework_MockObject_MockObject
     */
    private $session;

    /**
     * @var Router|\PHPUnit_Framework_MockObject_MockObject
     */
    private $router;

    /**
     * @var ConfigHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configHelper;

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

        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->router = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configHelper = $this->getMockBuilder(ConfigHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->form = $this->createMock(FormInterface::class);
        $this->request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = new ConfigHelperHandler(
            $this->formFactory,
            $this->session,
            $this->router,
            $this->configHelper
        );
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

    public function testShowSuccessMessageAndRedirect()
    {
        $successMessage = 'Success message';
        $flashBag = $this->createMock(FlashBagInterface::class);

        $flashBag
            ->expects($this->once())
            ->method('add')
            ->with('success', $successMessage);

        $this->session
            ->expects($this->once())
            ->method('getFlashBag')
            ->willReturn($flashBag);

        /** @var FieldConfigModel $fieldConfigModel */
        $fieldConfigModel = $this->getEntity(FieldConfigModel::class);
        $this->router
            ->expects($this->once())
            ->method('redirect')
            ->with($fieldConfigModel);

        $this->handler->showSuccessMessageAndRedirect($fieldConfigModel, $successMessage);
    }

    public function testConstructConfigResponse()
    {
        /** @var FieldConfigModel $fieldConfigModel */
        $fieldConfigModel = $this->getEntity(FieldConfigModel::class);

        $formView = new FormView();
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form
            ->expects($this->once())
            ->method('createView')
            ->willReturn($formView);

        $formAction = 'someAction';

        $entityConfig = $this->createMock(ConfigInterface::class);
        $this->configHelper
            ->expects($this->once())
            ->method('getEntityConfigByField')
            ->with($fieldConfigModel, 'entity')
            ->willReturn($entityConfig);

        $fieldConfig = $this->createMock(ConfigInterface::class);
        $this->configHelper
            ->expects($this->once())
            ->method('getFieldConfig')
            ->with($fieldConfigModel, 'entity')
            ->willReturn($fieldConfig);

        $modules = ['somemodule'];
        $this->configHelper
            ->expects($this->once())
            ->method('getExtendRequireJsModules')
            ->willReturn($modules);

        $nonExtendedEntities = ['first', 'second'];
        $this->configHelper
            ->expects($this->once())
            ->method('getNonExtendedEntitiesClasses')
            ->willReturn($nonExtendedEntities);

        $expectedResponse = [
            'entity_config' => $entityConfig,
            'field_config' => $fieldConfig,
            'field' => $fieldConfigModel,
            'form' => $formView,
            'formAction' => $formAction,
            'require_js' => $modules,
            'non_extended_entities_classes' => $nonExtendedEntities,
        ];

        $this->assertEquals(
            $expectedResponse,
            $this->handler->constructConfigResponse($fieldConfigModel, $form, $formAction)
        );
    }

    public function testRedirect()
    {
        /** @var FieldConfigModel $fieldConfigModel */
        $fieldConfigModel = $this->getEntity(FieldConfigModel::class);

        $redirectResponse = new RedirectResponse('some_url');
        $this->router
            ->expects($this->once())
            ->method('redirect')
            ->with($fieldConfigModel)
            ->willReturn($redirectResponse);

        $this->assertEquals($redirectResponse, $this->handler->redirect($fieldConfigModel));
    }
}

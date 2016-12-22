<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Form\Handler;

use Oro\Bundle\EntityConfigBundle\Config\ConfigHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Form\Handler\ConfigFieldHandler;
use Oro\Bundle\EntityConfigBundle\Form\Handler\ConfigHelperHandler;
use Oro\Bundle\FormBundle\Model\UpdateHandler;
use Oro\Component\Testing\Unit\EntityTrait;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ConfigFieldHandlerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const SAMPLE_FORM_ACTION = '/entity_config/create';
    const SAMPLE_SUCCESS_MESSAGE = 'Entity config was successfully saved';

    /**
     * @var ConfigHelperHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configHelperHandler;

    /**
     * @var ConfigHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configHelper;

    /**
     * @var UpdateHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    private $updateHandler;

    /**
     * @var RequestStack|\PHPUnit_Framework_MockObject_MockObject
     */
    private $requestStack;

    /**
     * @var FieldConfigModel|\PHPUnit_Framework_MockObject_MockObject
     */
    private $fieldConfigModel;

    /**
     * @var ConfigFieldHandler
     */
    private $handler;

    protected function setUp()
    {
        $this->configHelperHandler = $this->getMockBuilder(ConfigHelperHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configHelper = $this->getMockBuilder(ConfigHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->updateHandler = $this->getMockBuilder(UpdateHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestStack = $this->getMockBuilder(RequestStack::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->fieldConfigModel = $this->getEntity(FieldConfigModel::class, ['id' => 777]);

        $this->handler = new ConfigFieldHandler(
            $this->configHelperHandler,
            $this->configHelper,
            $this->updateHandler,
            $this->requestStack
        );
    }

    /**
     * @return Form|\PHPUnit_Framework_MockObject_MockObject
     */
    private function expectsCreateForm()
    {
        $form = $this->getMockBuilder(Form::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configHelperHandler
            ->expects($this->once())
            ->method('createSecondStepFieldForm')
            ->with($this->fieldConfigModel)
            ->willReturn($form);

        return $form;
    }

    /**
     * @return Request|\PHPUnit_Framework_MockObject_MockObject
     */
    private function expectsGetCurrentRequest()
    {
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        return $request;
    }

    public function testHandleUpdate()
    {
        $form = $this->expectsCreateForm();
        $formView = $this->getMockBuilder(FormView::class)
            ->disableOriginalConstructor()
            ->getMock();

        $form
            ->expects($this->any())
            ->method('createView')
            ->willReturn($formView);

        $entityConfig = $this->createMock(ConfigInterface::class);
        $this->configHelper
            ->expects($this->once())
            ->method('getEntityConfigByField')
            ->with($this->fieldConfigModel, 'entity')
            ->willReturn($entityConfig);

        $fieldConfig = $this->createMock(ConfigInterface::class);
        $this->configHelper
            ->expects($this->once())
            ->method('getFieldConfig')
            ->with($this->fieldConfigModel, 'entity')
            ->willReturn($fieldConfig);

        $jsModules = ['somemodule1', 'somemodule2'];
        $this->configHelper
            ->expects($this->once())
            ->method('getExtendRequireJsModules')
            ->willReturn($jsModules);

        $response = [];
        $arrayResponse = [
            'someKey' => 'someValue'
        ];

        $this->assertInstanceOf(FormInterface::class, $form);

        $this->updateHandler
            ->expects($this->once())
            ->method('update')
            ->with(
                $this->fieldConfigModel,
                $form,
                self::SAMPLE_SUCCESS_MESSAGE,
                $this->handler,
                $this->callback(function ($closure) use ($form, &$response) {
                    $this->assertInstanceOf(\Closure::class, $closure);
                    $response = $closure($this->fieldConfigModel, $form);

                    return true;
                })
            )
            ->willReturn($arrayResponse);

        $this->assertEquals(
            $arrayResponse,
            $this->handler->handleUpdate(
                $this->fieldConfigModel,
                self::SAMPLE_FORM_ACTION,
                self::SAMPLE_SUCCESS_MESSAGE
            )
        );

        $expectedResponse = [
            'entity_config' => $entityConfig,
            'field_config'  => $fieldConfig,
            'field'         => $this->fieldConfigModel,
            'form'          => $formView,
            'formAction'    => self::SAMPLE_FORM_ACTION,
            'require_js'    => $jsModules
        ];

        $this->assertEquals($expectedResponse, $response);
    }

    /**
     * @return array
     */
    public function processDataProvider()
    {
        return [
            'form is valid' => [
                'expectedValue' => true
            ],
            'form is not valid' => [
                'expectedValue' => false
            ]
        ];
    }

    /**
     * @dataProvider processDataProvider
     *
     * @var bool $expectedValue
     */
    public function testProcess($expectedValue)
    {
        $form = $this->expectsCreateForm();
        $request = $this->expectsGetCurrentRequest();

        $this->configHelperHandler
            ->expects($this->once())
            ->method('isFormValidAfterSubmit')
            ->with($request, $form)
            ->willReturn($expectedValue);

        $this->assertEquals($expectedValue, $this->handler->process($this->fieldConfigModel));
    }
}

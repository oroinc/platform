<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Form\Handler;

use Oro\Bundle\EntityConfigBundle\Config\ConfigHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Form\Handler\ConfigHelperHandler;
use Oro\Bundle\EntityConfigBundle\Form\Handler\CreateUpdateConfigFieldHandler;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Form\Type\FieldType;
use Oro\Bundle\EntityExtendBundle\Form\Util\FieldSessionStorage;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class CreateUpdateConfigFieldHandlerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    const FORM_ACTION = '/form/action';
    const ENTITY_CONFIG_MODEL_ID = 789;
    const FIELD_NAME = 'fieldName';
    const FIELD_TYPE = 'enum';
    const CREATE_ACTION_REDIRECT_URL = 'create_redirect_action_url';
    const SUCCESS_MESSAGE = 'success_message';
    const CLASS_NAME = 'class_name';

    /**
     * @var ConfigHelperHandler|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configHelperHandler;

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configManager;

    /**
     * @var ConfigHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configHelper;

    /**
     * @var FieldSessionStorage|\PHPUnit\Framework\MockObject\MockObject
     */
    private $sessionStorage;

    /**
     * @var CreateUpdateConfigFieldHandler
     */
    private $handler;

    protected function setUp()
    {
        $this->configHelperHandler = $this->getMockBuilder(ConfigHelperHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configHelper = $this->getMockBuilder(ConfigHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sessionStorage = $this->getMockBuilder(FieldSessionStorage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = new CreateUpdateConfigFieldHandler(
            $this->configHelperHandler,
            $this->configManager,
            $this->configHelper,
            $this->sessionStorage
        );
    }

    /**
     * @return Request|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createRequest()
    {
        return $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param array $properties
     * @return EntityConfigModel
     */
    private function createEntityConfigModel(array $properties = [])
    {
        $properties = array_merge(
            [
                'id' => self::ENTITY_CONFIG_MODEL_ID,
                'className' => self::CLASS_NAME
            ],
            $properties
        );

        /** @var EntityConfigModel $entityConfigModel */
        $entityConfigModel = $this->getEntity(EntityConfigModel::class, $properties);

        return $entityConfigModel;
    }

    /**
     * @param array $properties
     * @return FieldConfigModel
     */
    private function createFieldConfigModel(array $properties = [])
    {
        $properties = array_merge(
            ['type' => self::FIELD_TYPE],
            $properties
        );

        return $this->getEntity(FieldConfigModel::class, $properties);
    }

    /**
     * @param FormInterface|\PHPUnit\Framework\MockObject\MockObject $form
     * @param EntityConfigModel $entityConfigModel
     * @param FieldConfigModel $fieldConfigModel
     * @param Request|\PHPUnit\Framework\MockObject\MockObject $request
     */
    private function assertArrayResponseReturned(
        FormInterface $form,
        EntityConfigModel $entityConfigModel,
        FieldConfigModel $fieldConfigModel,
        Request $request
    ) {
        $formView = $this->createMock(FormView::class);

        $form
            ->expects($this->once())
            ->method('createView')
            ->willReturn($formView);

        $entityConfig = $this->createMock(ConfigInterface::class);
        $this->configHelper
            ->expects($this->once())
            ->method('getEntityConfig')
            ->with($entityConfigModel, 'entity')
            ->willReturn($entityConfig);

        $expectedResponse = [
            'form' => $formView,
            'formAction' => self::FORM_ACTION,
            'entity_id' => self::ENTITY_CONFIG_MODEL_ID,
            'entity_config' => $entityConfig
        ];

        $response = $this->handler->handleCreate($request, $fieldConfigModel, self::FORM_ACTION);
        $this->assertEquals($expectedResponse, $response);
    }

    public function testHandleCreateWhenFormIsNotValidAfterSubmit()
    {
        $form = $this->createMock(FormInterface::class);
        $request = $this->createRequest();

        $this->configHelperHandler
            ->expects($this->once())
            ->method('isFormValidAfterSubmit')
            ->with($request, $form)
            ->willReturn(false);

        $entityConfigModel = $this->createEntityConfigModel();
        $fieldConfigModel = $this->createFieldConfigModel(['entity' => $entityConfigModel]);

        $this->configHelperHandler
            ->expects($this->once())
            ->method('createFirstStepFieldForm')
            ->with($fieldConfigModel)
            ->willReturn($form);

        $this->assertArrayResponseReturned($form, $entityConfigModel, $fieldConfigModel, $request);
    }

    /**
     * @return array
     */
    public function fieldNamesDataProvider()
    {
        return [
            [
                'originalFieldNames' => [],
                'fieldName' => 'fieldName',
                'expectedFieldName' => 'fieldName'
            ],
            [
                'originalFieldNames' => [
                    'fieldName' => 'expectedFieldName'
                ],
                'fieldName' => 'fieldName',
                'expectedFieldName' => 'expectedFieldName'
            ],
        ];
    }

    /**
     * @dataProvider fieldNamesDataProvider
     *
     * @param array $originalFieldNames
     * @param string $fieldName
     * @param string $expectedFieldName
     */
    public function testHandleCreateWhenRedirectResponseIsReturned($originalFieldNames, $fieldName, $expectedFieldName)
    {
        $form = $this->createMock(FormInterface::class);
        $request = $this->createRequest();

        $this->configHelperHandler
            ->expects($this->once())
            ->method('isFormValidAfterSubmit')
            ->with($request, $form)
            ->willReturn(true);

        $formConfig = $this->createMock(FormConfigInterface::class);

        $formConfig
            ->expects($this->once())
            ->method('getAttribute')
            ->with(FieldType::ORIGINAL_FIELD_NAMES_ATTRIBUTE)
            ->willReturn($originalFieldNames);

        $form
            ->expects($this->once())
            ->method('getConfig')
            ->willReturn($formConfig);

        $entityConfigModel = $this->createEntityConfigModel();
        $fieldConfigModel = $this->createFieldConfigModel([
            'entity' => $entityConfigModel,
            'fieldName' => $fieldName
        ]);

        $this->configHelperHandler
            ->expects($this->once())
            ->method('createFirstStepFieldForm')
            ->with($fieldConfigModel)
            ->willReturn($form);

        $this->sessionStorage
            ->expects($this->once())
            ->method('saveFieldInfo')
            ->with($entityConfigModel, $expectedFieldName, self::FIELD_TYPE);

        $redirectResponse = $this->getMockBuilder(RedirectResponse::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configHelperHandler
            ->expects($this->once())
            ->method('redirect')
            ->with($entityConfigModel)
            ->willReturn($redirectResponse);

        $this->assertEquals(
            $redirectResponse,
            $this->handler->handleCreate($request, $fieldConfigModel, self::FORM_ACTION)
        );
    }

    /**
     * @param EntityConfigModel $entityConfigModel
     * @return array
     */
    private function expectsCreateConfigFieldModelAndUpdateItWithFieldOptions(EntityConfigModel $entityConfigModel)
    {
        $this->sessionStorage
            ->expects($this->once())
            ->method('hasFieldInfo')
            ->with($entityConfigModel)
            ->willReturn(true);

        $this->sessionStorage
            ->expects($this->once())
            ->method('getFieldInfo')
            ->with($entityConfigModel)
            ->willReturn([self::FIELD_NAME, self::FIELD_TYPE]);

        $extendEntityConfig = $this->createMock(ConfigInterface::class);
        $this->configHelper
            ->expects($this->once())
            ->method('getEntityConfig')
            ->with($entityConfigModel, 'extend')
            ->willReturn($extendEntityConfig);

        $additionalFieldOptions = [];
        $fieldOptions = [
            'extend' => [
                'is_extend' => true,
                'origin' => ExtendScope::ORIGIN_CUSTOM,
                'owner' => ExtendScope::OWNER_CUSTOM,
                'state' => ExtendScope::STATE_NEW,
                'bidirectional' => true,
            ]
        ];

        $this->configHelper
            ->expects($this->once())
            ->method('createFieldOptions')
            ->with($extendEntityConfig, self::FIELD_TYPE, $additionalFieldOptions)
            ->willReturn([self::FIELD_TYPE, $fieldOptions]);

        $newFieldModel = $this->createFieldConfigModel();
        $this->configManager
            ->expects($this->once())
            ->method('createConfigFieldModel')
            ->with(self::CLASS_NAME, self::FIELD_NAME, self::FIELD_TYPE)
            ->willReturn($newFieldModel);

        $this->configHelper
            ->expects($this->once())
            ->method('updateFieldConfigs')
            ->with($newFieldModel, $fieldOptions);

        return [$newFieldModel, $extendEntityConfig];
    }

    public function testHandleFieldSaveWhenRedirectedToCreateAction()
    {
        $request = $this->createRequest();
        $entityConfigModel = $this->createEntityConfigModel();

        $this->sessionStorage
            ->expects($this->once())
            ->method('hasFieldInfo')
            ->with($entityConfigModel)
            ->willReturn(false);

        $redirectResponse = $this->getMockBuilder(RedirectResponse::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configHelperHandler
            ->expects($this->once())
            ->method('redirect')
            ->with(self::CREATE_ACTION_REDIRECT_URL)
            ->willReturn($redirectResponse);

        $response = $this->handler->handleFieldSave(
            $request,
            $entityConfigModel,
            self::CREATE_ACTION_REDIRECT_URL,
            self::FORM_ACTION,
            self::SUCCESS_MESSAGE,
            []
        );

        $this->assertEquals($redirectResponse, $response);
    }

    /**
     * @param Request|\PHPUnit\Framework\MockObject\MockObject $request
     * @param FormInterface|\PHPUnit\Framework\MockObject\MockObject $form
     * @param EntityConfigModel $entityConfigModel
     * @param FieldConfigModel $newFieldModel
     */
    private function assertFieldSaveArrayResponseReturned(
        Request $request,
        FormInterface $form,
        EntityConfigModel $entityConfigModel,
        FieldConfigModel $newFieldModel
    ) {
        $expectedResponse = [
            'someKey' => 'someValue'
        ];

        $this->configHelperHandler
            ->expects($this->once())
            ->method('constructConfigResponse')
            ->with($newFieldModel, $form, self::FORM_ACTION)
            ->willReturn($expectedResponse);

        $response = $this->handler->handleFieldSave(
            $request,
            $entityConfigModel,
            self::CREATE_ACTION_REDIRECT_URL,
            self::FORM_ACTION,
            self::SUCCESS_MESSAGE,
            []
        );

        $this->assertEquals($expectedResponse, $response);
    }

    public function testHandleFieldSaveWhenFormIsNotValidAfterSubmit()
    {
        $entityConfigModel = $this->createEntityConfigModel();

        list($newFieldModel, $extendEntityConfig) = $this->expectsCreateConfigFieldModelAndUpdateItWithFieldOptions(
            $entityConfigModel
        );

        $request = $this->createRequest();
        $form = $this->createMock(FormInterface::class);

        $this->configHelperHandler
            ->expects($this->once())
            ->method('createSecondStepFieldForm')
            ->with($newFieldModel)
            ->willReturn($form);

        $this->configHelperHandler
            ->expects($this->once())
            ->method('isFormValidAfterSubmit')
            ->with($request, $form)
            ->willReturn(false);

        $this->configHelper
            ->expects($this->once())
            ->method('getEntityConfig')
            ->with($entityConfigModel, 'extend')
            ->willReturn($extendEntityConfig);

        $this->assertFieldSaveArrayResponseReturned($request, $form, $entityConfigModel, $newFieldModel);
    }

    /**
     * @param FieldConfigModel $newFieldModel
     * @param ConfigInterface|\PHPUnit\Framework\MockObject\MockObject $extendEntityConfig
     * @return RedirectResponse|\PHPUnit\Framework\MockObject\MockObject
     */
    private function expectsConfigSavingAndRedirect(
        FieldConfigModel $newFieldModel,
        ConfigInterface $extendEntityConfig
    ) {
        $extendEntityConfig ->expects($this->once())
            ->method('set')
            ->with('upgradeable', true);

        $this->configManager
            ->expects($this->once())
            ->method('persist')
            ->with($extendEntityConfig);

        $this->configManager
            ->expects($this->once())
            ->method('flush');

        $redirectResponse = new RedirectResponse('some_redirect_url');
        $this->configHelperHandler
            ->expects($this->once())
            ->method('showSuccessMessageAndRedirect')
            ->with($newFieldModel, self::SUCCESS_MESSAGE)
            ->willReturn($redirectResponse);

        return $redirectResponse;
    }

    public function testHandleFieldSaveWhenMethodIsPostAndFormIsValid()
    {
        $entityConfigModel = $this->createEntityConfigModel();

        list($newFieldModel, $extendEntityConfig) = $this->expectsCreateConfigFieldModelAndUpdateItWithFieldOptions(
            $entityConfigModel
        );

        $request = $this->createRequest();
        $form = $this->createMock(FormInterface::class);

        $this->configHelperHandler
            ->expects($this->once())
            ->method('isFormValidAfterSubmit')
            ->with($request, $form)
            ->willReturn(true);

        $this->configHelper
            ->expects($this->once())
            ->method('getEntityConfig')
            ->with($entityConfigModel, 'extend')
            ->willReturn($extendEntityConfig);

        $this->configHelperHandler
            ->expects($this->once())
            ->method('createSecondStepFieldForm')
            ->with($newFieldModel)
            ->willReturn($form);

        $this->configHelperHandler
            ->expects($this->once())
            ->method('showClearCacheMessage')
            ->willReturn($this->configHelperHandler);

        $redirectResponse = $this->expectsConfigSavingAndRedirect($newFieldModel, $extendEntityConfig);

        $response = $this->handler->handleFieldSave(
            $request,
            $entityConfigModel,
            self::CREATE_ACTION_REDIRECT_URL,
            self::FORM_ACTION,
            self::SUCCESS_MESSAGE,
            []
        );

        $this->assertEquals($redirectResponse->getTargetUrl(), $response->getTargetUrl());
        $this->assertEquals($redirectResponse->getStatusCode(), $response->getStatusCode());
    }
}

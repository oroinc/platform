<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Form\Handler;

use Oro\Bundle\EntityConfigBundle\Config\ConfigHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Event\AfterRemoveFieldEvent;
use Oro\Bundle\EntityConfigBundle\Event\Events;
use Oro\Bundle\EntityConfigBundle\Form\Handler\RemoveRestoreConfigFieldHandler;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Validator\FieldNameValidationHelper;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivityTarget;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;

class RemoveRestoreConfigFieldHandlerTest extends \PHPUnit_Framework_TestCase
{
    const SAMPLE_ERROR_MESSAGE = 'Restore error message';
    const SAMPLE_SUCCESS_MESSAGE = 'Entity config was successfully saved';
    const SAMPLE_VALIDATION_ERROR_MESSAGE1 = 'Validation error 1';
    const SAMPLE_VALIDATION_ERROR_MESSAGE2 = 'Validation error 2';

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configManager;

    /**
     * @var FieldNameValidationHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $validationHelper;

    /**
     * @var ConfigHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configHelper;

    /**
     * @var Session|\PHPUnit_Framework_MockObject_MockObject
     */
    private $session;

    /**
     * @var FieldConfigModel|\PHPUnit_Framework_MockObject_MockObject
     */
    private $fieldConfigModel;

    /**
     * @var RemoveRestoreConfigFieldHandler
     */
    private $handler;

    /**
     * @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $eventDispatcher;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->validationHelper = $this->getMockBuilder(FieldNameValidationHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configHelper = $this->getMockBuilder(ConfigHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->fieldConfigModel = $this->getMockBuilder(FieldConfigModel::class)->getMock();

        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->handler = new RemoveRestoreConfigFieldHandler(
            $this->configManager,
            $this->validationHelper,
            $this->configHelper,
            $this->session,
            $this->eventDispatcher
        );
    }

    /**
     * @param JsonResponse $response
     * @param array $expectedContent
     */
    private function expectsJsonResponseWithContent(JsonResponse $response, array $expectedContent)
    {
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(json_encode($expectedContent), $response->getContent());
        $this->assertEquals(JsonResponse::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @param ConfigInterface|\PHPUnit_Framework_MockObject_MockBuilder $fieldConfig
     * @param ConfigInterface|\PHPUnit_Framework_MockObject_MockBuilder $entityConfig
     */
    private function expectsConfigManagerPersistAndFlush(ConfigInterface $fieldConfig, ConfigInterface $entityConfig)
    {
        $this->configManager
            ->expects($this->exactly(2))
            ->method('persist')
            ->withConsecutive([$fieldConfig], [$entityConfig]);

        $this->configManager
            ->expects($this->once())
            ->method('flush');
    }

    public function testHandleRemove()
    {
        $this->validationHelper->expects($this->once())
            ->method('getRemoveFieldValidationErrors')
            ->with($this->fieldConfigModel)
            ->willReturn([]);

        $entityConfig = $this->createMock(ConfigInterface::class);
        $entityConfig
            ->expects($this->once())
            ->method('set')
            ->with('upgradeable', true);

        $this->configHelper
            ->expects($this->once())
            ->method('getEntityConfigByField')
            ->with($this->fieldConfigModel, 'extend')
            ->willReturn($entityConfig);

        $fieldConfig = $this->createMock(ConfigInterface::class);

        $fieldConfig
            ->expects($this->once())
            ->method('set')
            ->with('state', ExtendScope::STATE_DELETE);

        $this->configHelper
            ->expects($this->once())
            ->method('getFieldConfig')
            ->with($this->fieldConfigModel, 'extend')
            ->willReturn($fieldConfig);

        $this->configManager
            ->expects($this->exactly(2))
            ->method('persist')
            ->withConsecutive([$fieldConfig], [$entityConfig]);

        $this->configManager
            ->expects($this->once())
            ->method('flush');

        $flashBag = $this->createMock(FlashBagInterface::class);
        $flashBag
            ->expects($this->once())
            ->method('add')
            ->with('success', self::SAMPLE_SUCCESS_MESSAGE);

        $this->session
            ->expects($this->once())
            ->method('getFlashBag')
            ->willReturn($flashBag);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(Events::AFTER_REMOVE_FIELD, new AfterRemoveFieldEvent($this->fieldConfigModel));

        $expectedContent = [
            'message' => self::SAMPLE_SUCCESS_MESSAGE,
            'successful' => true
        ];

        $response = $this->handler->handleRemove($this->fieldConfigModel, self::SAMPLE_SUCCESS_MESSAGE);

        $this->expectsJsonResponseWithContent($response, $expectedContent);
    }

    public function testHandleRemoveValidationError()
    {
        $this->validationHelper->expects($this->once())
            ->method('getRemoveFieldValidationErrors')
            ->with($this->fieldConfigModel)
            ->willReturn([
                self::SAMPLE_VALIDATION_ERROR_MESSAGE1,
                self::SAMPLE_VALIDATION_ERROR_MESSAGE2
            ]);

        $fieldConfig = $this->createMock(ConfigInterface::class);

        $fieldConfig
            ->expects($this->never())
            ->method('set')
            ->with('state', ExtendScope::STATE_DELETE);

        $this->configManager
            ->expects($this->never())
            ->method('flush');

        $flashBag = $this->createMock(FlashBagInterface::class);
        $flashBag
            ->expects($this->exactly(2))
            ->method('add')
            ->withConsecutive(
                ['error', self::SAMPLE_VALIDATION_ERROR_MESSAGE1],
                ['error', self::SAMPLE_VALIDATION_ERROR_MESSAGE2]
            );

        $this->session
            ->expects($this->exactly(2))
            ->method('getFlashBag')
            ->willReturn($flashBag);

        $expectedContent = [
            'message' => sprintf(
                '%s. %s',
                self::SAMPLE_VALIDATION_ERROR_MESSAGE1,
                self::SAMPLE_VALIDATION_ERROR_MESSAGE2
            ),
            'successful' => false
        ];

        $response = $this->handler->handleRemove($this->fieldConfigModel, self::SAMPLE_SUCCESS_MESSAGE);

        $this->expectsJsonResponseWithContent($response, $expectedContent);
    }

    public function testHandleRestoreWhenFieldCannotBeRestored()
    {
        $this->validationHelper
            ->expects($this->once())
            ->method('canFieldBeRestored')
            ->with($this->fieldConfigModel)
            ->willReturn(false);

        $flashBag = $this->createMock(FlashBagInterface::class);
        $flashBag
            ->expects($this->once())
            ->method('add')
            ->with('error', self::SAMPLE_ERROR_MESSAGE);

        $this->session
            ->expects($this->once())
            ->method('getFlashBag')
            ->willReturn($flashBag);

        $this->configManager
            ->expects($this->never())
            ->method('persist');

        $this->configManager
            ->expects($this->never())
            ->method('flush');

        $expectedContent = [
            'message' => self::SAMPLE_ERROR_MESSAGE,
            'successful' => false
        ];

        $response = $this->handler->handleRestore(
            $this->fieldConfigModel,
            self::SAMPLE_ERROR_MESSAGE,
            self::SAMPLE_SUCCESS_MESSAGE
        );

        $this->expectsJsonResponseWithContent($response, $expectedContent);
    }

    public function testHandleRestoreWhenFieldCanBeRestoredAndEntityClassNotExists()
    {
        $entityClassName = 'ClassNotExists';
        $expectedState = ExtendScope::STATE_NEW;

        $this->validationHelper
            ->expects($this->once())
            ->method('canFieldBeRestored')
            ->with($this->fieldConfigModel)
            ->willReturn(true);

        $entity = $this->createMock(EntityConfigModel::class);
        $entity
            ->expects($this->once())
            ->method('getClassName')
            ->willReturn($entityClassName);

        $this->fieldConfigModel
            ->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $entityConfig = $this->createMock(ConfigInterface::class);
        $entityConfig
            ->expects($this->once())
            ->method('set')
            ->with('upgradeable', true);

        $this->configHelper
            ->expects($this->once())
            ->method('getEntityConfigByField')
            ->with($this->fieldConfigModel, 'extend')
            ->willReturn($entityConfig);

        $fieldConfig = $this->createMock(ConfigInterface::class);
        $fieldConfig
            ->expects($this->once())
            ->method('set')
            ->with('state', $expectedState);

        $this->configHelper
            ->expects($this->once())
            ->method('getFieldConfig')
            ->with($this->fieldConfigModel, 'extend')
            ->willReturn($fieldConfig);

        $this->expectsConfigManagerPersistAndFlush($fieldConfig, $entityConfig);

        $expectedContent = [
            'message' => self::SAMPLE_SUCCESS_MESSAGE,
            'successful' => true
        ];

        $response = $this->handler->handleRestore(
            $this->fieldConfigModel,
            self::SAMPLE_ERROR_MESSAGE,
            self::SAMPLE_SUCCESS_MESSAGE
        );

        $this->expectsJsonResponseWithContent($response, $expectedContent);
    }

    public function testHandleRestoreWhenFieldCanBeRestoredAndFieldPropertyNotExists()
    {
        $entityClassName = TestActivityTarget::class;
        $expectedState = ExtendScope::STATE_NEW;

        $this->validationHelper
            ->expects($this->once())
            ->method('canFieldBeRestored')
            ->with($this->fieldConfigModel)
            ->willReturn(true);

        $entity = $this->createMock(EntityConfigModel::class);
        $entity
            ->expects($this->exactly(2))
            ->method('getClassName')
            ->willReturn($entityClassName);

        $this->fieldConfigModel
            ->expects($this->exactly(2))
            ->method('getEntity')
            ->willReturn($entity);

        $this->fieldConfigModel
            ->expects($this->once())
            ->method('getFieldName')
            ->willReturn('NotExistentProperty');

        $entityConfig = $this->createMock(ConfigInterface::class);
        $entityConfig
            ->expects($this->once())
            ->method('set')
            ->with('upgradeable', true);

        $this->configHelper
            ->expects($this->once())
            ->method('getEntityConfigByField')
            ->with($this->fieldConfigModel, 'extend')
            ->willReturn($entityConfig);

        $fieldConfig = $this->createMock(ConfigInterface::class);
        $fieldConfig
            ->expects($this->once())
            ->method('set')
            ->with('state', $expectedState);

        $this->configHelper
            ->expects($this->once())
            ->method('getFieldConfig')
            ->with($this->fieldConfigModel, 'extend')
            ->willReturn($fieldConfig);

        $this->expectsConfigManagerPersistAndFlush($fieldConfig, $entityConfig);

        $expectedContent = [
            'message' => self::SAMPLE_SUCCESS_MESSAGE,
            'successful' => true
        ];

        $response = $this->handler->handleRestore(
            $this->fieldConfigModel,
            self::SAMPLE_ERROR_MESSAGE,
            self::SAMPLE_SUCCESS_MESSAGE
        );

        $this->expectsJsonResponseWithContent($response, $expectedContent);
    }

    public function testHandleRestoreWhenFieldCanBeRestoredAndClassNameAndFieldExist()
    {
        $entityClassName = TestActivityTarget::class;
        $expectedState = ExtendScope::STATE_RESTORE;

        $this->validationHelper
            ->expects($this->once())
            ->method('canFieldBeRestored')
            ->with($this->fieldConfigModel)
            ->willReturn(true);

        $entity = $this->createMock(EntityConfigModel::class);
        $entity
            ->expects($this->exactly(2))
            ->method('getClassName')
            ->willReturn($entityClassName);

        $this->fieldConfigModel
            ->expects($this->exactly(2))
            ->method('getEntity')
            ->willReturn($entity);

        $this->fieldConfigModel
            ->expects($this->once())
            ->method('getFieldName')
            ->willReturn('id');

        $entityConfig = $this->createMock(ConfigInterface::class);
        $entityConfig
            ->expects($this->once())
            ->method('set')
            ->with('upgradeable', true);

        $this->configHelper
            ->expects($this->once())
            ->method('getEntityConfigByField')
            ->with($this->fieldConfigModel, 'extend')
            ->willReturn($entityConfig);

        $fieldConfig = $this->createMock(ConfigInterface::class);
        $fieldConfig
            ->expects($this->once())
            ->method('set')
            ->with('state', $expectedState);

        $this->configHelper
            ->expects($this->once())
            ->method('getFieldConfig')
            ->with($this->fieldConfigModel, 'extend')
            ->willReturn($fieldConfig);

        $this->expectsConfigManagerPersistAndFlush($fieldConfig, $entityConfig);

        $expectedContent = [
            'message' => self::SAMPLE_SUCCESS_MESSAGE,
            'successful' => true
        ];

        $response = $this->handler->handleRestore(
            $this->fieldConfigModel,
            self::SAMPLE_ERROR_MESSAGE,
            self::SAMPLE_SUCCESS_MESSAGE
        );

        $this->expectsJsonResponseWithContent($response, $expectedContent);
    }
}

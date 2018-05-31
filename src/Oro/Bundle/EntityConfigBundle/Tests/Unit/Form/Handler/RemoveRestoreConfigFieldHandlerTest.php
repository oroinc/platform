<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Form\Handler;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
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

    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    private $registry;

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

        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->handler = new RemoveRestoreConfigFieldHandler(
            $this->configManager,
            $this->validationHelper,
            $this->configHelper,
            $this->session,
            $this->eventDispatcher
        );
        $this->handler->setManagerRegistry($this->registry);
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

        $entityConfig = $this->prepareEntityConfig();
        $fieldConfig = $this->prepareFieldConfig();
        $fieldConfig
            ->expects($this->once())
            ->method('set')
            ->with('state', ExtendScope::STATE_DELETE);

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

        $this->prepareConfigMocksForRestoreCalls($entityClassName, $expectedState);

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

    public function testHandleRestoreWhenFieldCanBeRestoredAndEntityClassNotManaged()
    {
        $entityClassName = '\stdClass';
        $expectedState = ExtendScope::STATE_NEW;

        $this->prepareConfigMocksForRestoreCalls($entityClassName, $expectedState);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with($entityClassName)
            ->willReturn(null);

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

        $this->prepareConfigMocksForRestoreCalls($entityClassName, $expectedState);

        $this->fieldConfigModel
            ->expects($this->any())
            ->method('getFieldName')
            ->willReturn('NotExistentProperty');

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->any())
            ->method('hasField')
            ->with('NotExistentProperty')
            ->willReturn(false);
        $metadata->expects($this->any())
            ->method('hasAssociation')
            ->with('NotExistentProperty')
            ->willReturn(false);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getClassMetadata')
            ->with($entityClassName)
            ->willReturn($metadata);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with($entityClassName)
            ->willReturn($em);

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

    /**
     * @dataProvider fieldExistenceDataProvider
     * @param bool $hasField
     * @param bool $hasAssociation
     */
    public function testHandleRestoreWhenFieldCanBeRestoredAndClassNameAndFieldExist($hasField, $hasAssociation)
    {
        $entityClassName = TestActivityTarget::class;
        $expectedState = ExtendScope::STATE_RESTORE;
        $fieldName = 'id';

        $this->prepareConfigMocksForRestoreCalls($entityClassName, $expectedState);

        $this->fieldConfigModel
            ->expects($this->once())
            ->method('getFieldName')
            ->willReturn($fieldName);

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->any())
            ->method('hasField')
            ->with($fieldName)
            ->willReturn(true);
        $metadata->expects($this->any())
            ->method('hasAssociation')
            ->with($fieldName)
            ->willReturn(false);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getClassMetadata')
            ->with($entityClassName)
            ->willReturn($metadata);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with($entityClassName)
            ->willReturn($em);

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

    /**
     * @return array
     */
    public function fieldExistenceDataProvider()
    {
        return [
            'field' => [true, false],
            'association' => [false, true]
        ];
    }

    /**
     * @param string $entityClassName
     */
    protected function prepareEntityConfigModel($entityClassName)
    {
        $entity = $this->createMock(EntityConfigModel::class);
        $entity
            ->expects($this->any())
            ->method('getClassName')
            ->willReturn($entityClassName);

        $this->fieldConfigModel
            ->expects($this->any())
            ->method('getEntity')
            ->willReturn($entity);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function prepareEntityConfig(): \PHPUnit_Framework_MockObject_MockObject
    {
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

        return $entityConfig;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function prepareFieldConfig(): \PHPUnit_Framework_MockObject_MockObject
    {
        $fieldConfig = $this->createMock(ConfigInterface::class);

        $this->configHelper
            ->expects($this->once())
            ->method('getFieldConfig')
            ->with($this->fieldConfigModel, 'extend')
            ->willReturn($fieldConfig);

        return $fieldConfig;
    }

    /**
     * @param string $entityClassName
     * @param string $expectedState
     */
    protected function prepareConfigMocksForRestoreCalls($entityClassName, $expectedState)
    {
        $this->validationHelper
            ->expects($this->once())
            ->method('canFieldBeRestored')
            ->with($this->fieldConfigModel)
            ->willReturn(true);

        $this->prepareEntityConfigModel($entityClassName);
        $entityConfig = $this->prepareEntityConfig();
        $fieldConfig = $this->prepareFieldConfig($expectedState);
        $fieldConfig->expects($this->exactly(2))
            ->method('set')
            ->withConsecutive(
                ['state'],
                ['is_deleted', false]
            );

        $this->expectsConfigManagerPersistAndFlush($fieldConfig, $entityConfig);
    }
}

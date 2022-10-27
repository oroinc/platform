<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Form\EventListener\EnumFieldConfigSubscriber;
use Oro\Bundle\EntityExtendBundle\Tools\EnumSynchronizer;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Translation\Translator;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EnumFieldConfigSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var Translator|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var EnumSynchronizer|\PHPUnit\Framework\MockObject\MockObject */
    private $enumSynchronizer;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var EnumFieldConfigSubscriber */
    private $subscriber;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->translator = $this->createMock(Translator::class);
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnArgument(0);

        $this->enumSynchronizer = $this->createMock(EnumSynchronizer::class);

        $nameGenerator = $this->createMock(ExtendDbIdentifierNameGenerator::class);
        $nameGenerator->expects($this->any())
            ->method('getMaxEnumCodeSize')
            ->willReturn(54);

        $this->logger = $this->createMock(LoggerInterface::class);

        $this->subscriber = new EnumFieldConfigSubscriber(
            $this->configManager,
            $this->translator,
            $this->enumSynchronizer,
            $nameGenerator
        );
        $this->subscriber->setLogger($this->logger);
    }

    public function testPreSetDataForEntityConfigModel()
    {
        $configModel = $this->createMock(EntityConfigModel::class);

        $event = $this->getFormEventMock($configModel);
        $event->expects($this->never())
            ->method('setData');

        $this->subscriber->preSetData($event);
    }

    public function testPreSetDataForNotEnumFieldType()
    {
        $configModel = $this->createMock(FieldConfigModel::class);
        $configModel->expects($this->once())
            ->method('getType')
            ->willReturn('manyToOne');

        $event = $this->getFormEventMock($configModel);
        $event->expects($this->never())
            ->method('setData');

        $this->subscriber->preSetData($event);
    }

    /**
     * @dataProvider enumTypeProvider
     */
    public function testPreSetDataForNewEnum(string $dataType)
    {
        $configModel = $this->createMock(FieldConfigModel::class);
        $configModel->expects($this->once())
            ->method('getType')
            ->willReturn($dataType);
        $configModel->expects($this->once())
            ->method('toArray')
            ->with('enum')
            ->willReturn(['enum_name' => 'Test Enum']);

        $event = $this->getFormEventMock($configModel);
        $event->expects($this->never())
            ->method('setData');

        $this->subscriber->preSetData($event);
    }

    /**
     * @dataProvider enumTypeProvider
     */
    public function testPreSetDataForExistingEnum(string $dataType)
    {
        $enumCode = 'test_enum';
        $enumLabel = ExtendHelper::getEnumTranslationKey('label', $enumCode);
        $enumValueClassName = ExtendHelper::buildEnumValueClassName($enumCode);

        $configModel = $this->createMock(FieldConfigModel::class);
        $configModel->expects($this->once())
            ->method('getType')
            ->willReturn($dataType);
        $configModel->expects($this->once())
            ->method('toArray')
            ->with('enum')
            ->willReturn(['enum_code' => $enumCode]);

        $event = $this->getFormEventMock($configModel);

        $initialData = [];
        $enumOptions = [
            ['id' => 'val1', 'label' => 'Value 1', 'priority' => 1],
        ];

        $expectedData = [
            'enum' => [
                'enum_name'    => $enumLabel,
                'enum_public'  => true,
                'enum_options' => $enumOptions,
            ]
        ];

        $event->expects($this->once())
            ->method('getData')
            ->willReturn($initialData);

        $enumConfig = new Config(new EntityConfigId('enum', $enumValueClassName));
        $enumConfig->set('public', true);

        $this->enumSynchronizer->expects($this->once())
            ->method('getEnumOptions')
            ->with($enumValueClassName)
            ->willReturn($enumOptions);

        $enumConfigProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('enum')
            ->willReturn($enumConfigProvider);
        $enumConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($enumValueClassName)
            ->willReturn(true);
        $enumConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($enumValueClassName)
            ->willReturn($enumConfig);

        $event->expects($this->once())
            ->method('setData')
            ->with($expectedData);

        $this->subscriber->preSetData($event);
    }

    public function testPostSubmitForEntityConfigModel()
    {
        $configModel = $this->createMock(EntityConfigModel::class);

        $event = $this->getFormEventMock($configModel);
        $event->expects($this->never())
            ->method('setData');

        $this->subscriber->postSubmit($event);
    }

    public function testPostSubmitForNotEnumFieldType()
    {
        $configModel = $this->createMock(FieldConfigModel::class);
        $configModel->expects($this->once())
            ->method('getType')
            ->willReturn('manyToOne');

        $event = $this->getFormEventMock($configModel);

        $event->expects($this->never())
            ->method('setData');

        $this->subscriber->postSubmit($event);
    }

    /**
     * @dataProvider enumTypeProvider
     */
    public function testPostSubmitForNotValidForm(string $dataType)
    {
        $configModel = $this->createMock(FieldConfigModel::class);
        $configModel->expects($this->once())
            ->method('getType')
            ->willReturn($dataType);

        $form = $this->createMock(\Symfony\Component\Form\Test\FormInterface::class);
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(false);

        $event = $this->getFormEventMock($configModel, $form);

        $event->expects($this->never())
            ->method('setData');

        $this->subscriber->postSubmit($event);
    }

    /**
     * @dataProvider enumTypeProvider
     */
    public function testPostSubmitForNewEnum(string $dataType)
    {
        $enumCode = 'test_enum';
        $enumName = 'Test Enum';
        $enumValueClassName = ExtendHelper::buildEnumValueClassName($enumCode);
        $locale = 'fr';

        $configModel = $this->createMock(FieldConfigModel::class);
        $configModel->expects($this->never())
            ->method('getId');
        $configModel->expects($this->once())
            ->method('getType')
            ->willReturn($dataType);

        $form = $this->createMock(\Symfony\Component\Form\Test\FormInterface::class);
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $event = $this->getFormEventMock($configModel, $form);

        $enumOptions   = [
            ['id' => 'val1', 'label' => 'Value 1', 'priority' => 1],
            ['id' => 'val1', 'label' => 'Value 1', 'priority' => 4],
            ['label' => 'Value 1', 'priority' => 2],
        ];
        $submittedData = [
            'enum' => [
                'enum_name'    => $enumName,
                'enum_public'  => true,
                'enum_options' => $enumOptions
            ]
        ];
        $expectedData  = [
            'enum' => [
                'enum_name'    => $enumName,
                'enum_public'  => true,
                'enum_options' => [
                    ['id' => 'val1', 'label' => 'Value 1', 'priority' => 1],
                    ['label' => 'Value 1', 'priority' => 2],
                    ['id' => 'val1', 'label' => 'Value 1', 'priority' => 3],
                ],
                'enum_locale'  => $locale
            ]
        ];

        $event->expects($this->once())
            ->method('getData')
            ->willReturn($submittedData);
        $configModel->expects($this->once())
            ->method('toArray')
            ->with('enum')
            ->willReturn([]);

        $this->translator->expects($this->once())
            ->method('getLocale')
            ->willReturn($locale);
        $enumConfigProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('enum')
            ->willReturn($enumConfigProvider);
        $enumConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($enumValueClassName)
            ->willReturn(false);

        $event->expects($this->once())
            ->method('setData')
            ->with($expectedData);

        $this->subscriber->postSubmit($event);
    }

    /**
     * @dataProvider enumTypeProvider
     */
    public function testPostSubmitForNewEnumWithoutNameAndPublic(string $dataType)
    {
        $entityClassName    = 'Test\Entity';
        $fieldName          = 'testField';
        $enumCode           = ExtendHelper::generateEnumCode($entityClassName, $fieldName);
        $enumValueClassName = ExtendHelper::buildEnumValueClassName($enumCode);
        $locale             = 'fr';

        $entityConfigModel = $this->createMock(EntityConfigModel::class);
        $entityConfigModel->expects($this->once())
            ->method('getClassName')
            ->willReturn($entityClassName);

        $configModel = $this->createMock(FieldConfigModel::class);
        $configModel->expects($this->never())
            ->method('getId');
        $configModel->expects($this->once())
            ->method('getType')
            ->willReturn($dataType);
        $configModel->expects($this->once())
            ->method('getEntity')
            ->willReturn($entityConfigModel);
        $configModel->expects($this->once())
            ->method('getFieldName')
            ->willReturn($fieldName);

        $form = $this->createMock(\Symfony\Component\Form\Test\FormInterface::class);
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $event = $this->getFormEventMock($configModel, $form);

        $enumOptions   = [
            ['id' => 'val1', 'label' => 'Value 1', 'priority' => 1],
        ];
        $submittedData = [
            'enum' => [
                'enum_options' => $enumOptions
            ]
        ];
        $expectedData  = [
            'enum' => [
                'enum_options' => $enumOptions,
                'enum_locale'  => $locale
            ]
        ];

        $event->expects($this->once())
            ->method('getData')
            ->willReturn($submittedData);
        $configModel->expects($this->once())
            ->method('toArray')
            ->with('enum')
            ->willReturn([]);

        $this->translator->expects($this->once())
            ->method('getLocale')
            ->willReturn($locale);
        $enumConfigProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('enum')
            ->willReturn($enumConfigProvider);
        $enumConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($enumValueClassName)
            ->willReturn(false);

        $event->expects($this->once())
            ->method('setData')
            ->with($expectedData);

        $this->subscriber->postSubmit($event);
    }

    /**
     * @dataProvider enumTypeProvider
     */
    public function testPostSubmitForExistingEnum(string $dataType)
    {
        $enumCode = 'test_enum';
        $enumName = 'Test Enum';
        $enumPublic = false;
        $enumValueClassName = ExtendHelper::buildEnumValueClassName($enumCode);
        $locale = 'fr';

        $configModel = $this->createMock(FieldConfigModel::class);
        $configModel->expects($this->once())
            ->method('getId')
            ->willReturn(123);
        $configModel->expects($this->once())
            ->method('getType')
            ->willReturn($dataType);

        $form = $this->createMock(\Symfony\Component\Form\Test\FormInterface::class);
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $event = $this->getFormEventMock($configModel, $form);

        $enumOptions   = [
            ['id' => 'val1', 'label' => 'Value 1', 'priority' => 1],
        ];
        $submittedData = [
            'enum' => [
                'enum_name'    => $enumName,
                'enum_public'  => $enumPublic,
                'enum_options' => $enumOptions
            ]
        ];
        $expectedData  = [
            'enum' => []
        ];

        $event->expects($this->once())
            ->method('getData')
            ->willReturn($submittedData);
        $configModel->expects($this->once())
            ->method('toArray')
            ->with('enum')
            ->willReturn(['enum_code' => $enumCode]);

        $this->translator->expects($this->once())
            ->method('getLocale')
            ->willReturn($locale);
        $enumConfigProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('enum')
            ->willReturn($enumConfigProvider);
        $enumConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($enumValueClassName)
            ->willReturn(true);

        $this->enumSynchronizer->expects($this->once())
            ->method('applyEnumNameTrans')
            ->with($enumCode, $enumName, $locale);
        $this->enumSynchronizer->expects($this->once())
            ->method('applyEnumOptions')
            ->with($enumValueClassName, $enumOptions, $locale);
        $this->enumSynchronizer->expects($this->once())
            ->method('applyEnumEntityOptions')
            ->with($enumValueClassName, $enumPublic);

        $event->expects($this->once())
            ->method('setData')
            ->with($expectedData);

        $this->subscriber->postSubmit($event);
    }

    public function enumTypeProvider(): array
    {
        return [
            ['enum'],
            ['multiEnum'],
        ];
    }

    /**
     * @dataProvider enumTypeProvider
     */
    public function testPostSubmitForEnumSyncError(string $dataType)
    {
        $enumCode = 'test_enum';
        $enumName = 'Test Enum';
        $enumValueClassName = ExtendHelper::buildEnumValueClassName($enumCode);
        $locale = 'en_GB';

        $configModel = $this->createMock(FieldConfigModel::class);
        $configModel->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $configModel->expects($this->any())
            ->method('getType')
            ->willReturn($dataType);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $event = $this->getFormEventMock($configModel, $form);

        $enumOptions   = [
            ['id' => 'val1', 'label' => 'Value 1', 'priority' => 1],
        ];
        $submittedData = [
            'enum' => [
                'enum_name'    => $enumName,
                'enum_public'  => true,
                'enum_options' => $enumOptions
            ]
        ];

        $event->expects($this->any())
            ->method('getData')
            ->willReturn($submittedData);
        $configModel->expects($this->any())
            ->method('toArray')
            ->with('enum')
            ->willReturn([]);

        $this->translator->expects($this->any())
            ->method('getLocale')
            ->willReturn($locale);

        $enumConfigProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->with('enum')
            ->willReturn($enumConfigProvider);
        $enumConfigProvider->expects($this->any())
            ->method('hasConfig')
            ->with($enumValueClassName)
            ->willReturn(true);

        $event->expects($this->never())
            ->method('setData');

        $this->enumSynchronizer->expects($this->any())
            ->method('applyEnumNameTrans');

        $exception = new \Exception();
        $this->enumSynchronizer->expects($this->once())
            ->method('applyEnumOptions')
            ->with($enumValueClassName, $enumOptions, $locale)
            ->willThrowException($exception);

        $form->expects($this->once())
            ->method('addError');
        $this->logger->expects($this->once())
            ->method('error')
            ->with('Error occurred during enum options save', ['exception'=> $exception]);

        $this->subscriber->postSubmit($event);
    }

    private function getFormEventMock(
        ConfigModel $configModel,
        FormInterface|\PHPUnit\Framework\MockObject\MockObject|null $form = null
    ): FormEvent|\PHPUnit\Framework\MockObject\MockObject {
        if (!$form) {
            $form = $this->createMock(FormInterface::class);
        }
        $formConfig = $this->createMock(FormConfigInterface::class);
        $form->expects($this->once())
            ->method('getConfig')
            ->willReturn($formConfig);
        $formConfig->expects($this->once())
            ->method('getOption')
            ->with('config_model')
            ->willReturn($configModel);

        $event = $this->createMock(FormEvent::class);
        $event->expects($this->once())
            ->method('getForm')
            ->willReturn($form);

        return $event;
    }
}

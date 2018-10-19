<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Form\EventListener\EnumFieldConfigSubscriber;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormInterface;

class EnumFieldConfigSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $configManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $enumSynchronizer;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $logger;

    /** @var EnumFieldConfigSubscriber */
    protected $subscriber;

    public function setUp()
    {
        $this->configManager    = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->translator       = $this->createMock('Symfony\Component\Translation\TranslatorInterface');
        $this->enumSynchronizer = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Tools\EnumSynchronizer')
            ->disableOriginalConstructor()
            ->getMock();

        $this->translator->expects($this->any())
            ->method('trans')
            ->will($this->returnArgument(0));

        $this->logger = $this->createMock(LoggerInterface::class);

        $this->subscriber = new EnumFieldConfigSubscriber(
            $this->configManager,
            $this->translator,
            $this->enumSynchronizer
        );
        $this->subscriber->setLogger($this->logger);
    }

    public function testPreSetDataForEntityConfigModel()
    {
        $configModel = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel')
            ->disableOriginalConstructor()
            ->getMock();

        $event = $this->getFormEventMock($configModel);

        $event->expects($this->never())
            ->method('setData');

        $this->subscriber->preSetData($event);
    }

    public function testPreSetDataForNotEnumFieldType()
    {
        $configModel = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel')
            ->disableOriginalConstructor()
            ->getMock();
        $configModel->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('manyToOne'));

        $event = $this->getFormEventMock($configModel);

        $event->expects($this->never())
            ->method('setData');

        $this->subscriber->preSetData($event);
    }

    /**
     * @dataProvider enumTypeProvider
     */
    public function testPreSetDataForNewEnum($dataType)
    {
        $configModel = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel')
            ->disableOriginalConstructor()
            ->getMock();
        $configModel->expects($this->once())
            ->method('getType')
            ->will($this->returnValue($dataType));
        $configModel->expects($this->once())
            ->method('toArray')
            ->with('enum')
            ->will($this->returnValue(['enum_name' => 'Test Enum']));

        $event = $this->getFormEventMock($configModel);

        $event->expects($this->never())
            ->method('setData');

        $this->subscriber->preSetData($event);
    }

    /**
     * @dataProvider enumTypeProvider
     */
    public function testPreSetDataForExistingEnum($dataType)
    {
        $enumCode           = 'test_enum';
        $enumLabel          = ExtendHelper::getEnumTranslationKey('label', $enumCode);
        $enumValueClassName = ExtendHelper::buildEnumValueClassName($enumCode);

        $configModel = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel')
            ->disableOriginalConstructor()
            ->getMock();
        $configModel->expects($this->once())
            ->method('getType')
            ->will($this->returnValue($dataType));
        $configModel->expects($this->once())
            ->method('toArray')
            ->with('enum')
            ->will($this->returnValue(['enum_code' => $enumCode]));

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
            ->will($this->returnValue($initialData));

        $enumConfig = new Config(new EntityConfigId('enum', $enumValueClassName));
        $enumConfig->set('public', true);

        $this->enumSynchronizer->expects($this->once())
            ->method('getEnumOptions')
            ->with($enumValueClassName)
            ->will($this->returnValue($enumOptions));

        $enumConfigProvider = $this->getConfigProviderMock();
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('enum')
            ->will($this->returnValue($enumConfigProvider));
        $enumConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($enumValueClassName)
            ->will($this->returnValue(true));
        $enumConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($enumValueClassName)
            ->will($this->returnValue($enumConfig));

        $event->expects($this->once())
            ->method('setData')
            ->with($expectedData);

        $this->subscriber->preSetData($event);
    }

    public function testPostSubmitForEntityConfigModel()
    {
        $configModel = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel')
            ->disableOriginalConstructor()
            ->getMock();

        $event = $this->getFormEventMock($configModel);

        $event->expects($this->never())
            ->method('setData');

        $this->subscriber->postSubmit($event);
    }

    public function testPostSubmitForNotEnumFieldType()
    {
        $configModel = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel')
            ->disableOriginalConstructor()
            ->getMock();
        $configModel->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('manyToOne'));

        $event = $this->getFormEventMock($configModel);

        $event->expects($this->never())
            ->method('setData');

        $this->subscriber->postSubmit($event);
    }

    /**
     * @dataProvider enumTypeProvider
     */
    public function testPostSubmitForNotValidForm($dataType)
    {
        $configModel = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel')
            ->disableOriginalConstructor()
            ->getMock();
        $configModel->expects($this->once())
            ->method('getType')
            ->will($this->returnValue($dataType));

        $form = $this->createMock('Symfony\Component\Form\Test\FormInterface');
        $form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(false));

        $event = $this->getFormEventMock($configModel, $form);

        $event->expects($this->never())
            ->method('setData');

        $this->subscriber->postSubmit($event);
    }

    /**
     * @dataProvider enumTypeProvider
     */
    public function testPostSubmitForNewEnum($dataType)
    {
        $enumCode           = 'test_enum';
        $enumName           = 'Test Enum';
        $enumValueClassName = ExtendHelper::buildEnumValueClassName($enumCode);
        $locale             = 'fr';

        $configModel = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel')
            ->disableOriginalConstructor()
            ->getMock();
        $configModel->expects($this->never())
            ->method('getId');
        $configModel->expects($this->once())
            ->method('getType')
            ->will($this->returnValue($dataType));

        $form = $this->createMock('Symfony\Component\Form\Test\FormInterface');
        $form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));

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
            ->will($this->returnValue($submittedData));
        $configModel->expects($this->once())
            ->method('toArray')
            ->with('enum')
            ->will($this->returnValue([]));

        $this->translator->expects($this->once())
            ->method('getLocale')
            ->will($this->returnValue($locale));
        $enumConfigProvider = $this->getConfigProviderMock();
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('enum')
            ->will($this->returnValue($enumConfigProvider));
        $enumConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($enumValueClassName)
            ->will($this->returnValue(false));

        $event->expects($this->once())
            ->method('setData')
            ->with($expectedData);

        $this->subscriber->postSubmit($event);
    }

    /**
     * @dataProvider enumTypeProvider
     */
    public function testPostSubmitForNewEnumWithoutNameAndPublic($dataType)
    {
        $entityClassName    = 'Test\Entity';
        $fieldName          = 'testField';
        $enumCode           = ExtendHelper::generateEnumCode($entityClassName, $fieldName);
        $enumValueClassName = ExtendHelper::buildEnumValueClassName($enumCode);
        $locale             = 'fr';

        $entityConfigModel = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel')
            ->disableOriginalConstructor()
            ->getMock();
        $entityConfigModel->expects($this->once())
            ->method('getClassName')
            ->will($this->returnValue($entityClassName));

        $configModel = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel')
            ->disableOriginalConstructor()
            ->getMock();
        $configModel->expects($this->never())
            ->method('getId');
        $configModel->expects($this->once())
            ->method('getType')
            ->will($this->returnValue($dataType));
        $configModel->expects($this->once())
            ->method('getEntity')
            ->will($this->returnValue($entityConfigModel));
        $configModel->expects($this->once())
            ->method('getFieldName')
            ->will($this->returnValue($fieldName));

        $form = $this->createMock('Symfony\Component\Form\Test\FormInterface');
        $form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));

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
            ->will($this->returnValue($submittedData));
        $configModel->expects($this->once())
            ->method('toArray')
            ->with('enum')
            ->will($this->returnValue([]));

        $this->translator->expects($this->once())
            ->method('getLocale')
            ->will($this->returnValue($locale));
        $enumConfigProvider = $this->getConfigProviderMock();
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('enum')
            ->will($this->returnValue($enumConfigProvider));
        $enumConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($enumValueClassName)
            ->will($this->returnValue(false));

        $event->expects($this->once())
            ->method('setData')
            ->with($expectedData);

        $this->subscriber->postSubmit($event);
    }

    /**
     * @dataProvider enumTypeProvider
     */
    public function testPostSubmitForExistingEnum($dataType)
    {
        $enumCode           = 'test_enum';
        $enumName           = 'Test Enum';
        $enumPublic         = false;
        $enumValueClassName = ExtendHelper::buildEnumValueClassName($enumCode);
        $locale             = 'fr';

        $configModel = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel')
            ->disableOriginalConstructor()
            ->getMock();
        $configModel->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(123));
        $configModel->expects($this->once())
            ->method('getType')
            ->will($this->returnValue($dataType));

        $form = $this->createMock('Symfony\Component\Form\Test\FormInterface');
        $form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));

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
            ->will($this->returnValue($submittedData));
        $configModel->expects($this->once())
            ->method('toArray')
            ->with('enum')
            ->will($this->returnValue(['enum_code' => $enumCode]));

        $this->translator->expects($this->once())
            ->method('getLocale')
            ->will($this->returnValue($locale));
        $enumConfigProvider = $this->getConfigProviderMock();
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('enum')
            ->will($this->returnValue($enumConfigProvider));
        $enumConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($enumValueClassName)
            ->will($this->returnValue(true));

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

    public function enumTypeProvider()
    {
        return [
            ['enum'],
            ['multiEnum'],
        ];
    }

    /**
     * @dataProvider enumTypeProvider
     * @param string $dataType
     */
    public function testPostSubmitForEnumSyncError($dataType)
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

        $enumConfigProvider = $this->getConfigProviderMock();
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

    /**
     * @param mixed                                         $configModel
     * @param \PHPUnit\Framework\MockObject\MockObject|null $form
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getFormEventMock($configModel, $form = null)
    {
        if (!$form) {
            $form = $this->createMock('Symfony\Component\Form\Test\FormInterface');
        }
        $formConfig = $this->createMock('Symfony\Component\Form\FormConfigInterface');
        $form->expects($this->once())
            ->method('getConfig')
            ->will($this->returnValue($formConfig));
        $formConfig->expects($this->once())
            ->method('getOption')
            ->with('config_model')
            ->will($this->returnValue($configModel));

        $event = $this->getMockBuilder('Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('getForm')
            ->will($this->returnValue($form));

        return $event;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getConfigProviderMock()
    {
        return $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
    }
}

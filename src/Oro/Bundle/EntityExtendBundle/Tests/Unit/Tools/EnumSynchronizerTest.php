<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools;

use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Gedmo\Translatable\Query\TreeWalker\TranslationWalker;
use Gedmo\Translatable\TranslatableListener;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Translation\ConfigTranslationHelper;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\EnumSynchronizer;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class EnumSynchronizerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigManager */
    protected $configManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry */
    protected $doctrine;

    /** @var \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface */
    protected $translator;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $dbTranslationMetadataCache;

    /** @var ConfigTranslationHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $translationHelper;

    /** @var EnumSynchronizer */
    protected $synchronizer;

    public function setUp()
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->translationHelper = $this
            ->getMockBuilder(ConfigTranslationHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->synchronizer = new EnumSynchronizer(
            $this->configManager,
            $this->doctrine,
            $this->translator,
            $this->translationHelper
        );
    }

    /**
     * @dataProvider enumTypeProvider
     */
    public function testSyncNoChanges($enumType)
    {
        $config1 = new Config(new EntityConfigId('extend', 'Test\Entity1'));
        $config1->set('is_extend', true);
        $config2 = new Config(new EntityConfigId('extend', 'Test\Entity2'));
        $config2->set('is_extend', true);
        $config2->set('is_deleted', true);
        $config3 = new Config(new EntityConfigId('extend', 'Test\Entity3'));

        $configs = [$config1, $config2, $config3];

        $fieldConfig1 = new Config(new FieldConfigId('extend', 'Test\Entity1', 'field1', $enumType));
        $fieldConfig2 = new Config(new FieldConfigId('extend', 'Test\Entity1', 'field2', $enumType));
        $fieldConfig2->set('is_deleted', true);
        $fieldConfig3 = new Config(new FieldConfigId('extend', 'Test\Entity1', 'field3', 'string'));

        $fieldConfigs = [$fieldConfig1, $fieldConfig2, $fieldConfig3];

        $enumFieldConfig1 = new Config(new FieldConfigId('enum', 'Test\Entity1', 'field1', $enumType));

        $enumConfigProvider = $this->createMock(ConfigProvider::class);
        $extendConfigProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects($this->exactly(2))
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['extend', $extendConfigProvider],
                        ['enum', $enumConfigProvider]
                    ]
                )
            );
        $extendConfigProvider->expects($this->at(0))
            ->method('getConfigs')
            ->will($this->returnValue($configs));
        $extendConfigProvider->expects($this->at(1))
            ->method('getConfigs')
            ->with($config1->getId()->getClassName())
            ->will($this->returnValue($fieldConfigs));
        $enumConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with('Test\Entity1', 'field1')
            ->will($this->returnValue($enumFieldConfig1));

        /** @var EnumSynchronizer|\PHPUnit\Framework\MockObject\MockObject $synchronizer */
        $synchronizer = $this->getMockBuilder(EnumSynchronizer::class)
            ->setMethods(['applyEnumNameTrans', 'applyEnumOptions', 'applyEnumEntityOptions', 'updateEnumFieldConfig'])
            ->setConstructorArgs([
                $this->configManager,
                $this->doctrine,
                $this->translator,
                $this->translationHelper,
            ])
            ->getMock();

        $synchronizer->expects($this->never())
            ->method('updateEnumFieldConfig');

        $synchronizer->sync();
    }

    /**
     * @dataProvider enumTypeProvider
     */
    public function testSyncForAlreadySynchronizedField($enumType)
    {
        $enumCode = 'test_enum';

        $entityConfig = new Config(new EntityConfigId('extend', 'Test\Entity1'));
        $entityConfig->set('is_extend', true);
        $fieldConfig = new Config(new FieldConfigId('extend', 'Test\Entity1', 'field1', $enumType));
        $enumFieldConfig = new Config(new FieldConfigId('enum', 'Test\Entity1', 'field1', $enumType));
        $enumFieldConfig->set('enum_code', $enumCode);

        $enumConfigProvider = $this->createMock(ConfigProvider::class);
        $extendConfigProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects($this->exactly(2))
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['extend', $extendConfigProvider],
                        ['enum', $enumConfigProvider]
                    ]
                )
            );
        $extendConfigProvider->expects($this->at(0))
            ->method('getConfigs')
            ->will($this->returnValue([$entityConfig]));
        $extendConfigProvider->expects($this->at(1))
            ->method('getConfigs')
            ->with($entityConfig->getId()->getClassName())
            ->will($this->returnValue([$fieldConfig]));
        $enumConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with('Test\Entity1', 'field1')
            ->will($this->returnValue($enumFieldConfig));
        $this->configManager->expects($this->never())
            ->method('persist');

        /** @var EnumSynchronizer|\PHPUnit\Framework\MockObject\MockObject $synchronizer */
        $synchronizer = $this->getMockBuilder(EnumSynchronizer::class)
            ->setMethods(['applyEnumNameTrans', 'applyEnumOptions', 'applyEnumEntityOptions'])
            ->setConstructorArgs([
                $this->configManager,
                $this->doctrine,
                $this->translator,
                $this->translationHelper,
            ])
            ->getMock();

        $synchronizer->expects($this->never())
            ->method('applyEnumNameTrans');
        $synchronizer->expects($this->never())
            ->method('applyEnumOptions');
        $synchronizer->expects($this->never())
            ->method('applyEnumEntityOptions');

        $synchronizer->sync();
    }

    /**
     * @dataProvider enumTypeProvider
     */
    public function testSyncForNewField($enumType)
    {
        $enumCode = 'test_enum';
        $enumName = 'Test Enum';
        $locale = 'fr';
        $enumPublic = true;
        $enumOptions = [['label' => 'Opt1']];

        $enumValueClassName = 'Test\EnumValue';

        $entityConfig = new Config(new EntityConfigId('extend', 'Test\Entity1'));
        $entityConfig->set('is_extend', true);
        $fieldConfig = new Config(new FieldConfigId('extend', 'Test\Entity1', 'field1', $enumType));
        $fieldConfig->set('target_entity', $enumValueClassName);
        $enumFieldConfig = new Config(new FieldConfigId('enum', 'Test\Entity1', 'field1', $enumType));
        $enumFieldConfig->set('enum_code', $enumCode);
        $enumFieldConfig->set('enum_name', $enumName);
        $enumFieldConfig->set('enum_locale', $locale);
        $enumFieldConfig->set('enum_public', $enumPublic);
        $enumFieldConfig->set('enum_options', $enumOptions);

        $expectedEnumFieldConfig = new Config($enumFieldConfig->getId());
        $expectedEnumFieldConfig->set('enum_code', $enumCode);

        $enumConfigProvider = $this->createMock(ConfigProvider::class);
        $extendConfigProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects($this->exactly(2))
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['extend', $extendConfigProvider],
                        ['enum', $enumConfigProvider]
                    ]
                )
            );
        $extendConfigProvider->expects($this->at(0))
            ->method('getConfigs')
            ->will($this->returnValue([$entityConfig]));
        $extendConfigProvider->expects($this->at(1))
            ->method('getConfigs')
            ->with($entityConfig->getId()->getClassName())
            ->will($this->returnValue([$fieldConfig]));
        $enumConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with('Test\Entity1', 'field1')
            ->will($this->returnValue($enumFieldConfig));
        $this->configManager->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($enumFieldConfig));
        $this->configManager->expects($this->once())
            ->method('flush');

        /** @var EnumSynchronizer|\PHPUnit\Framework\MockObject\MockObject $synchronizer */
        $synchronizer = $this->getMockBuilder(EnumSynchronizer::class)
            ->setMethods(['applyEnumNameTrans', 'applyEnumOptions', 'applyEnumEntityOptions'])
            ->setConstructorArgs([
                $this->configManager,
                $this->doctrine,
                $this->translator,
                $this->translationHelper,
            ])
            ->getMock();

        $synchronizer->expects($this->once())
            ->method('applyEnumNameTrans')
            ->with($enumCode, $enumName, $locale);
        $synchronizer->expects($this->once())
            ->method('applyEnumOptions')
            ->with($enumValueClassName, $enumOptions, $locale);
        $synchronizer->expects($this->once())
            ->method('applyEnumEntityOptions')
            ->with($enumValueClassName, $enumPublic, false);

        $synchronizer->sync();

        $this->assertEquals($expectedEnumFieldConfig, $enumFieldConfig);
    }

    public function enumTypeProvider()
    {
        return [
            ['enum'],
            ['multiEnum']
        ];
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage $enumCode must not be empty.
     */
    public function testApplyEnumNameTransWithEmptyEnumCode()
    {
        $this->synchronizer->applyEnumNameTrans('', 'Test Enum', 'fr');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage $enumName must not be empty.
     */
    public function testApplyEnumNameTransWithEmptyEnumName()
    {
        $this->synchronizer->applyEnumNameTrans('test_enum', '', 'fr');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage $locale must not be empty.
     */
    public function testApplyEnumNameTransWithEmptyLocale()
    {
        $this->synchronizer->applyEnumNameTrans('test_enum', 'Test Enum', null);
    }

    public function testApplyEnumNameTransNoChanges()
    {
        $enumCode = 'test_enum';
        $enumName = 'Test Enum';
        $locale = 'fr';

        $this->translator->expects($this->once())
            ->method('trans')
            ->with(
                ExtendHelper::getEnumTranslationKey('label', $enumCode),
                [],
                null,
                $locale
            )
            ->will($this->returnValue($enumName));

        $this->doctrine->expects($this->never())
            ->method('getManagerForClass');
        $this->translationHelper->expects($this->once())
            ->method('saveTranslations')
            ->with([]);

        $this->synchronizer->applyEnumNameTrans($enumCode, $enumName, $locale);
    }

    public function testApplyEnumNameTransEnumNameChanged()
    {
        $enumCode = 'test_enum';
        $enumName = 'Test Enum New';
        $locale = 'fr';

        $oldEnumName = 'Test Enum';

        $this->translator->expects($this->once())
            ->method('trans')
            ->with(
                ExtendHelper::getEnumTranslationKey('label', $enumCode),
                [],
                null,
                $locale
            )
            ->will($this->returnValue($oldEnumName));

        $this->translationHelper->expects($this->once())
            ->method('saveTranslations')
            ->with([
                ExtendHelper::getEnumTranslationKey('label', $enumCode) => $enumName,
                ExtendHelper::getEnumTranslationKey('plural_label', $enumCode) => $enumName,
            ]);

        $this->synchronizer->applyEnumNameTrans($enumCode, $enumName, $locale);
    }

    public function testApplyEnumNameTransNoTrans()
    {
        $enumCode = 'test_enum';
        $enumName = 'Test Enum New';
        $locale = 'fr';

        $this->translator->expects($this->once())
            ->method('trans')
            ->with(
                ExtendHelper::getEnumTranslationKey('label', $enumCode),
                [],
                null,
                $locale
            )
            ->will($this->returnValue(ExtendHelper::getEnumTranslationKey('label', $enumCode)));

        $this->translationHelper->expects($this->once())
            ->method('saveTranslations')
            ->with([
                ExtendHelper::getEnumTranslationKey('label', $enumCode) => $enumName,
                ExtendHelper::getEnumTranslationKey('plural_label', $enumCode) => $enumName,
            ]);

        $this->synchronizer->applyEnumNameTrans($enumCode, $enumName, $locale);
    }

    public function testApplyEnumNameTransNoTransForDefaultLocale()
    {
        $enumCode = 'test_enum';
        $enumName = 'Test Enum New';
        $locale = Translator::DEFAULT_LOCALE;

        $this->translator->expects($this->once())
            ->method('trans')
            ->with(
                ExtendHelper::getEnumTranslationKey('label', $enumCode),
                [],
                null,
                $locale
            )
            ->will($this->returnValue(ExtendHelper::getEnumTranslationKey('label', $enumCode)));

        $this->translationHelper->expects($this->once())
            ->method('saveTranslations')
            ->with([
                ExtendHelper::getEnumTranslationKey('label', $enumCode) => $enumName,
                ExtendHelper::getEnumTranslationKey('plural_label', $enumCode) => $enumName,
                ExtendHelper::getEnumTranslationKey('description', $enumCode) => '',
            ]);

        $this->synchronizer->applyEnumNameTrans($enumCode, $enumName, $locale);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage $enumValueClassName must not be empty.
     */
    public function testApplyEnumEntityOptionsWithEmptyClassName()
    {
        $this->synchronizer->applyEnumEntityOptions('', false);
    }

    public function testApplyEnumEntityOptionsNoChanges()
    {
        $enumValueClassName = 'Test\EnumValue';
        $isPublic = true;

        $enumConfig = new Config(new EntityConfigId('enum', $enumValueClassName));
        $enumConfig->set('public', $isPublic);

        $enumConfigProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('enum')
            ->will($this->returnValue($enumConfigProvider));
        $enumConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($enumValueClassName)
            ->will($this->returnValue($enumConfig));
        $this->configManager->expects($this->never())
            ->method('persist');

        $this->synchronizer->applyEnumEntityOptions($enumValueClassName, $isPublic);
    }

    public function testApplyEnumEntityOptionsNoFlush()
    {
        $enumValueClassName = 'Test\EnumValue';
        $isPublic = false;

        $enumConfig = new Config(new EntityConfigId('enum', $enumValueClassName));
        $enumConfig->set('public', !$isPublic);

        $enumConfigProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('enum')
            ->will($this->returnValue($enumConfigProvider));
        $enumConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($enumValueClassName)
            ->will($this->returnValue($enumConfig));
        $this->configManager->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($enumConfig));
        $this->configManager->expects($this->never())
            ->method('flush');

        $this->synchronizer->applyEnumEntityOptions($enumValueClassName, $isPublic, false);

        $this->assertEquals(
            $isPublic,
            $enumConfig->get('public')
        );
    }

    public function testApplyEnumEntityOptions()
    {
        $enumValueClassName = 'Test\EnumValue';
        $isPublic = true;

        $enumConfig = new Config(new EntityConfigId('enum', $enumValueClassName));

        $enumConfigProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('enum')
            ->will($this->returnValue($enumConfigProvider));
        $enumConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($enumValueClassName)
            ->will($this->returnValue($enumConfig));
        $this->configManager->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($enumConfig));
        $this->configManager->expects($this->once())
            ->method('flush');

        $this->synchronizer->applyEnumEntityOptions($enumValueClassName, $isPublic);

        $this->assertEquals(
            $isPublic,
            $enumConfig->get('public')
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage $enumValueClassName must not be empty.
     */
    public function testApplyEnumOptionsWithEmptyClassName()
    {
        $this->synchronizer->applyEnumOptions('', [], 'en');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage $locale must not be empty.
     */
    public function testApplyEnumOptionsWithEmptyLocale()
    {
        $this->synchronizer->applyEnumOptions('Test\EnumValue', [], null);
    }

    public function testApplyEnumOptionsEmpty()
    {
        $enumValueClassName = 'Test\EnumValue';
        $locale = 'fr';

        $enumOptions = [];
        $values = [];

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects($this->once())
            ->method('commit');
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with($enumValueClassName)
            ->will($this->returnValue($em));

        $this->setApplyEnumOptionsQueryExpectation($em, $enumValueClassName, $locale, $values);

        $em->expects($this->never())
            ->method('flush');
        $this->translationHelper->expects($this->never())
            ->method('invalidateCache');

        $this->synchronizer->applyEnumOptions($enumValueClassName, $enumOptions, $locale);
    }

    public function testApplyEnumOptionsTransactionError()
    {
        $enumValueClassName = 'Test\EnumValue';
        $locale = 'fr';

        $values = [
            new TestEnumValue('opt1', 'Option 1', 1, true)
        ];

        $em = $this->createMock(EntityManager::class);
        $this->setApplyEnumOptionsQueryExpectation($em, $enumValueClassName, $locale, $values);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects($this->never())
            ->method('commit');
        $em->expects($this->once())
            ->method('rollback');
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with($enumValueClassName)
            ->will($this->returnValue($em));

        $em->expects($this->once())
            ->method('flush')
            ->willThrowException(new \Exception());

        $this->expectException(\Exception::class);
        $this->translationHelper->expects($this->never())
            ->method('invalidateCache');

        $this->synchronizer->applyEnumOptions($enumValueClassName, [], $locale);
    }

    public function testApplyEnumOptionsNoChanges()
    {
        $enumValueClassName = 'Test\EnumValue';
        $locale = 'fr';

        $enumOptions = [
            ['id' => 'opt1', 'label' => 'Option 1', 'priority' => 1, 'is_default' => true]
        ];
        $values = [
            new TestEnumValue('opt1', 'Option 1', 1, true)
        ];

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects($this->once())
            ->method('commit');
        $em->expects($this->never())
            ->method('rollback');
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with($enumValueClassName)
            ->will($this->returnValue($em));

        $this->setApplyEnumOptionsQueryExpectation($em, $enumValueClassName, $locale, $values);

        $em->expects($this->never())
            ->method('flush');
        $this->translationHelper->expects($this->never())
            ->method('invalidateCache');

        $this->synchronizer->applyEnumOptions($enumValueClassName, $enumOptions, $locale);
    }

    public function testApplyEnumOptions()
    {
        $enumValueClassName = 'Test\EnumValue';
        $locale = 'fr';

        $enumOptions = [
            ['id' => 'opt1', 'label' => 'Option 1', 'priority' => 1, 'is_default' => true],
            ['id' => 'opt2', 'label' => 'Option 2', 'priority' => 2, 'is_default' => false],
            ['id' => 'opt5', 'label' => 'Option 5', 'priority' => 3, 'is_default' => false],
            ['id' => '', 'label' => 'Option 4', 'priority' => 4, 'is_default' => true],
        ];

        $value1 = new TestEnumValue('opt1', 'Option 1', 1, true);
        $value2 = new TestEnumValue('opt2', 'Option 2 old', 4, true);
        $value3 = new TestEnumValue('opt3', 'Option 3', 3, false);
        $value5 = new TestEnumValue('opt5', 'Option 5', 2, false);

        $newValue = new TestEnumValue('opt4', 'Option 4', 4, true);

        $values = [$value1, $value2, $value3, $value5];

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects($this->once())
            ->method('commit');
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with($enumValueClassName)
            ->will($this->returnValue($em));

        $enumRepo = $this->setApplyEnumOptionsQueryExpectation($em, $enumValueClassName, $locale, $values);

        $em->expects($this->once())
            ->method('remove')
            ->with($this->identicalTo($value3));
        $enumRepo->expects($this->once())
            ->method('createEnumValue')
            ->with('Option 4', 4, true, 'option_4')
            ->will($this->returnValue($newValue));
        $em->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($newValue));
        $em->expects($this->never())
            ->method('rollback');

        $em->expects($this->exactly(2))
            ->method('flush');
        $this->translationHelper->expects($this->once())
            ->method('invalidateCache')
            ->with($locale);

        $this->synchronizer->applyEnumOptions($enumValueClassName, $enumOptions, $locale);

        $expectedValue1 = new TestEnumValue('opt1', 'Option 1', 1, true);
        $this->assertEquals($expectedValue1, $value1);
        $expectedValue2 = new TestEnumValue('opt2', 'Option 2', 2, false);
        $expectedValue2->setLocale($locale);
        $this->assertEquals($expectedValue2, $value2);
        $expectedValue5 = new TestEnumValue('opt5', 'Option 5', 3, false);
        $expectedValue5->setLocale($locale);
        $this->assertEquals($expectedValue5, $value5);
        $expectedNewValue = new TestEnumValue('opt4', 'Option 4', 4, true);
        $expectedNewValue->setLocale($locale);
        $this->assertEquals($expectedNewValue, $newValue);
    }

    public function testApplyEnumOptionsOptionsInDifferentCase()
    {
        $enumValueClassName = 'Test\EnumValue';
        $locale = 'fr';

        $enumOptions = [
            ['id' => '', 'label' => 'Option 1', 'priority' => 1, 'is_default' => true],
            ['id' => '', 'label' => 'OPTION 1', 'priority' => 2, 'is_default' => false],
        ];

        $value = new TestEnumValue('option_1', 'Option 1', 1, true);
        $newValue = new TestEnumValue('option_1_1', 'OPTION 1', 2, false);

        $values = [$value];

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects($this->once())
            ->method('commit');
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with($enumValueClassName)
            ->will($this->returnValue($em));

        $enumRepo = $this->setApplyEnumOptionsQueryExpectation($em, $enumValueClassName, $locale, $values);

        $em->expects($this->never())
            ->method('remove');
        $enumRepo->expects($this->once())
            ->method('createEnumValue')
            ->with('OPTION 1', 2, false, 'option_1_1')
            ->will($this->returnValue($newValue));
        $em->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($newValue));
        $em->expects($this->never())
            ->method('rollback');

        $em->expects($this->once())
            ->method('flush');
        $this->translationHelper->expects($this->once())
            ->method('invalidateCache')
            ->with($locale);

        $this->synchronizer->applyEnumOptions($enumValueClassName, $enumOptions, $locale);

        $expectedValue1 = new TestEnumValue('option_1', 'Option 1', 1, true);
        $this->assertEquals($expectedValue1, $value);

        $expectedNewValue = new TestEnumValue('option_1_1', 'OPTION 1', 2, false);
        $expectedNewValue->setLocale($locale);
        $this->assertEquals($expectedNewValue, $newValue);
    }

    public function testApplyEnumOptionsOptionsInDifferentCaseForExistingValues()
    {
        $enumValueClassName = 'Test\EnumValue';
        $locale = 'fr';

        $value1 = new TestEnumValue('value', 'value', 1, true);
        $value2 = new TestEnumValue('value_1', 'VALUE', 2, false);
        $values = [$value1, $value2];

        $enumOptions = [
            ['id' => '', 'label' => 'Value', 'priority' => 1, 'is_default' => true],
            ['id' => '', 'label' => 'value', 'priority' => 2, 'is_default' => false],
            ['id' => '', 'label' => 'vALUE', 'priority' => 3, 'is_default' => false],
        ];

        $newValue1 = new TestEnumValue('value_2', 'Value', 1, true);
        $newValue2 = new TestEnumValue('value_3', 'vALUE', 3, false);

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects($this->once())
            ->method('commit');
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with($enumValueClassName)
            ->will($this->returnValue($em));

        $enumRepo = $this->setApplyEnumOptionsQueryExpectation($em, $enumValueClassName, $locale, $values);

        $em->expects($this->once())
            ->method('remove')
            ->with($value2);
        $enumRepo->expects($this->exactly(2))
            ->method('createEnumValue')
            ->withConsecutive(
                ['Value', 1, true, 'value_2'],
                ['vALUE', 3, false, 'value_3']
            )
            ->willReturnOnConsecutiveCalls(
                $newValue1,
                $newValue2
            );
        $em->expects($this->exactly(2))
            ->method('persist')
            ->withConsecutive(
                [$newValue1],
                [$newValue2]
            );
        $em->expects($this->never())
            ->method('rollback');

        $em->expects($this->exactly(2))
            ->method('flush');
        $this->translationHelper->expects($this->once())
            ->method('invalidateCache')
            ->with($locale);

        $this->synchronizer->applyEnumOptions($enumValueClassName, $enumOptions, $locale);

        $expectedValue = new TestEnumValue('value', 'value', 2, false);
        $expectedValue->setLocale($locale);
        $this->assertEquals($expectedValue, $value1);

        $expectedNewValue1 = new TestEnumValue('value_2', 'Value', 1, true);
        $expectedNewValue1->setLocale($locale);
        $this->assertEquals($expectedNewValue1, $newValue1);

        $expectedNewValue2 = new TestEnumValue('value_3', 'vALUE', 3, false);
        $expectedNewValue2->setLocale($locale);
        $this->assertEquals($expectedNewValue2, $newValue2);
    }

    public function testApplyEnumOptionsWithDuplicatedIds()
    {
        $enumValueClassName = 'Test\EnumValue';
        $locale = 'fr';

        $enumOptions = [
            ['id' => '', 'label' => '0', 'priority' => 1, 'is_default' => true],
            ['id' => '', 'label' => '*0*', 'priority' => 2, 'is_default' => false],
            ['id' => '', 'label' => '**0**', 'priority' => 3, 'is_default' => false],
            ['id' => '', 'label' => '0_1', 'priority' => 4, 'is_default' => false],
        ];

        $newValue1 = new TestEnumValue('0', '0', 1, true);
        $newValue2 = new TestEnumValue('0_1', '*0*', 2, false);
        $newValue3 = new TestEnumValue('0_2', '**0**', 3, false);
        $newValue4 = new TestEnumValue('0_1_1', '0_1', 4, false);

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects($this->once())
            ->method('commit');
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with($enumValueClassName)
            ->will($this->returnValue($em));

        $enumRepo = $this->setApplyEnumOptionsQueryExpectation($em, $enumValueClassName, $locale, []);

        $enumRepo->expects($this->exactly(4))
            ->method('createEnumValue')
            ->withConsecutive(
                ['0', 1, true, '0'],
                ['*0*', 2, false, '0_1'],
                ['**0**', 3, false, '0_2'],
                ['0_1', 4, false, '0_1_1']
            )
            ->willReturnOnConsecutiveCalls(
                $newValue1,
                $newValue2,
                $newValue3,
                $newValue4
            );
        $em->expects($this->exactly(4))
            ->method('persist')
            ->withConsecutive(
                $newValue1,
                $newValue2,
                $newValue3,
                $newValue4
            );

        $em->expects($this->once())
            ->method('flush');
        $em->expects($this->never())
            ->method('rollback');
        $this->translationHelper->expects($this->once())
            ->method('invalidateCache')
            ->with($locale);

        $this->synchronizer->applyEnumOptions($enumValueClassName, $enumOptions, $locale);

        $expectedNewValue1 = new TestEnumValue('0', '0', 1, true);
        $expectedNewValue1->setLocale($locale);
        $this->assertEquals($expectedNewValue1, $newValue1);
        $expectedNewValue2 = new TestEnumValue('0_1', '*0*', 2, false);
        $expectedNewValue2->setLocale($locale);
        $this->assertEquals($expectedNewValue2, $newValue2);
        $expectedNewValue3 = new TestEnumValue('0_2', '**0**', 3, false);
        $expectedNewValue3->setLocale($locale);
        $this->assertEquals($expectedNewValue3, $newValue3);
        $expectedNewValue4 = new TestEnumValue('0_1_1', '0_1', 4, false);
        $expectedNewValue4->setLocale($locale);
        $this->assertEquals($expectedNewValue4, $newValue4);
    }

    public function testApplyEnumOptionsWithDuplicatedIdsAndGeneratedIdEqualsRemovingId()
    {
        $enumValueClassName = 'Test\EnumValue';
        $locale = 'fr';

        $value1 = new TestEnumValue('option_1', 'Existing Option 1', 1, true);
        $value2 = new TestEnumValue('option_1_1', 'Existing Option 11', 3, true);
        $value3 = new TestEnumValue('option_1_2', 'Existing Option 12', 2, false);
        $values = [$value1, $value2, $value3];

        $enumOptions = [
            ['id' => 'option_1', 'label' => 'Existing Option 1', 'priority' => 1, 'is_default' => true],
            ['id' => 'option_1_1', 'label' => 'Existing Option 11', 'priority' => 3, 'is_default' => false],
            ['id' => '', 'label' => 'Option 1', 'priority' => 2, 'is_default' => true],
        ];

        $newValue = new TestEnumValue('option_1_3', 'Option 1', 2, true);

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects($this->once())
            ->method('commit');
        $em->expects($this->once())
            ->method('remove')
            ->with($this->identicalTo($value3));
        $em->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($newValue));
        $em->expects($this->exactly(2))
            ->method('flush');
        $em->expects($this->never())
            ->method('rollback');

        $enumRepo = $this->setApplyEnumOptionsQueryExpectation($em, $enumValueClassName, $locale, $values);
        $enumRepo->expects($this->once())
            ->method('createEnumValue')
            ->with('Option 1', 2, true, 'option_1_3')
            ->will($this->returnValue($newValue));

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with($enumValueClassName)
            ->will($this->returnValue($em));

        $this->translationHelper->expects($this->once())
            ->method('invalidateCache')
            ->with($locale);

        $this->synchronizer->applyEnumOptions($enumValueClassName, $enumOptions, $locale);

        $expectedValue1 = new TestEnumValue('option_1', 'Existing Option 1', 1, true);
        $this->assertEquals($expectedValue1, $value1);
        $expectedValue2 = new TestEnumValue('option_1_1', 'Existing Option 11', 3, false);
        $expectedValue2->setLocale($locale);
        $this->assertEquals($expectedValue2, $value2);
        $expectedNewValue = new TestEnumValue('option_1_3', 'Option 1', 2, true);
        $expectedNewValue->setLocale($locale);
        $this->assertEquals($expectedNewValue, $newValue);
    }

    public function testApplyEnumOptionsMatchByLabel()
    {
        $enumValueClassName = 'Test\EnumValue';
        $locale = 'fr';

        $enumOptions = [
            ['id' => '', 'label' => 'Option 1', 'priority' => 1, 'is_default' => false]
        ];

        $value = new TestEnumValue('option_1', 'Option 1', 2, true);
        $expectedValue = new TestEnumValue('option_1', 'Option 1', 1, false);
        $expectedValue->setLocale($locale);

        $values = [$value];

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects($this->once())
            ->method('commit');

        $em->expects($this->never())
            ->method('remove');
        $em->expects($this->never())
            ->method('persist');
        $em->expects($this->once())
            ->method('flush')
            ->with([$expectedValue]);
        $em->expects($this->never())
            ->method('rollback');

        $enumRepo = $this->setApplyEnumOptionsQueryExpectation($em, $enumValueClassName, $locale, $values);
        $enumRepo->expects($this->never())
            ->method('createEnumValue');

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with($enumValueClassName)
            ->will($this->returnValue($em));

        $this->translationHelper->expects($this->once())
            ->method('invalidateCache')
            ->with($locale);

        $this->synchronizer->applyEnumOptions($enumValueClassName, $enumOptions, $locale);

        $this->assertEquals($expectedValue, $value);
    }

    public function testGetEnumOptions()
    {
        $enumValueClassName = 'Test\EnumValue';
        $values = [['id' => 'opt1']];
        $locale = 'de_DE';

        $em = $this->createMock(EntityManager::class);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with($enumValueClassName)
            ->will($this->returnValue($em));
        $enumRepo = $this->createMock(EnumValueRepository::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with($enumValueClassName)
            ->will($this->returnValue($enumRepo));
        $qb = $this->createMock(QueryBuilder::class);
        $enumRepo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('e')
            ->will($this->returnValue($qb));
        $query = $this->getMockBuilder(AbstractQuery::class)
            ->disableOriginalConstructor()
            ->setMethods(['setHint', 'getArrayResult'])
            ->getMockForAbstractClass();
        $qb->expects($this->once())
            ->method('select')
            ->with('e.id, e.priority, e.name as label, e.default as is_default')
            ->will($this->returnSelf());
        $qb->expects($this->once())
            ->method('orderBy')
            ->with('e.priority')
            ->will($this->returnSelf());
        $qb->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($query));
        $query->expects($this->exactly(2))
            ->method('setHint')
            ->withConsecutive(
                [
                    Query::HINT_CUSTOM_OUTPUT_WALKER,
                    TranslationWalker::class
                ],
                [
                    TranslatableListener::HINT_TRANSLATABLE_LOCALE,
                    $locale
                ]
            )
            ->will($this->returnSelf());
        $query->expects($this->once())
            ->method('getArrayResult')
            ->will($this->returnValue($values));

        $translatableListener = $this->createMock(TranslatableListener::class);
        $translatableListener->expects($this->once())
            ->method('getListenerLocale')
            ->willReturn($locale);

        $eventManager = $this->createMock(EventManager::class);
        $eventManager->expects($this->any())
            ->method('getListeners')
            ->willReturn([[$translatableListener]]);

        $em->expects($this->any())
            ->method('getEventManager')
            ->willReturn($eventManager);

        $result = $this->synchronizer->getEnumOptions($enumValueClassName);

        $this->assertEquals($values, $result);
    }

    /**
     * @param \PHPUnit\Framework\MockObject\MockObject $em
     * @param string $enumValueClassName
     * @param string $locale
     * @param array $values
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function setApplyEnumOptionsQueryExpectation($em, $enumValueClassName, $locale, $values)
    {
        $enumRepo = $this->createMock(EnumValueRepository::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with($enumValueClassName)
            ->will($this->returnValue($enumRepo));
        $qb = $this->createMock(QueryBuilder::class);
        $enumRepo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('o')
            ->will($this->returnValue($qb));
        $query = $this->getMockBuilder(AbstractQuery::class)
            ->disableOriginalConstructor()
            ->setMethods(['setHint', 'getResult'])
            ->getMockForAbstractClass();
        $qb->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($query));
        $query->expects($this->once())
            ->method('setHint')
            ->with(TranslatableListener::HINT_TRANSLATABLE_LOCALE, $locale)
            ->will($this->returnSelf());
        $query->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue($values));

        return $enumRepo;
    }
}

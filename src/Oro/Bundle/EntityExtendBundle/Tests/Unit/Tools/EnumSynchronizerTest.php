<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools;

use Doctrine\Common\EventManager;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
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
use Oro\Component\DoctrineUtils\ORM\Walker\TranslatableSqlWalker;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class EnumSynchronizerTest extends \PHPUnit\Framework\TestCase
{
    private ConfigManager|\PHPUnit\Framework\MockObject\MockObject $configManager;

    private ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $doctrine;

    private TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject $translator;

    private ConfigTranslationHelper|\PHPUnit\Framework\MockObject\MockObject $translationHelper;

    private EnumSynchronizer $synchronizer;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translationHelper = $this->createMock(ConfigTranslationHelper::class);

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
    public function testSyncNoChanges(string $enumType): void
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
            ->willReturnMap([
                ['extend', $extendConfigProvider],
                ['enum', $enumConfigProvider],
            ]);
        $extendConfigProvider->expects($this->exactly(2))
            ->method('getConfigs')
            ->withConsecutive([null], [$config1->getId()->getClassName()])
            ->willReturnOnConsecutiveCalls($configs, $fieldConfigs);
        $enumConfigProvider->expects(self::once())
            ->method('getConfig')
            ->with('Test\Entity1', 'field1')
            ->willReturn($enumFieldConfig1);

        $synchronizer = $this->getMockBuilder(EnumSynchronizer::class)
            ->onlyMethods(['applyEnumNameTrans', 'applyEnumOptions', 'applyEnumEntityOptions', 'updateEnumFieldConfig'])
            ->setConstructorArgs([
                $this->configManager,
                $this->doctrine,
                $this->translator,
                $this->translationHelper,
            ])
            ->getMock();

        $synchronizer->expects(self::never())
            ->method('updateEnumFieldConfig');

        $synchronizer->sync();
    }

    /**
     * @dataProvider enumTypeProvider
     */
    public function testSyncForAlreadySynchronizedField(string $enumType): void
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
            ->willReturnMap([
                ['extend', $extendConfigProvider],
                ['enum', $enumConfigProvider],
            ]);
        $extendConfigProvider->expects($this->exactly(2))
            ->method('getConfigs')
            ->withConsecutive([null], [$entityConfig->getId()->getClassName()])
            ->willReturnOnConsecutiveCalls([$entityConfig], [$fieldConfig]);
        $enumConfigProvider->expects(self::once())
            ->method('getConfig')
            ->with('Test\Entity1', 'field1')
            ->willReturn($enumFieldConfig);
        $this->configManager->expects(self::never())
            ->method('persist');

        $synchronizer = $this->getMockBuilder(EnumSynchronizer::class)
            ->onlyMethods(['applyEnumNameTrans', 'applyEnumOptions', 'applyEnumEntityOptions'])
            ->setConstructorArgs([
                $this->configManager,
                $this->doctrine,
                $this->translator,
                $this->translationHelper,
            ])
            ->getMock();

        $synchronizer->expects(self::never())
            ->method('applyEnumNameTrans');
        $synchronizer->expects(self::never())
            ->method('applyEnumOptions');
        $synchronizer->expects(self::never())
            ->method('applyEnumEntityOptions');

        $synchronizer->sync();
    }

    /**
     * @dataProvider enumTypeProvider
     */
    public function testSyncForNewField(string $enumType): void
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
            ->willReturnMap([
                ['extend', $extendConfigProvider],
                ['enum', $enumConfigProvider],
            ]);
        $extendConfigProvider->expects($this->exactly(2))
            ->method('getConfigs')
            ->withConsecutive([null], [$entityConfig->getId()->getClassName()])
            ->willReturnOnConsecutiveCalls([$entityConfig], [$fieldConfig]);
        $enumConfigProvider->expects(self::once())
            ->method('getConfig')
            ->with('Test\Entity1', 'field1')
            ->willReturn($enumFieldConfig);
        $this->configManager->expects(self::once())
            ->method('persist')
            ->with($this->identicalTo($enumFieldConfig));
        $this->configManager->expects(self::once())
            ->method('flush');

        $synchronizer = $this->getMockBuilder(EnumSynchronizer::class)
            ->onlyMethods(['applyEnumNameTrans', 'applyEnumOptions', 'applyEnumEntityOptions'])
            ->setConstructorArgs([
                $this->configManager,
                $this->doctrine,
                $this->translator,
                $this->translationHelper,
            ])
            ->getMock();

        $synchronizer->expects(self::once())
            ->method('applyEnumNameTrans')
            ->with($enumCode, $enumName, $locale);
        $synchronizer->expects(self::once())
            ->method('applyEnumOptions')
            ->with($enumValueClassName, $enumOptions, $locale);
        $synchronizer->expects(self::once())
            ->method('applyEnumEntityOptions')
            ->with($enumValueClassName, $enumPublic, false);

        $synchronizer->sync();

        self::assertEquals($expectedEnumFieldConfig, $enumFieldConfig);
    }

    public function enumTypeProvider(): array
    {
        return [
            ['enum'],
            ['multiEnum'],
        ];
    }

    public function testApplyEnumNameTransNoChanges(): void
    {
        $enumCode = 'test_enum';
        $enumName = 'Test Enum';
        $locale = 'fr';

        $this->translator->expects(self::once())
            ->method('trans')
            ->with(
                ExtendHelper::getEnumTranslationKey('label', $enumCode),
                [],
                null,
                $locale
            )
            ->willReturn($enumName);

        $this->doctrine->expects(self::never())
            ->method('getManagerForClass');
        $this->translationHelper->expects(self::never())
            ->method('saveTranslations')
            ->with([]);

        $this->synchronizer->applyEnumNameTrans($enumCode, $enumName, $locale);
    }

    public function testApplyEnumNameTransEnumNameChanged(): void
    {
        $enumCode = 'test_enum';
        $enumName = 'Test Enum New';
        $locale = 'fr';

        $oldEnumName = 'Test Enum';

        $this->translator->expects(self::once())
            ->method('trans')
            ->with(
                ExtendHelper::getEnumTranslationKey('label', $enumCode),
                [],
                null,
                $locale
            )
            ->willReturn($oldEnumName);

        $this->translationHelper->expects(self::once())
            ->method('saveTranslations')
            ->with([
                ExtendHelper::getEnumTranslationKey('label', $enumCode) => $enumName,
                ExtendHelper::getEnumTranslationKey('plural_label', $enumCode) => $enumName,
                ExtendHelper::getEnumTranslationKey('description', $enumCode) => $enumName,
            ]);

        $this->synchronizer->applyEnumNameTrans($enumCode, $enumName, $locale);
    }

    public function testApplyEnumNameTransNoTrans(): void
    {
        $enumCode = 'test_enum';
        $enumName = 'Test Enum New';
        $locale = 'fr';

        $this->translator->expects(self::once())
            ->method('trans')
            ->with(
                ExtendHelper::getEnumTranslationKey('label', $enumCode),
                [],
                null,
                $locale
            )
            ->willReturn(ExtendHelper::getEnumTranslationKey('label', $enumCode));

        $this->translationHelper->expects(self::once())
            ->method('saveTranslations')
            ->with([
                ExtendHelper::getEnumTranslationKey('label', $enumCode) => $enumName,
                ExtendHelper::getEnumTranslationKey('plural_label', $enumCode) => $enumName,
                ExtendHelper::getEnumTranslationKey('description', $enumCode) => $enumName,
            ]);

        $this->synchronizer->applyEnumNameTrans($enumCode, $enumName, $locale);
    }

    public function testApplyEnumNameTransNoTransForDefaultLocale(): void
    {
        $enumCode = 'test_enum';
        $enumName = 'Test Enum New';
        $locale = Translator::DEFAULT_LOCALE;

        $this->translator->expects(self::once())
            ->method('trans')
            ->with(
                ExtendHelper::getEnumTranslationKey('label', $enumCode),
                [],
                null,
                $locale
            )
            ->willReturn(ExtendHelper::getEnumTranslationKey('label', $enumCode));

        $this->translationHelper->expects(self::once())
            ->method('saveTranslations')
            ->with([
                ExtendHelper::getEnumTranslationKey('label', $enumCode) => $enumName,
                ExtendHelper::getEnumTranslationKey('plural_label', $enumCode) => $enumName,
                ExtendHelper::getEnumTranslationKey('description', $enumCode) => $enumName,
            ]);

        $this->synchronizer->applyEnumNameTrans($enumCode, $enumName, $locale);
    }

    public function testApplyEnumEntityOptionsWithEmptyClassName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$enumValueClassName must not be empty.');

        $this->synchronizer->applyEnumEntityOptions('', false);
    }

    public function testApplyEnumEntityOptionsNoChanges(): void
    {
        $enumValueClassName = 'Test\EnumValue';
        $isPublic = true;

        $enumConfig = new Config(new EntityConfigId('enum', $enumValueClassName));
        $enumConfig->set('public', $isPublic);

        $enumConfigProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects(self::once())
            ->method('getProvider')
            ->with('enum')
            ->willReturn($enumConfigProvider);
        $enumConfigProvider->expects(self::once())
            ->method('getConfig')
            ->with($enumValueClassName)
            ->willReturn($enumConfig);
        $this->configManager->expects(self::never())
            ->method('persist');

        $this->synchronizer->applyEnumEntityOptions($enumValueClassName, $isPublic);
    }

    public function testApplyEnumEntityOptionsNoFlush(): void
    {
        $enumValueClassName = 'Test\EnumValue';
        $isPublic = false;

        $enumConfig = new Config(new EntityConfigId('enum', $enumValueClassName));
        $enumConfig->set('public', !$isPublic);

        $enumConfigProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects(self::once())
            ->method('getProvider')
            ->with('enum')
            ->willReturn($enumConfigProvider);
        $enumConfigProvider->expects(self::once())
            ->method('getConfig')
            ->with($enumValueClassName)
            ->willReturn($enumConfig);
        $this->configManager->expects(self::once())
            ->method('persist')
            ->with($this->identicalTo($enumConfig));
        $this->configManager->expects(self::never())
            ->method('flush');

        $this->synchronizer->applyEnumEntityOptions($enumValueClassName, $isPublic, false);

        self::assertEquals(
            $isPublic,
            $enumConfig->get('public')
        );
    }

    public function testApplyEnumEntityOptions(): void
    {
        $enumValueClassName = 'Test\EnumValue';
        $isPublic = true;

        $enumConfig = new Config(new EntityConfigId('enum', $enumValueClassName));

        $enumConfigProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects(self::once())
            ->method('getProvider')
            ->with('enum')
            ->willReturn($enumConfigProvider);
        $enumConfigProvider->expects(self::once())
            ->method('getConfig')
            ->with($enumValueClassName)
            ->willReturn($enumConfig);
        $this->configManager->expects(self::once())
            ->method('persist')
            ->with($this->identicalTo($enumConfig));
        $this->configManager->expects(self::once())
            ->method('flush');

        $this->synchronizer->applyEnumEntityOptions($enumValueClassName, $isPublic);

        self::assertEquals(
            $isPublic,
            $enumConfig->get('public')
        );
    }

    public function testApplyEnumOptionsWithEmptyClassName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$enumValueClassName must not be empty.');

        $this->synchronizer->applyEnumOptions('', [], 'en');
    }

    public function testApplyEnumOptionsWithEmptyLocale(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$locale must not be empty.');

        $this->synchronizer->applyEnumOptions('Test\EnumValue', [], null);
    }

    public function testApplyEnumOptionsEmpty(): void
    {
        $enumValueClassName = 'Test\EnumValue';
        $locale = 'fr';

        $enumOptions = [];
        $values = [];

        $em = $this->createMock(EntityManager::class);
        $em->expects(self::once())
            ->method('beginTransaction');
        $em->expects(self::once())
            ->method('commit');
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with($enumValueClassName)
            ->willReturn($em);

        $this->setApplyEnumOptionsQueryExpectation($em, $enumValueClassName, $locale, $values);

        $em->expects(self::never())
            ->method('flush');
        $this->translationHelper->expects(self::never())
            ->method('invalidateCache');

        $this->synchronizer->applyEnumOptions($enumValueClassName, $enumOptions, $locale);
    }

    public function testApplyEnumOptionsTransactionError(): void
    {
        $enumValueClassName = 'Test\EnumValue';
        $locale = 'fr';

        $values = [
            new TestEnumValue('opt1', 'Option 1', 1, true),
        ];

        $em = $this->createMock(EntityManager::class);
        $this->setApplyEnumOptionsQueryExpectation($em, $enumValueClassName, $locale, $values);
        $em->expects(self::once())
            ->method('beginTransaction');
        $em->expects(self::never())
            ->method('commit');
        $em->expects(self::once())
            ->method('rollback');
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with($enumValueClassName)
            ->willReturn($em);

        $em->expects(self::once())
            ->method('flush')
            ->willThrowException(new \Exception());

        $this->expectException(\Exception::class);
        $this->translationHelper->expects(self::never())
            ->method('invalidateCache');

        $this->synchronizer->applyEnumOptions($enumValueClassName, [], $locale);
    }

    public function testApplyEnumOptionsNoChanges(): void
    {
        $enumValueClassName = 'Test\EnumValue';
        $locale = 'fr';

        $enumOptions = [
            ['id' => 'opt1', 'label' => 'Option 1', 'priority' => 1, 'is_default' => true],
        ];
        $values = [
            new TestEnumValue('opt1', 'Option 1', 1, true),
        ];

        $em = $this->createMock(EntityManager::class);
        $em->expects(self::once())
            ->method('beginTransaction');
        $em->expects(self::once())
            ->method('commit');
        $em->expects(self::never())
            ->method('rollback');
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with($enumValueClassName)
            ->willReturn($em);

        $this->setApplyEnumOptionsQueryExpectation($em, $enumValueClassName, $locale, $values);

        $em->expects(self::never())
            ->method('flush');
        $this->translationHelper->expects(self::never())
            ->method('invalidateCache');

        $this->synchronizer->applyEnumOptions($enumValueClassName, $enumOptions, $locale);
    }

    public function testApplyEnumOptions(): void
    {
        $enumValueClassName = 'Test\EnumValue';
        $locale = 'fr';

        $enumOptions = [
            ['id' => 'opt1', 'label' => 'Option 1', 'priority' => 1, 'is_default' => true],
            ['id' => 'opt2', 'label' => 'Option 2', 'priority' => 2, 'is_default' => false],
            ['id' => 'opt5', 'label' => 'Option 5', 'priority' => 3, 'is_default' => false],
            ['id' => '', 'label' => 'Option 4', 'priority' => 4, 'is_default' => true],
            ['id' => '0025', 'label' => '0.025', 'priority' => 5, 'is_default' => false],
            ['id' => '025', 'label' => '0.25', 'priority' => 6, 'is_default' => false],
        ];

        $value1 = new TestEnumValue('opt1', 'Option 1', 1, true);
        $value2 = new TestEnumValue('opt2', 'Option 2 old', 4, true);
        $value3 = new TestEnumValue('opt3', 'Option 3', 3, false);
        $value5 = new TestEnumValue('opt5', 'Option 5', 2, false);
        $value6 = new TestEnumValue('025', '0.25', 5, false);
        $value7 = new TestEnumValue('0025', '0.025', 6, false);

        $newValue = new TestEnumValue('opt4', 'Option 4', 4, true);

        $values = [$value1, $value2, $value3, $value5, $value6, $value7];

        $em = $this->createMock(EntityManager::class);
        $em->expects(self::once())
            ->method('beginTransaction');
        $em->expects(self::once())
            ->method('commit');
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with($enumValueClassName)
            ->willReturn($em);

        $enumRepo = $this->setApplyEnumOptionsQueryExpectation($em, $enumValueClassName, $locale, $values);

        $em->expects(self::once())
            ->method('remove')
            ->with($this->identicalTo($value3));
        $enumRepo->expects(self::once())
            ->method('createEnumValue')
            ->with('Option 4', 4, true, 'option_4')
            ->willReturn($newValue);
        $em->expects(self::once())
            ->method('persist')
            ->with($this->identicalTo($newValue));
        $em->expects(self::never())
            ->method('rollback');

        $em->expects($this->exactly(2))
            ->method('flush');
        $this->translationHelper->expects(self::once())
            ->method('invalidateCache')
            ->with($locale);

        $this->synchronizer->applyEnumOptions($enumValueClassName, $enumOptions, $locale);

        $expectedValue1 = new TestEnumValue('opt1', 'Option 1', 1, true);
        self::assertEquals($expectedValue1, $value1);
        $expectedValue2 = new TestEnumValue('opt2', 'Option 2', 2, false);
        $expectedValue2->setLocale($locale);
        self::assertEquals($expectedValue2, $value2);
        $expectedValue5 = new TestEnumValue('opt5', 'Option 5', 3, false);
        $expectedValue5->setLocale($locale);
        self::assertEquals($expectedValue5, $value5);
        $expectedNewValue = new TestEnumValue('opt4', 'Option 4', 4, true);
        $expectedNewValue->setLocale($locale);
        self::assertEquals($expectedNewValue, $newValue);
        $expectedValue6 = new TestEnumValue('025', '0.25', 6, false);
        $expectedValue6->setLocale($locale);
        self::assertEquals($expectedValue6, $value6);
        $expectedValue7 = new TestEnumValue('0025', '0.025', 5, false);
        $expectedValue7->setLocale($locale);
        self::assertEquals($expectedValue7, $value7);
    }

    public function testApplyEnumOptionsOptionsInDifferentCase(): void
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
        $em->expects(self::once())
            ->method('beginTransaction');
        $em->expects(self::once())
            ->method('commit');
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with($enumValueClassName)
            ->willReturn($em);

        $enumRepo = $this->setApplyEnumOptionsQueryExpectation($em, $enumValueClassName, $locale, $values);

        $em->expects(self::never())
            ->method('remove');
        $enumRepo->expects(self::once())
            ->method('createEnumValue')
            ->with('OPTION 1', 2, false, 'option_1_1')
            ->willReturn($newValue);
        $em->expects(self::once())
            ->method('persist')
            ->with($this->identicalTo($newValue));
        $em->expects(self::never())
            ->method('rollback');

        $em->expects(self::once())
            ->method('flush');
        $this->translationHelper->expects(self::once())
            ->method('invalidateCache')
            ->with($locale);

        $this->synchronizer->applyEnumOptions($enumValueClassName, $enumOptions, $locale);

        $expectedValue1 = new TestEnumValue('option_1', 'Option 1', 1, true);
        self::assertEquals($expectedValue1, $value);

        $expectedNewValue = new TestEnumValue('option_1_1', 'OPTION 1', 2, false);
        $expectedNewValue->setLocale($locale);
        self::assertEquals($expectedNewValue, $newValue);
    }

    public function testApplyEnumOptionsOptionsInDifferentCaseForExistingValues(): void
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
        $em->expects(self::once())
            ->method('beginTransaction');
        $em->expects(self::once())
            ->method('commit');
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with($enumValueClassName)
            ->willReturn($em);

        $enumRepo = $this->setApplyEnumOptionsQueryExpectation($em, $enumValueClassName, $locale, $values);

        $em->expects(self::once())
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
        $em->expects(self::never())
            ->method('rollback');

        $em->expects($this->exactly(2))
            ->method('flush');
        $this->translationHelper->expects(self::once())
            ->method('invalidateCache')
            ->with($locale);

        $this->synchronizer->applyEnumOptions($enumValueClassName, $enumOptions, $locale);

        $expectedValue = new TestEnumValue('value', 'value', 2, false);
        $expectedValue->setLocale($locale);
        self::assertEquals($expectedValue, $value1);

        $expectedNewValue1 = new TestEnumValue('value_2', 'Value', 1, true);
        $expectedNewValue1->setLocale($locale);
        self::assertEquals($expectedNewValue1, $newValue1);

        $expectedNewValue2 = new TestEnumValue('value_3', 'vALUE', 3, false);
        $expectedNewValue2->setLocale($locale);
        self::assertEquals($expectedNewValue2, $newValue2);
    }

    public function testApplyEnumOptionsWithDuplicatedIds(): void
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
        $em->expects(self::once())
            ->method('beginTransaction');
        $em->expects(self::once())
            ->method('commit');
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with($enumValueClassName)
            ->willReturn($em);

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
                [$newValue1],
                [$newValue2],
                [$newValue3],
                [$newValue4]
            );

        $em->expects(self::once())
            ->method('flush');
        $em->expects(self::never())
            ->method('rollback');
        $this->translationHelper->expects(self::once())
            ->method('invalidateCache')
            ->with($locale);

        $this->synchronizer->applyEnumOptions($enumValueClassName, $enumOptions, $locale);

        $expectedNewValue1 = new TestEnumValue('0', '0', 1, true);
        $expectedNewValue1->setLocale($locale);
        self::assertEquals($expectedNewValue1, $newValue1);
        $expectedNewValue2 = new TestEnumValue('0_1', '*0*', 2, false);
        $expectedNewValue2->setLocale($locale);
        self::assertEquals($expectedNewValue2, $newValue2);
        $expectedNewValue3 = new TestEnumValue('0_2', '**0**', 3, false);
        $expectedNewValue3->setLocale($locale);
        self::assertEquals($expectedNewValue3, $newValue3);
        $expectedNewValue4 = new TestEnumValue('0_1_1', '0_1', 4, false);
        $expectedNewValue4->setLocale($locale);
        self::assertEquals($expectedNewValue4, $newValue4);
    }

    public function testApplyEnumOptionsWithDuplicatedIdsAndGeneratedIdEqualsRemovingId(): void
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
        $em->expects(self::once())
            ->method('beginTransaction');
        $em->expects(self::once())
            ->method('commit');
        $em->expects(self::once())
            ->method('remove')
            ->with($this->identicalTo($value3));
        $em->expects(self::once())
            ->method('persist')
            ->with($this->identicalTo($newValue));
        $em->expects($this->exactly(2))
            ->method('flush');
        $em->expects(self::never())
            ->method('rollback');

        $enumRepo = $this->setApplyEnumOptionsQueryExpectation($em, $enumValueClassName, $locale, $values);
        $enumRepo->expects(self::once())
            ->method('createEnumValue')
            ->with('Option 1', 2, true, 'option_1_3')
            ->willReturn($newValue);

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with($enumValueClassName)
            ->willReturn($em);

        $this->translationHelper->expects(self::once())
            ->method('invalidateCache')
            ->with($locale);

        $this->synchronizer->applyEnumOptions($enumValueClassName, $enumOptions, $locale);

        $expectedValue1 = new TestEnumValue('option_1', 'Existing Option 1', 1, true);
        self::assertEquals($expectedValue1, $value1);
        $expectedValue2 = new TestEnumValue('option_1_1', 'Existing Option 11', 3, false);
        $expectedValue2->setLocale($locale);
        self::assertEquals($expectedValue2, $value2);
        $expectedNewValue = new TestEnumValue('option_1_3', 'Option 1', 2, true);
        $expectedNewValue->setLocale($locale);
        self::assertEquals($expectedNewValue, $newValue);
    }

    public function testApplyEnumOptionsMatchByLabel(): void
    {
        $enumValueClassName = 'Test\EnumValue';
        $locale = 'fr';

        $enumOptions = [
            ['id' => '', 'label' => 'Option 1', 'priority' => 1, 'is_default' => false],
        ];

        $value = new TestEnumValue('option_1', 'Option 1', 2, true);
        $expectedValue = new TestEnumValue('option_1', 'Option 1', 1, false);
        $expectedValue->setLocale($locale);

        $values = [$value];

        $em = $this->createMock(EntityManager::class);
        $em->expects(self::once())
            ->method('beginTransaction');
        $em->expects(self::once())
            ->method('commit');

        $em->expects(self::never())
            ->method('remove');
        $em->expects(self::never())
            ->method('persist');
        $em->expects(self::once())
            ->method('flush')
            ->with([$expectedValue]);
        $em->expects(self::never())
            ->method('rollback');

        $enumRepo = $this->setApplyEnumOptionsQueryExpectation($em, $enumValueClassName, $locale, $values);
        $enumRepo->expects(self::never())
            ->method('createEnumValue');

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with($enumValueClassName)
            ->willReturn($em);

        $this->translationHelper->expects(self::once())
            ->method('invalidateCache')
            ->with($locale);

        $this->synchronizer->applyEnumOptions($enumValueClassName, $enumOptions, $locale);

        self::assertEquals($expectedValue, $value);
    }

    public function testGetEnumOptions(): void
    {
        $enumValueClassName = 'Test\EnumValue';
        $values = [['id' => 'opt1']];
        $locale = 'de_DE';

        $em = $this->createMock(EntityManager::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with($enumValueClassName)
            ->willReturn($em);
        $enumRepo = $this->createMock(EnumValueRepository::class);
        $em->expects(self::once())
            ->method('getRepository')
            ->with($enumValueClassName)
            ->willReturn($enumRepo);
        $qb = $this->createMock(QueryBuilder::class);
        $enumRepo->expects(self::once())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($qb);
        $query = $this->createMock(AbstractQuery::class);
        $qb->expects(self::once())
            ->method('select')
            ->with('e.id, e.priority, e.name as label, e.default as is_default')
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('orderBy')
            ->with('e.priority')
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects($this->exactly(2))
            ->method('setHint')
            ->withConsecutive(
                [Query::HINT_CUSTOM_OUTPUT_WALKER, TranslatableSqlWalker::class],
                [TranslatableListener::HINT_TRANSLATABLE_LOCALE, $locale]
            )
            ->willReturnSelf();
        $query->expects(self::once())
            ->method('getArrayResult')
            ->willReturn($values);

        $translatableListener = $this->createMock(TranslatableListener::class);
        $translatableListener->expects(self::once())
            ->method('getListenerLocale')
            ->willReturn($locale);

        $eventManager = $this->createMock(EventManager::class);
        $eventManager->expects(self::any())
            ->method('getListeners')
            ->willReturn([[$translatableListener]]);

        $em->expects(self::any())
            ->method('getEventManager')
            ->willReturn($eventManager);

        $result = $this->synchronizer->getEnumOptions($enumValueClassName);

        self::assertEquals($values, $result);
    }

    private function setApplyEnumOptionsQueryExpectation(
        EntityManager|\PHPUnit\Framework\MockObject\MockObject $em,
        string $enumValueClassName,
        string $locale,
        array $values
    ): EnumValueRepository|\PHPUnit\Framework\MockObject\MockObject {
        $enumRepo = $this->createMock(EnumValueRepository::class);
        $em->expects(self::once())
            ->method('getRepository')
            ->with($enumValueClassName)
            ->willReturn($enumRepo);
        $qb = $this->createMock(QueryBuilder::class);
        $enumRepo->expects(self::once())
            ->method('createQueryBuilder')
            ->with('o')
            ->willReturn($qb);
        $query = $this->createMock(AbstractQuery::class);
        $qb->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects(self::once())
            ->method('setHint')
            ->with(TranslatableListener::HINT_TRANSLATABLE_LOCALE, $locale)
            ->willReturnSelf();
        $query->expects(self::once())
            ->method('getResult')
            ->willReturn($values);

        return $enumRepo;
    }

    /**
     * @dataProvider fillOptionIdsDataProvider
     */
    public function testFillOptionIds(array $values, array $options, array $expectedOptions): void
    {
        $this->synchronizer->fillOptionIds($values, $options);

        self::assertEquals($expectedOptions, $options);
    }

    public function fillOptionIdsDataProvider(): array
    {
        return [
            'empty values, empty options' => ['values' => [], 'options' => [], 'expectedOptions' => []],
            'empty values' => [
                'values' => [],
                'options' => [['id' => 'sample-id', 'label' => 'Sample Label']],
                'expectedOptions' => [['id' => 'sample-id', 'label' => 'Sample Label']],
            ],
            'missing id and label in value' => [
                'values' => [new TestEnumValue(null, null, 1, true)],
                'options' => [['id' => null, 'label' => null], ['id' => 'sample-id', 'label' => 'Sample Label']],
                'expectedOptions' => [
                    ['id' => null, 'label' => null],
                    ['id' => 'sample-id', 'label' => 'Sample Label'],
                ],
            ],
            'empty id and label in value' => [
                'values' => [new TestEnumValue('', '', 1, true)],
                'options' => [['id' => '', 'label' => ''], ['id' => 'sample-id', 'label' => 'Sample Label']],
                'expectedOptions' => [['id' => '', 'label' => ''], ['id' => 'sample-id', 'label' => 'Sample Label']],
            ],
            'empty id in value' => [
                'values' => [new TestEnumValue('', 'Sample Label', 1, true)],
                'options' => [
                    ['id' => 'sample-new-id', 'label' => 'Sample Label'],
                    ['id' => 'sample-id', 'label' => 'Sample Label'],
                ],
                'expectedOptions' => [
                    ['id' => 'sample-new-id', 'label' => 'Sample Label'],
                    ['id' => 'sample-id', 'label' => 'Sample Label'],
                ],
            ],
            'not empty id in value' => [
                'values' => [new TestEnumValue('sample-new-id', 'Sample Label', 1, true)],
                'options' => [
                    ['id' => 'sample-new-id', 'label' => 'Sample Label'],
                    ['id' => 'sample-id', 'label' => 'Sample Label'],
                ],
                'expectedOptions' => [
                    ['id' => 'sample-new-id', 'label' => 'Sample Label'],
                    ['id' => 'sample-id', 'label' => 'Sample Label'],
                ],
            ],
        ];
    }
}

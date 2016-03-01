<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools;

use Doctrine\ORM\Query;

use Gedmo\Translatable\TranslatableListener;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\EnumSynchronizer;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;
use Oro\Bundle\TranslationBundle\Entity\Translation;

class EnumSynchronizerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrine;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $dbTranslationMetadataCache;

    /** @var EnumSynchronizer */
    protected $synchronizer;

    public function setUp()
    {
        $this->configManager              = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine                   = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->translator                 = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $this->dbTranslationMetadataCache =
            $this->getMockBuilder('Oro\Bundle\TranslationBundle\Translation\DynamicTranslationMetadataCache')
                ->disableOriginalConstructor()
                ->getMock();

        $this->synchronizer = new EnumSynchronizer(
            $this->configManager,
            $this->doctrine,
            $this->translator,
            $this->dbTranslationMetadataCache
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

        $enumConfigProvider   = $this->getConfigProviderMock();
        $extendConfigProvider = $this->getConfigProviderMock();
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

        /** @var EnumSynchronizer|\PHPUnit_Framework_MockObject_MockObject $synchronizer */
        $synchronizer = $this->getMock(
            'Oro\Bundle\EntityExtendBundle\Tools\EnumSynchronizer',
            ['applyEnumNameTrans', 'applyEnumOptions', 'applyEnumEntityOptions', 'updateEnumFieldConfig'],
            [
                $this->configManager,
                $this->doctrine,
                $this->translator,
                $this->dbTranslationMetadataCache
            ]
        );

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
        $fieldConfig     = new Config(new FieldConfigId('extend', 'Test\Entity1', 'field1', $enumType));
        $enumFieldConfig = new Config(new FieldConfigId('enum', 'Test\Entity1', 'field1', $enumType));
        $enumFieldConfig->set('enum_code', $enumCode);

        $enumConfigProvider   = $this->getConfigProviderMock();
        $extendConfigProvider = $this->getConfigProviderMock();
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

        /** @var EnumSynchronizer|\PHPUnit_Framework_MockObject_MockObject $synchronizer */
        $synchronizer = $this->getMock(
            'Oro\Bundle\EntityExtendBundle\Tools\EnumSynchronizer',
            ['applyEnumNameTrans', 'applyEnumOptions', 'applyEnumEntityOptions'],
            [
                $this->configManager,
                $this->doctrine,
                $this->translator,
                $this->dbTranslationMetadataCache
            ]
        );

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
        $enumCode    = 'test_enum';
        $enumName    = 'Test Enum';
        $locale      = 'fr';
        $enumPublic  = true;
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

        $enumConfigProvider   = $this->getConfigProviderMock();
        $extendConfigProvider = $this->getConfigProviderMock();
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

        /** @var EnumSynchronizer|\PHPUnit_Framework_MockObject_MockObject $synchronizer */
        $synchronizer = $this->getMock(
            'Oro\Bundle\EntityExtendBundle\Tools\EnumSynchronizer',
            ['applyEnumNameTrans', 'applyEnumOptions', 'applyEnumEntityOptions'],
            [
                $this->configManager,
                $this->doctrine,
                $this->translator,
                $this->dbTranslationMetadataCache
            ]
        );

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
        $locale   = 'fr';

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
        $this->dbTranslationMetadataCache->expects($this->never())
            ->method('updateTimestamp');

        $this->synchronizer->applyEnumNameTrans($enumCode, $enumName, $locale);
    }

    public function testApplyEnumNameTransEnumNameChanged()
    {
        $enumCode = 'test_enum';
        $enumName = 'Test Enum New';
        $locale   = 'fr';

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

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(Translation::ENTITY_NAME)
            ->will($this->returnValue($em));
        $transRepo = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('getRepository')
            ->with(Translation::ENTITY_NAME)
            ->will($this->returnValue($transRepo));

        $transLabelObj       = new \stdClass();
        $transPluralLabelObj = new \stdClass();

        $transRepo->expects($this->at(0))
            ->method('saveValue')
            ->with(
                ExtendHelper::getEnumTranslationKey('label', $enumCode),
                $enumName,
                $locale,
                TranslationRepository::DEFAULT_DOMAIN,
                Translation::SCOPE_UI
            )
            ->will($this->returnValue($transLabelObj));
        $transRepo->expects($this->at(1))
            ->method('saveValue')
            ->with(
                ExtendHelper::getEnumTranslationKey('plural_label', $enumCode),
                $enumName,
                $locale,
                TranslationRepository::DEFAULT_DOMAIN,
                Translation::SCOPE_UI
            )
            ->will($this->returnValue($transPluralLabelObj));

        $em->expects($this->once())
            ->method('flush')
            ->with($this->identicalTo([$transLabelObj, $transPluralLabelObj]));
        $this->dbTranslationMetadataCache->expects($this->once())
            ->method('updateTimestamp')
            ->with($locale);

        $this->synchronizer->applyEnumNameTrans($enumCode, $enumName, $locale);
    }

    public function testApplyEnumNameTransNoTrans()
    {
        $enumCode = 'test_enum';
        $enumName = 'Test Enum New';
        $locale   = 'fr';

        $this->translator->expects($this->once())
            ->method('trans')
            ->with(
                ExtendHelper::getEnumTranslationKey('label', $enumCode),
                [],
                null,
                $locale
            )
            ->will($this->returnValue(ExtendHelper::getEnumTranslationKey('label', $enumCode)));

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(Translation::ENTITY_NAME)
            ->will($this->returnValue($em));
        $transRepo = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('getRepository')
            ->with(Translation::ENTITY_NAME)
            ->will($this->returnValue($transRepo));

        $transLabelObj       = new \stdClass();
        $transPluralLabelObj = new \stdClass();

        $transRepo->expects($this->at(0))
            ->method('saveValue')
            ->with(
                ExtendHelper::getEnumTranslationKey('label', $enumCode),
                $enumName,
                $locale,
                TranslationRepository::DEFAULT_DOMAIN,
                Translation::SCOPE_UI
            )
            ->will($this->returnValue($transLabelObj));
        $transRepo->expects($this->at(1))
            ->method('saveValue')
            ->with(
                ExtendHelper::getEnumTranslationKey('plural_label', $enumCode),
                $enumName,
                $locale,
                TranslationRepository::DEFAULT_DOMAIN,
                Translation::SCOPE_UI
            )
            ->will($this->returnValue($transPluralLabelObj));

        $em->expects($this->once())
            ->method('flush')
            ->with($this->identicalTo([$transLabelObj, $transPluralLabelObj]));
        $this->dbTranslationMetadataCache->expects($this->once())
            ->method('updateTimestamp')
            ->with($locale);

        $this->synchronizer->applyEnumNameTrans($enumCode, $enumName, $locale);
    }

    public function testApplyEnumNameTransNoTransForDefaultLocale()
    {
        $enumCode = 'test_enum';
        $enumName = 'Test Enum New';
        $locale   = Translation::DEFAULT_LOCALE;

        $this->translator->expects($this->once())
            ->method('trans')
            ->with(
                ExtendHelper::getEnumTranslationKey('label', $enumCode),
                [],
                null,
                $locale
            )
            ->will($this->returnValue(ExtendHelper::getEnumTranslationKey('label', $enumCode)));

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(Translation::ENTITY_NAME)
            ->will($this->returnValue($em));
        $transRepo = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('getRepository')
            ->with(Translation::ENTITY_NAME)
            ->will($this->returnValue($transRepo));

        $transLabelObj       = new \stdClass();
        $transPluralLabelObj = new \stdClass();
        $transDescriptionObj = new \stdClass();

        $transRepo->expects($this->at(0))
            ->method('saveValue')
            ->with(
                ExtendHelper::getEnumTranslationKey('label', $enumCode),
                $enumName,
                $locale,
                TranslationRepository::DEFAULT_DOMAIN,
                Translation::SCOPE_UI
            )
            ->will($this->returnValue($transLabelObj));
        $transRepo->expects($this->at(1))
            ->method('saveValue')
            ->with(
                ExtendHelper::getEnumTranslationKey('plural_label', $enumCode),
                $enumName,
                $locale,
                TranslationRepository::DEFAULT_DOMAIN,
                Translation::SCOPE_UI
            )
            ->will($this->returnValue($transPluralLabelObj));
        $transRepo->expects($this->at(2))
            ->method('saveValue')
            ->with(
                ExtendHelper::getEnumTranslationKey('description', $enumCode),
                '',
                $locale,
                TranslationRepository::DEFAULT_DOMAIN,
                Translation::SCOPE_UI
            )
            ->will($this->returnValue($transDescriptionObj));

        $em->expects($this->once())
            ->method('flush')
            ->with($this->identicalTo([$transLabelObj, $transPluralLabelObj, $transDescriptionObj]));
        $this->dbTranslationMetadataCache->expects($this->once())
            ->method('updateTimestamp')
            ->with($locale);

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
        $isPublic           = true;

        $enumConfig = new Config(new EntityConfigId('enum', $enumValueClassName));
        $enumConfig->set('public', $isPublic);

        $enumConfigProvider = $this->getConfigProviderMock();
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
        $isPublic           = false;

        $enumConfig = new Config(new EntityConfigId('enum', $enumValueClassName));
        $enumConfig->set('public', !$isPublic);

        $enumConfigProvider = $this->getConfigProviderMock();
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
        $isPublic           = true;

        $enumConfig = new Config(new EntityConfigId('enum', $enumValueClassName));

        $enumConfigProvider = $this->getConfigProviderMock();
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
        $locale             = 'fr';

        $enumOptions = [];
        $values      = [];

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with($enumValueClassName)
            ->will($this->returnValue($em));

        $this->setApplyEnumOptionsQueryExpectation($em, $enumValueClassName, $locale, $values);

        $em->expects($this->never())
            ->method('flush');
        $this->dbTranslationMetadataCache->expects($this->never())
            ->method('updateTimestamp');

        $this->synchronizer->applyEnumOptions($enumValueClassName, $enumOptions, $locale);
    }

    public function testApplyEnumOptionsNoChanges()
    {
        $enumValueClassName = 'Test\EnumValue';
        $locale             = 'fr';

        $enumOptions = [
            ['id' => 'opt1', 'label' => 'Option 1', 'priority' => 1, 'is_default' => true]
        ];
        $values      = [
            new TestEnumValue('opt1', 'Option 1', 1, true)
        ];

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with($enumValueClassName)
            ->will($this->returnValue($em));

        $this->setApplyEnumOptionsQueryExpectation($em, $enumValueClassName, $locale, $values);

        $em->expects($this->never())
            ->method('flush');
        $this->dbTranslationMetadataCache->expects($this->never())
            ->method('updateTimestamp');

        $this->synchronizer->applyEnumOptions($enumValueClassName, $enumOptions, $locale);
    }

    public function testApplyEnumOptions()
    {
        $enumValueClassName = 'Test\EnumValue';
        $locale             = 'fr';

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

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
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

        $em->expects($this->once())
            ->method('flush');
        $this->dbTranslationMetadataCache->expects($this->once())
            ->method('updateTimestamp')
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

    public function testApplyEnumOptionsWithDuplicatedIds()
    {
        $enumValueClassName = 'Test\EnumValue';
        $locale             = 'fr';

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

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with($enumValueClassName)
            ->will($this->returnValue($em));

        $enumRepo = $this->setApplyEnumOptionsQueryExpectation($em, $enumValueClassName, $locale, []);

        $enumRepo->expects($this->at(1))
            ->method('createEnumValue')
            ->with('0', 1, true, '0')
            ->will($this->returnValue($newValue1));
        $em->expects($this->at(1))
            ->method('persist')
            ->with($this->identicalTo($newValue1));
        $enumRepo->expects($this->at(2))
            ->method('createEnumValue')
            ->with('*0*', 2, false, '0_1')
            ->will($this->returnValue($newValue2));
        $em->expects($this->at(2))
            ->method('persist')
            ->with($this->identicalTo($newValue2));
        $enumRepo->expects($this->at(3))
            ->method('createEnumValue')
            ->with('**0**', 3, false, '0_2')
            ->will($this->returnValue($newValue3));
        $em->expects($this->at(3))
            ->method('persist')
            ->with($this->identicalTo($newValue3));
        $enumRepo->expects($this->at(4))
            ->method('createEnumValue')
            ->with('0_1', 4, false, '0_1_1')
            ->will($this->returnValue($newValue4));
        $em->expects($this->at(4))
            ->method('persist')
            ->with($this->identicalTo($newValue4));

        $em->expects($this->once())
            ->method('flush');
        $this->dbTranslationMetadataCache->expects($this->once())
            ->method('updateTimestamp')
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
        $locale             = 'fr';

        $enumOptions = [
            ['id' => 'option_1', 'label' => 'Existing Option 1', 'priority' => 1, 'is_default' => true],
            ['id' => 'option_1_1', 'label' => 'Existing Option 11', 'priority' => 3, 'is_default' => false],
            ['id' => '', 'label' => 'Option 1', 'priority' => 2, 'is_default' => true],
        ];

        $value1 = new TestEnumValue('option_1', 'Existing Option 1', 1, true);
        $value2 = new TestEnumValue('option_1_1', 'Existing Option 11', 3, true);
        $value3 = new TestEnumValue('option_1_2', 'Existing Option 12', 2, false);

        $newValue = new TestEnumValue('option_1_2', 'Option 1', 2, true);

        $values = [$value1, $value2, $value3];

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
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
            ->with('Option 1', 2, true, 'option_1_2')
            ->will($this->returnValue($newValue));
        $em->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($newValue));

        $em->expects($this->once())
            ->method('flush');
        $this->dbTranslationMetadataCache->expects($this->once())
            ->method('updateTimestamp')
            ->with($locale);

        $this->synchronizer->applyEnumOptions($enumValueClassName, $enumOptions, $locale);

        $expectedValue1 = new TestEnumValue('option_1', 'Existing Option 1', 1, true);
        $this->assertEquals($expectedValue1, $value1);
        $expectedValue2 = new TestEnumValue('option_1_1', 'Existing Option 11', 3, false);
        $expectedValue2->setLocale($locale);
        $this->assertEquals($expectedValue2, $value2);
        $expectedNewValue = new TestEnumValue('option_1_2', 'Option 1', 2, true);
        $expectedNewValue->setLocale($locale);
        $this->assertEquals($expectedNewValue, $newValue);
    }

    public function testGetEnumOptions()
    {
        $enumValueClassName = 'Test\EnumValue';
        $values             = [['id' => 'opt1']];

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with($enumValueClassName)
            ->will($this->returnValue($em));
        $enumRepo = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('getRepository')
            ->with($enumValueClassName)
            ->will($this->returnValue($enumRepo));
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $enumRepo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('e')
            ->will($this->returnValue($qb));
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
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
        $query->expects($this->once())
            ->method('setHint')
            ->with(
                Query::HINT_CUSTOM_OUTPUT_WALKER,
                'Gedmo\Translatable\Query\TreeWalker\TranslationWalker'
            )
            ->will($this->returnSelf());
        $query->expects($this->once())
            ->method('getArrayResult')
            ->will($this->returnValue($values));

        $result = $this->synchronizer->getEnumOptions($enumValueClassName);

        $this->assertEquals($values, $result);
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject $em
     * @param string                                   $enumValueClassName
     * @param string                                   $locale
     * @param array                                    $values
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function setApplyEnumOptionsQueryExpectation($em, $enumValueClassName, $locale, $values)
    {
        $enumRepo = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('getRepository')
            ->with($enumValueClassName)
            ->will($this->returnValue($enumRepo));
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $enumRepo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('o')
            ->will($this->returnValue($qb));
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
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

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getConfigProviderMock()
    {
        return $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
    }
}

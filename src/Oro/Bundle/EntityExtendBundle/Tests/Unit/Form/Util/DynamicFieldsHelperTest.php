<?php

namespace EntityExtendBundle\Tests\Unit\Form\Util;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Form\Util\DynamicFieldsHelper;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Util\Stub\Entity;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

class DynamicFieldsHelperTest extends \PHPUnit\Framework\TestCase
{
    const CLASS_NAME = 'MockClass';
    const FIELD_NAME = 'mockField';

    /** @var DynamicFieldsHelper */
    private $helper;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var RouterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $router;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->featureChecker = $this->getMockBuilder(FeatureChecker::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->router = $this->getMockBuilder(RouterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->translator = $this->getMockBuilder(TranslatorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = new DynamicFieldsHelper(
            $this->configManager,
            $this->featureChecker,
            $this->doctrineHelper,
            $this->router,
            $this->translator
        );
    }

    public function testShouldBeInitialized()
    {
        $fieldConfigId = $this->getFieldConfigId(RelationType::MANY_TO_MANY);
        $extendConfigProvider = $this->getExtendConfigProvider(
            $fieldConfigId,
            [
                'is_attribute' => false,
                'owner' => ExtendScope::OWNER_CUSTOM,
            ]
        );

        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->willReturn($extendConfigProvider);

        /** @var ConfigInterface|\PHPUnit\Framework\MockObject\MockObject $formConfig */
        $formConfig = $this->getMockBuilder(ConfigInterface::class)
            ->getMock();
        $formConfig->expects($this->once())
            ->method('getId')
            ->willReturn($fieldConfigId);

        /** @var FormView|\PHPUnit\Framework\MockObject\MockObject $formView */
        $formView = $this->getMockBuilder(FormView::class)
            ->disableOriginalConstructor()
            ->getMock();
        $formView->children[self::FIELD_NAME] = 'MOCK';

        $this->assertTrue($this->helper->shouldBeInitialized(self::CLASS_NAME, $formConfig, $formView));
    }

    public function testShouldNotBeInitializedFieldIsNotApplicable()
    {
        $fieldConfigId = $this->getFieldConfigId(RelationType::MANY_TO_MANY);
        $extendConfigProvider = $this->getExtendConfigProvider(
            $fieldConfigId,
            [
                'is_attribute' => false,
                'owner' => ExtendScope::OWNER_SYSTEM,
            ]
        );

        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->willReturn($extendConfigProvider);

        /** @var ConfigInterface|\PHPUnit\Framework\MockObject\MockObject $formConfig */
        $formConfig = $this->getMockBuilder(ConfigInterface::class)
            ->getMock();
        $formConfig->expects($this->once())
            ->method('getId')
            ->willReturn($fieldConfigId);

        /** @var FormView|\PHPUnit\Framework\MockObject\MockObject $formView */
        $formView = $this->getMockBuilder(FormView::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->assertFalse($this->helper->shouldBeInitialized(self::CLASS_NAME, $formConfig, $formView));
    }

    public function testShouldNotBeInitializedViewHasNoField()
    {
        $fieldConfigId = $this->getFieldConfigId(RelationType::MANY_TO_MANY);
        $extendConfigProvider = $this->getExtendConfigProvider(
            $fieldConfigId,
            [
                'is_attribute' => false,
                'owner' => ExtendScope::OWNER_CUSTOM,
            ]
        );

        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->willReturn($extendConfigProvider);

        /** @var ConfigInterface|\PHPUnit\Framework\MockObject\MockObject $formConfig */
        $formConfig = $this->getMockBuilder(ConfigInterface::class)
            ->getMock();
        $formConfig->expects($this->once())
            ->method('getId')
            ->willReturn($fieldConfigId);

        /** @var FormView|\PHPUnit\Framework\MockObject\MockObject $formView */
        $formView = $this->getMockBuilder(FormView::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->assertFalse($this->helper->shouldBeInitialized(self::CLASS_NAME, $formConfig, $formView));
    }

    public function testShouldNotBeInitializedUnsupportedFieldType()
    {
        $fieldConfigId = $this->getFieldConfigId(RelationType::TO_MANY);
        $extendConfigProvider = $this->getExtendConfigProvider(
            $fieldConfigId,
            [
                'is_attribute' => false,
                'owner' => ExtendScope::OWNER_CUSTOM,
            ]
        );

        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->willReturn($extendConfigProvider);

        /** @var ConfigInterface|\PHPUnit\Framework\MockObject\MockObject $formConfig */
        $formConfig = $this->getMockBuilder(ConfigInterface::class)
            ->getMock();
        $formConfig->expects($this->once())
            ->method('getId')
            ->willReturn($fieldConfigId);

        /** @var FormView|\PHPUnit\Framework\MockObject\MockObject $formView */
        $formView = $this->getMockBuilder(FormView::class)
            ->disableOriginalConstructor()
            ->getMock();
        $formView->children[self::FIELD_NAME] = 'MOCK';

        $this->assertFalse($this->helper->shouldBeInitialized(self::CLASS_NAME, $formConfig, $formView));
    }

    /**
     * @param string $relationType
     * @param array $values
     * @param bool $expected
     * @dataProvider isApplicableFieldDataProvider
     */
    public function testIsApplicableField($relationType, array $values, $expected)
    {
        $fieldConfigId = $this->getFieldConfigId($relationType);

        $extendConfigProvider = $this->getExtendConfigProvider(
            $fieldConfigId,
            $values
        );

        $this->assertSame(
            $expected,
            $this->helper->isApplicableField(
                $extendConfigProvider->getConfig(self::CLASS_NAME, self::FIELD_NAME),
                $extendConfigProvider
            )
        );
    }

    public function testAddInitialElements()
    {
        $entity = new Entity();
        $entity->setId(1)
            ->setMockField('mockedValue');

        $fieldConfigId = $this->getFieldConfigId(RelationType::MANY_TO_MANY);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->getMockBuilder(FormInterface::class)
            ->getMock();
        $form->expects($this->any())
            ->method('getData')
            ->willReturn($entity);

        /** @var FormView|\PHPUnit\Framework\MockObject\MockObject $formView */
        $formView = $this->getMockBuilder(FormView::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var ConfigInterface|\PHPUnit\Framework\MockObject\MockObject $formConfig */
        $formConfig = $this->getMockBuilder(ConfigInterface::class)
            ->getMock();
        $formConfig->expects($this->once())
            ->method('getId')
            ->willReturn($fieldConfigId);

        $this->helper->addInitialElements($formView, $form, $formConfig);
    }

    /**
     * @param array $pkColumns
     * @param bool $hasConfig
     * @param string $expected
     * @dataProvider addInitialElementsDataProvider
     */
    public function testGetIdColumnName($pkColumns, $hasConfig, $expected)
    {
        $entity = new Entity();
        $entity->setId(1)
            ->setMockField('mockedValue');

        $fieldConfigId = $this->getFieldConfigId(RelationType::MANY_TO_MANY);

        $extendConfigProvider = $this->getExtendConfigProvider(
            $fieldConfigId,
            [
                'pk_columns' => $pkColumns
            ],
            null
        );
        $extendConfigProvider->expects($this->any())
            ->method('hasConfig')
            ->willReturn($hasConfig);

        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->willReturn($extendConfigProvider);

        $this->assertSame(
            $expected,
            $this->helper->getIdColumnName(self::CLASS_NAME)
        );
    }

    /**
     * @return array
     */
    public function isApplicableFieldDataProvider()
    {
        return [
            'is applicable' => [
                RelationType::MANY_TO_MANY,
                [
                    'owner' => ExtendScope::OWNER_CUSTOM,
                ],
                true,
            ],
            'wrong relation type' => [
                RelationType::TO_MANY,
                [
                    'owner' => ExtendScope::OWNER_CUSTOM,
                ],
                false,
            ],
            'system owner' => [
                RelationType::MANY_TO_MANY,
                [
                    'owner' => ExtendScope::OWNER_SYSTEM,
                ],
                false,
            ],
            'is deleted' => [
                RelationType::MANY_TO_MANY,
                [
                    'owner' => ExtendScope::OWNER_CUSTOM,
                    'extend' => true,
                    'deleted' => false,
                ],
                true,
            ],
        ];
    }

    /**
     * @return array
     */
    public function addInitialElementsDataProvider()
    {
        return [
            'has config with id field' => [
                ['id'], true, 'id'
            ],
            'has config with more id fields' => [
                ['test', 'id'], true, 'test'
            ],
            'has no config' => [
                null, false, 'id'
            ],
        ];
    }

    /**
     * @param string $fieldType
     * @return FieldConfigId|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getFieldConfigId($fieldType)
    {
        $fieldConfigId = $this->getMockBuilder(FieldConfigId::class)
            ->disableOriginalConstructor()
            ->getMock();
        $fieldConfigId->expects($this->any())
            ->method('getFieldName')
            ->willReturn(self::FIELD_NAME);
        $fieldConfigId->expects($this->any())
            ->method('getClassName')
            ->willReturn(self::CLASS_NAME);
        $fieldConfigId->expects($this->any())
            ->method('getFieldType')
            ->willReturn($fieldType);

        return $fieldConfigId;
    }

    /**
     * @param FieldConfigId $fieldConfigId
     * @param array $values
     * @param string $fieldName
     * @return ConfigInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getExtendConfigProvider(FieldConfigId $fieldConfigId, array $values, $fieldName = self::FIELD_NAME)
    {
        $extendConfigProvider = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $extendConfigProvider->expects($this->any())
            ->method('getConfig')
            ->with(self::CLASS_NAME, $fieldName)
            ->willReturn(new Config(
                $fieldConfigId,
                $values
            ));

        return $extendConfigProvider;
    }
}

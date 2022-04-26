<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Util;

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
use Symfony\Contracts\Translation\TranslatorInterface;

class DynamicFieldsHelperTest extends \PHPUnit\Framework\TestCase
{
    private const CLASS_NAME = 'MockClass';
    private const FIELD_NAME = 'mockField';

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

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

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

        $formConfig = $this->createMock(ConfigInterface::class);
        $formConfig->expects($this->once())
            ->method('getId')
            ->willReturn($fieldConfigId);

        $formView = $this->createMock(FormView::class);
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

        $formConfig = $this->createMock(ConfigInterface::class);
        $formConfig->expects($this->once())
            ->method('getId')
            ->willReturn($fieldConfigId);

        $formView = $this->createMock(FormView::class);

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

        $formConfig = $this->createMock(ConfigInterface::class);
        $formConfig->expects($this->once())
            ->method('getId')
            ->willReturn($fieldConfigId);

        $formView = $this->createMock(FormView::class);

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

        $formConfig = $this->createMock(ConfigInterface::class);
        $formConfig->expects($this->once())
            ->method('getId')
            ->willReturn($fieldConfigId);

        $formView = $this->createMock(FormView::class);
        $formView->children[self::FIELD_NAME] = 'MOCK';

        $this->assertFalse($this->helper->shouldBeInitialized(self::CLASS_NAME, $formConfig, $formView));
    }

    /**
     * @dataProvider isApplicableFieldDataProvider
     */
    public function testIsApplicableField(string $relationType, array $values, bool $expected)
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
            ->setMockField([]);

        $fieldConfigId = $this->getFieldConfigId(RelationType::MANY_TO_MANY);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('getData')
            ->willReturn($entity);

        $formView = $this->createMock(FormView::class);

        $formConfig = $this->createMock(ConfigInterface::class);
        $formConfig->expects($this->once())
            ->method('getId')
            ->willReturn($fieldConfigId);

        $formView->children[self::FIELD_NAME] = $this->createMock(FormInterface::class);

        $this->helper->addInitialElements($formView, $form, $formConfig);
    }

    /**
     * @dataProvider addInitialElementsDataProvider
     */
    public function testGetIdColumnName(?array $pkColumns, bool $hasConfig, string $expected)
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

    public function isApplicableFieldDataProvider(): array
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

    public function addInitialElementsDataProvider(): array
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
     * @return FieldConfigId|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getFieldConfigId(string $fieldType)
    {
        $fieldConfigId = $this->createMock(FieldConfigId::class);
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
     * @return ConfigProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getExtendConfigProvider(
        FieldConfigId $fieldConfigId,
        array $values,
        ?string $fieldName = self::FIELD_NAME
    ) {
        $extendConfigProvider = $this->createMock(ConfigProvider::class);
        $extendConfigProvider->expects($this->any())
            ->method('getConfig')
            ->with(self::CLASS_NAME, $fieldName)
            ->willReturn(new Config($fieldConfigId, $values));

        return $extendConfigProvider;
    }
}

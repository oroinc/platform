<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Tests\Unit\ExtendedFields;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager as EntityConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Component\DraftSession\ExtendedFields\EntityDraftExtendedFieldsProvider;
use Oro\Component\DraftSession\Tests\Unit\Stub\EntityDraftAwareStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class EntityDraftExtendedFieldsProviderTest extends TestCase
{
    private EntityConfigManager&MockObject $configManager;
    private ConfigProvider&MockObject $formConfigProvider;
    private ConfigProvider&MockObject $extendConfigProvider;
    private ConfigProvider&MockObject $attributeConfigProvider;
    private EntityDraftExtendedFieldsProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(EntityConfigManager::class);
        $this->formConfigProvider = $this->createMock(ConfigProvider::class);
        $this->extendConfigProvider = $this->createMock(ConfigProvider::class);
        $this->attributeConfigProvider = $this->createMock(ConfigProvider::class);

        $this->configManager->expects(self::any())
            ->method('getProvider')
            ->willReturnMap([
                ['extend', $this->extendConfigProvider],
                ['form', $this->formConfigProvider],
                ['attribute', $this->attributeConfigProvider],
            ]);

        $this->provider = new EntityDraftExtendedFieldsProvider($this->configManager);
    }

    public function testReturnsEmptyArrayWhenNoFormConfigs(): void
    {
        $this->formConfigProvider->expects(self::once())
            ->method('getConfigs')
            ->with(EntityDraftAwareStub::class)
            ->willReturn([]);

        self::assertSame([], $this->provider->getApplicableExtendedFields(EntityDraftAwareStub::class));
    }

    public function testSkipsDisabledFormFields(): void
    {
        $formConfig = new Config(
            new FieldConfigId('form', EntityDraftAwareStub::class, 'customField', 'string'),
            ['is_enabled' => false]
        );

        $this->formConfigProvider->expects(self::once())
            ->method('getConfigs')
            ->with(EntityDraftAwareStub::class)
            ->willReturn([$formConfig]);

        self::assertSame([], $this->provider->getApplicableExtendedFields(EntityDraftAwareStub::class));
    }

    public function testSkipsExcludedFields(): void
    {
        $formConfig = new Config(
            new FieldConfigId('form', EntityDraftAwareStub::class, 'excludedField', 'string'),
            ['is_enabled' => true]
        );

        $this->formConfigProvider->expects(self::once())
            ->method('getConfigs')
            ->with(EntityDraftAwareStub::class)
            ->willReturn([$formConfig]);

        $this->attributeConfigProvider->expects(self::never())
            ->method('hasConfig');

        $this->provider->addExcludedField(EntityDraftAwareStub::class, 'excludedField');

        self::assertSame([], $this->provider->getApplicableExtendedFields(EntityDraftAwareStub::class));
    }

    public function testSkipsAttributes(): void
    {
        $formConfig = new Config(
            new FieldConfigId('form', EntityDraftAwareStub::class, 'attrField', 'string'),
            ['is_enabled' => true]
        );

        $this->formConfigProvider->expects(self::once())
            ->method('getConfigs')
            ->with(EntityDraftAwareStub::class)
            ->willReturn([$formConfig]);

        $this->attributeConfigProvider->expects(self::once())
            ->method('hasConfig')
            ->with(EntityDraftAwareStub::class, 'attrField')
            ->willReturn(true);

        $this->attributeConfigProvider->expects(self::once())
            ->method('getConfig')
            ->with(EntityDraftAwareStub::class, 'attrField')
            ->willReturn(
                new Config(
                    new FieldConfigId('attribute', EntityDraftAwareStub::class, 'attrField', 'string'),
                    ['is_attribute' => true]
                )
            );

        self::assertSame([], $this->provider->getApplicableExtendedFields(EntityDraftAwareStub::class));
    }

    public function testSkipsNonCustomOwnerFields(): void
    {
        $formConfig = new Config(
            new FieldConfigId('form', EntityDraftAwareStub::class, 'systemField', 'string'),
            ['is_enabled' => true]
        );

        $this->formConfigProvider->expects(self::once())
            ->method('getConfigs')
            ->with(EntityDraftAwareStub::class)
            ->willReturn([$formConfig]);

        $this->attributeConfigProvider->expects(self::once())
            ->method('hasConfig')
            ->willReturn(false);

        $this->extendConfigProvider->expects(self::once())
            ->method('getConfig')
            ->with(EntityDraftAwareStub::class, 'systemField')
            ->willReturn(
                new Config(
                    new FieldConfigId('extend', EntityDraftAwareStub::class, 'systemField', 'string'),
                    ['owner' => ExtendScope::OWNER_SYSTEM, 'state' => ExtendScope::STATE_ACTIVE]
                )
            );

        self::assertSame([], $this->provider->getApplicableExtendedFields(EntityDraftAwareStub::class));
    }

    public function testSkipsInaccessibleFields(): void
    {
        $formConfig = new Config(
            new FieldConfigId('form', EntityDraftAwareStub::class, 'deletedField', 'string'),
            ['is_enabled' => true]
        );

        $this->formConfigProvider->expects(self::once())
            ->method('getConfigs')
            ->with(EntityDraftAwareStub::class)
            ->willReturn([$formConfig]);

        $this->attributeConfigProvider->expects(self::once())
            ->method('hasConfig')
            ->willReturn(false);

        $this->extendConfigProvider->expects(self::once())
            ->method('getConfig')
            ->with(EntityDraftAwareStub::class, 'deletedField')
            ->willReturn(
                new Config(
                    new FieldConfigId('extend', EntityDraftAwareStub::class, 'deletedField', 'string'),
                    [
                        'owner' => ExtendScope::OWNER_CUSTOM,
                        'is_extend' => true,
                        'is_deleted' => true,
                        'state' => ExtendScope::STATE_ACTIVE,
                    ]
                )
            );

        self::assertSame([], $this->provider->getApplicableExtendedFields(EntityDraftAwareStub::class));
    }

    public function testSkipsToAnyRelationTypeFields(): void
    {
        $formConfig = new Config(
            new FieldConfigId('form', EntityDraftAwareStub::class, 'refOneField', 'ref-one'),
            ['is_enabled' => true]
        );

        $this->formConfigProvider->expects(self::once())
            ->method('getConfigs')
            ->with(EntityDraftAwareStub::class)
            ->willReturn([$formConfig]);

        $this->attributeConfigProvider->expects(self::once())
            ->method('hasConfig')
            ->willReturn(false);

        $this->extendConfigProvider->expects(self::once())
            ->method('getConfig')
            ->with(EntityDraftAwareStub::class, 'refOneField')
            ->willReturn(
                new Config(
                    new FieldConfigId('extend', EntityDraftAwareStub::class, 'refOneField', 'ref-one'),
                    ['owner' => ExtendScope::OWNER_CUSTOM, 'state' => ExtendScope::STATE_ACTIVE]
                )
            );

        self::assertSame([], $this->provider->getApplicableExtendedFields(EntityDraftAwareStub::class));
    }

    public function testSkipsHiddenTargetEntity(): void
    {
        $formConfig = new Config(
            new FieldConfigId('form', EntityDraftAwareStub::class, 'hiddenRelation', 'manyToOne'),
            ['is_enabled' => true]
        );

        $this->formConfigProvider->expects(self::once())
            ->method('getConfigs')
            ->with(EntityDraftAwareStub::class)
            ->willReturn([$formConfig]);

        $this->attributeConfigProvider->expects(self::once())
            ->method('hasConfig')
            ->willReturn(false);

        $this->extendConfigProvider->expects(self::once())
            ->method('getConfig')
            ->with(EntityDraftAwareStub::class, 'hiddenRelation')
            ->willReturn(
                new Config(
                    new FieldConfigId('extend', EntityDraftAwareStub::class, 'hiddenRelation', 'manyToOne'),
                    [
                        'owner' => ExtendScope::OWNER_CUSTOM,
                        'state' => ExtendScope::STATE_ACTIVE,
                        'target_entity' => \stdClass::class,
                    ]
                )
            );

        $this->configManager->expects(self::once())
            ->method('isHiddenModel')
            ->with(\stdClass::class)
            ->willReturn(true);

        self::assertSame([], $this->provider->getApplicableExtendedFields(EntityDraftAwareStub::class));
    }

    public function testSkipsInaccessibleTargetEntity(): void
    {
        $formConfig = new Config(
            new FieldConfigId('form', EntityDraftAwareStub::class, 'deletedTargetRelation', 'manyToOne'),
            ['is_enabled' => true]
        );

        $this->formConfigProvider->expects(self::once())
            ->method('getConfigs')
            ->with(EntityDraftAwareStub::class)
            ->willReturn([$formConfig]);

        $this->attributeConfigProvider->expects(self::once())
            ->method('hasConfig')
            ->willReturn(false);

        $this->extendConfigProvider->expects(self::exactly(2))
            ->method('getConfig')
            ->willReturnMap([
                [
                    EntityDraftAwareStub::class,
                    'deletedTargetRelation',
                    new Config(
                        new FieldConfigId('extend', EntityDraftAwareStub::class, 'deletedTargetRelation', 'manyToOne'),
                        [
                            'owner' => ExtendScope::OWNER_CUSTOM,
                            'state' => ExtendScope::STATE_ACTIVE,
                            'target_entity' => \stdClass::class,
                        ]
                    ),
                ],
                [
                    \stdClass::class,
                    null,
                    new Config(
                        new EntityConfigId('extend', \stdClass::class),
                        ['is_extend' => true, 'is_deleted' => true]
                    ),
                ],
            ]);

        $this->configManager->expects(self::once())
            ->method('isHiddenModel')
            ->with(\stdClass::class)
            ->willReturn(false);

        self::assertSame([], $this->provider->getApplicableExtendedFields(EntityDraftAwareStub::class));
    }

    public function testReturnsApplicableFields(): void
    {
        $formConfigs = [
            new Config(
                new FieldConfigId('form', EntityDraftAwareStub::class, 'customString', 'string'),
                ['is_enabled' => true]
            ),
            new Config(
                new FieldConfigId('form', EntityDraftAwareStub::class, 'customEnum', 'enum'),
                ['is_enabled' => true]
            ),
            new Config(
                new FieldConfigId('form', EntityDraftAwareStub::class, 'customRelation', 'manyToOne'),
                ['is_enabled' => true]
            ),
        ];

        $this->formConfigProvider->expects(self::once())
            ->method('getConfigs')
            ->with(EntityDraftAwareStub::class)
            ->willReturn($formConfigs);

        $this->attributeConfigProvider->expects(self::exactly(3))
            ->method('hasConfig')
            ->willReturn(false);

        $targetEntityConfig = new Config(
            new EntityConfigId('extend', \stdClass::class),
            ['is_extend' => false]
        );

        $this->extendConfigProvider->expects(self::exactly(4))
            ->method('getConfig')
            ->willReturnMap([
                [
                    EntityDraftAwareStub::class,
                    'customString',
                    new Config(
                        new FieldConfigId('extend', EntityDraftAwareStub::class, 'customString', 'string'),
                        ['owner' => ExtendScope::OWNER_CUSTOM, 'state' => ExtendScope::STATE_ACTIVE]
                    ),
                ],
                [
                    EntityDraftAwareStub::class,
                    'customEnum',
                    new Config(
                        new FieldConfigId('extend', EntityDraftAwareStub::class, 'customEnum', 'enum'),
                        ['owner' => ExtendScope::OWNER_CUSTOM, 'state' => ExtendScope::STATE_ACTIVE]
                    ),
                ],
                [
                    EntityDraftAwareStub::class,
                    'customRelation',
                    new Config(
                        new FieldConfigId('extend', EntityDraftAwareStub::class, 'customRelation', 'manyToOne'),
                        [
                            'owner' => ExtendScope::OWNER_CUSTOM,
                            'state' => ExtendScope::STATE_ACTIVE,
                            'target_entity' => \stdClass::class,
                        ]
                    ),
                ],
                [
                    \stdClass::class,
                    null,
                    $targetEntityConfig,
                ],
            ]);

        $this->configManager->expects(self::once())
            ->method('isHiddenModel')
            ->with(\stdClass::class)
            ->willReturn(false);

        self::assertSame(
            [
                'customString' => 'string',
                'customEnum' => 'enum',
                'customRelation' => 'manyToOne',
            ],
            $this->provider->getApplicableExtendedFields(EntityDraftAwareStub::class)
        );
    }

    public function testAddExcludedFieldAccumulatesPerClass(): void
    {
        $this->provider->addExcludedField(EntityDraftAwareStub::class, 'fieldA');
        $this->provider->addExcludedField(EntityDraftAwareStub::class, 'fieldB');
        $this->provider->addExcludedField(\stdClass::class, 'fieldA');

        $formConfigs = [
            new Config(
                new FieldConfigId('form', EntityDraftAwareStub::class, 'fieldA', 'string'),
                ['is_enabled' => true]
            ),
            new Config(
                new FieldConfigId('form', EntityDraftAwareStub::class, 'fieldB', 'string'),
                ['is_enabled' => true]
            ),
            new Config(
                new FieldConfigId('form', EntityDraftAwareStub::class, 'fieldC', 'string'),
                ['is_enabled' => true]
            ),
        ];

        $this->formConfigProvider->expects(self::once())
            ->method('getConfigs')
            ->with(EntityDraftAwareStub::class)
            ->willReturn($formConfigs);

        $this->attributeConfigProvider->expects(self::once())
            ->method('hasConfig')
            ->with(EntityDraftAwareStub::class, 'fieldC')
            ->willReturn(false);

        $this->extendConfigProvider->expects(self::once())
            ->method('getConfig')
            ->with(EntityDraftAwareStub::class, 'fieldC')
            ->willReturn(
                new Config(
                    new FieldConfigId('extend', EntityDraftAwareStub::class, 'fieldC', 'string'),
                    ['owner' => ExtendScope::OWNER_CUSTOM, 'state' => ExtendScope::STATE_ACTIVE]
                )
            );

        self::assertSame(
            ['fieldC' => 'string'],
            $this->provider->getApplicableExtendedFields(EntityDraftAwareStub::class)
        );
    }
}

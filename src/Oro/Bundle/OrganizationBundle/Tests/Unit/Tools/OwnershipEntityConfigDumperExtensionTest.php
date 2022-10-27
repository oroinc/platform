<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Tools;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Exception\RuntimeException;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntityExtendBundle\Tools\RelationBuilder;
use Oro\Bundle\OrganizationBundle\Tools\OwnershipEntityConfigDumperExtension;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;

class OwnershipEntityConfigDumperExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var RelationBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $relationBuilder;

    /** @var OwnershipMetadataProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $ownershipMetadataProvider;

    /** @var OwnershipEntityConfigDumperExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->relationBuilder = $this->createMock(RelationBuilder::class);
        $this->ownershipMetadataProvider = $this->createMock(OwnershipMetadataProviderInterface::class);

        $this->extension = new OwnershipEntityConfigDumperExtension(
            $this->configManager,
            $this->relationBuilder,
            $this->ownershipMetadataProvider
        );
    }

    public function testSupportsPreUpdate()
    {
        $this->assertTrue(
            $this->extension->supports(ExtendConfigDumper::ACTION_PRE_UPDATE)
        );
    }

    public function testSupportsPostUpdate()
    {
        $this->assertFalse(
            $this->extension->supports(ExtendConfigDumper::ACTION_POST_UPDATE)
        );
    }

    /**
     * @dataProvider preUpdateProvider
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testPreUpdate($ownerType, $getOwnerClassMethodName, $getOwnerClassMethodNameCalls)
    {
        $extendConfig1 = new Config(new EntityConfigId('extend', 'Test\Entity1'));
        $extendConfig1->set('owner', ExtendScope::OWNER_CUSTOM);
        $extendConfig1->set('state', ExtendScope::STATE_NEW);
        // should be skipped because it has empty owner_type
        $extendConfig2 = new Config(new EntityConfigId('extend', 'Test\Entity2'));
        $extendConfig2->set('owner', ExtendScope::OWNER_CUSTOM);
        $extendConfig2->set('state', ExtendScope::STATE_NEW);
        // should be skipped because it has no ownership config
        $extendConfig3 = new Config(new EntityConfigId('extend', 'Test\Entity3'));
        $extendConfig3->set('owner', ExtendScope::OWNER_CUSTOM);
        $extendConfig3->set('state', ExtendScope::STATE_NEW);
        // should be skipped because it is not new entity
        $extendConfig4 = new Config(new EntityConfigId('extend', 'Test\Entity4'));
        $extendConfig4->set('owner', ExtendScope::OWNER_CUSTOM);
        $extendConfig4->set('state', ExtendScope::STATE_UPDATE);
        // should be skipped because it is not custom entity
        $extendConfig5 = new Config(new EntityConfigId('extend', 'Test\Entity5'));
        $extendConfig5->set('owner', ExtendScope::OWNER_SYSTEM);

        $ownershipConfig1 = new Config(new EntityConfigId('ownership', 'Test\Entity1'));
        $ownershipConfig1->set('owner_type', $ownerType);
        $ownershipConfig1->set('owner_field_name', 'owner_field');
        $ownershipConfig1->set('organization_field_name', 'organization_field');
        $ownershipConfig2 = new Config(new EntityConfigId('ownership', 'Test\Entity2'));

        $extendConfigs    = [$extendConfig1, $extendConfig3, $extendConfig3, $extendConfig4, $extendConfig5];
        $ownershipConfigs = [$ownershipConfig1, $ownershipConfig2];

        $extendConfigProvider = $this->createMock(ConfigProvider::class);
        $extendConfigProvider->expects($this->once())
            ->method('getConfigs')
            ->willReturn($extendConfigs);

        $ownershipConfigProvider = $this->createMock(ConfigProvider::class);
        $ownershipConfigProvider->expects($this->any())
            ->method('hasConfig')
            ->willReturnCallback(function ($className, $fieldName) use ($ownershipConfigs) {
                foreach ($ownershipConfigs as $ownershipConfig) {
                    if ($ownershipConfig->getId()->getClassName() === $className) {
                        return true;
                    }
                }

                return false;
            });
        $ownershipConfigProvider->expects($this->any())
            ->method('getConfig')
            ->willReturnCallback(function ($className, $fieldName) use ($ownershipConfigs) {
                foreach ($ownershipConfigs as $ownershipConfig) {
                    if ($ownershipConfig->getId()->getClassName() === $className) {
                        return $ownershipConfig;
                    }
                }

                throw new RuntimeException(sprintf('No config for "%s".', $className));
            });

        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->willReturnMap([
                ['ownership', $ownershipConfigProvider],
                ['extend', $extendConfigProvider],
            ]);
        if ($getOwnerClassMethodNameCalls === 1) {
            $this->ownershipMetadataProvider->expects($this->exactly(2))
                ->method($getOwnerClassMethodName)
                ->willReturn('Test\Owner');
        } else {
            $this->ownershipMetadataProvider->expects($this->once())
                ->method($getOwnerClassMethodName)
                ->willReturn('Test\Owner');
            $this->ownershipMetadataProvider->expects($this->any())
                ->method('getOrganizationClass')
                ->willReturn('Test\Organization');
        }

        $this->relationBuilder->expects($this->exactly($getOwnerClassMethodNameCalls))
            ->method('addManyToOneRelation');

        switch ($getOwnerClassMethodNameCalls) {
            case 1:
                $this->relationBuilder->expects($this->once())
                    ->method('addManyToOneRelation')
                    ->with(
                        $this->identicalTo($extendConfig1),
                        'Test\Owner',
                        'owner_field',
                        'id',
                        [
                            'entity'    => [
                                'label'       => 'oro.custom_entity.owner_field.label',
                                'description' => 'oro.custom_entity.owner_field.description',
                            ],
                            'view'      => [
                                'is_displayable' => false
                            ],
                            'form'      => [
                                'is_enabled' => false
                            ],
                            'dataaudit' => [
                                'auditable' => true
                            ],
                            'datagrid' => [
                                'is_visible' => 0
                            ]
                        ]
                    );
                break;
            case 2:
                $this->relationBuilder->expects($this->exactly(2))
                    ->method('addManyToOneRelation')
                    ->withConsecutive(
                        [
                            $this->identicalTo($extendConfig1),
                            'Test\Owner',
                            'owner_field',
                            'id',
                            [
                                'entity'    => [
                                    'label'       => 'oro.custom_entity.owner_field.label',
                                    'description' => 'oro.custom_entity.owner_field.description',
                                ],
                                'view'      => [
                                    'is_displayable' => false
                                ],
                                'form'      => [
                                    'is_enabled' => false
                                ],
                                'dataaudit' => [
                                    'auditable' => true
                                ],
                                'datagrid' => [
                                    'is_visible' => 0
                                ]
                            ]
                        ],
                        [
                            $this->identicalTo($extendConfig1),
                            'Test\Organization',
                            'organization_field',
                            'id',
                            [
                                'entity'    => [
                                    'label'       => 'oro.custom_entity.organization_field.label',
                                    'description' => 'oro.custom_entity.organization_field.description',
                                ],
                                'view'      => [
                                    'is_displayable' => false
                                ],
                                'form'      => [
                                    'is_enabled' => false
                                ],
                                'dataaudit' => [
                                    'auditable' => true
                                ],
                                'datagrid' => [
                                    'is_visible' => 0
                                ]
                            ]
                        ]
                    );
                break;
        }

        $this->extension->preUpdate();
    }

    public function preUpdateProvider(): array
    {
        return [
            ['USER', 'getUserClass', 2],
            ['BUSINESS_UNIT', 'getBusinessUnitClass', 2],
            ['ORGANIZATION', 'getOrganizationClass', 1],
        ];
    }
}

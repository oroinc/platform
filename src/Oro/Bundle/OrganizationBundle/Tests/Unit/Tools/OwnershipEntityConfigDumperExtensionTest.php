<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Tools;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Exception\RuntimeException;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\OrganizationBundle\Tools\OwnershipEntityConfigDumperExtension;

class OwnershipEntityConfigDumperExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $relationBuilder;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $ownershipMetadataProvider;

    /** @var OwnershipEntityConfigDumperExtension */
    protected $extension;

    public function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->relationBuilder = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Tools\RelationBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $this->ownershipMetadataProvider =
            $this->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider')
                ->disableOriginalConstructor()
                ->getMock();

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

        $extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $extendConfigProvider->expects($this->once())
            ->method('getConfigs')
            ->will($this->returnValue($extendConfigs));

        $ownershipConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $ownershipConfigProvider->expects($this->any())
            ->method('hasConfig')
            ->will(
                $this->returnCallback(
                    function ($className, $fieldName) use ($ownershipConfigs) {
                        foreach ($ownershipConfigs as $ownershipConfig) {
                            if ($ownershipConfig->getId()->getClassName() === $className) {
                                return true;
                            }
                        }

                        return false;
                    }
                )
            );
        $ownershipConfigProvider->expects($this->any())
            ->method('getConfig')
            ->will(
                $this->returnCallback(
                    function ($className, $fieldName) use ($ownershipConfigs) {
                        foreach ($ownershipConfigs as $ownershipConfig) {
                            if ($ownershipConfig->getId()->getClassName() === $className) {
                                return $ownershipConfig;
                            }
                        }

                        throw new RuntimeException(sprintf('No config for "%s".', $className));
                    }
                )
            );

        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['ownership', $ownershipConfigProvider],
                        ['extend', $extendConfigProvider],
                    ]
                )
            );
        if ($getOwnerClassMethodNameCalls == 1) {
            $this->ownershipMetadataProvider->expects($this->exactly(2))
                ->method($getOwnerClassMethodName)
                ->will($this->returnValue('Test\Owner'));
        } else {
            $this->ownershipMetadataProvider->expects($this->once())
                ->method($getOwnerClassMethodName)
                ->will($this->returnValue('Test\Owner'));
            $this->ownershipMetadataProvider->expects($this->any())
                ->method('getOrganizationClass')
                ->will($this->returnValue('Test\Organization'));
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
                            ]
                        ]
                    );
                break;
            case 2:
                $this->relationBuilder->expects($this->at(0))
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
                            ]
                        ]
                    );
                $this->relationBuilder->expects($this->at(1))
                    ->method('addManyToOneRelation')
                    ->with(
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
                            ]
                        ]
                    );
                break;
        }

        $this->extension->preUpdate();
    }

    public function preUpdateProvider()
    {
        return [
            ['USER', 'getUserClass', 2],
            ['BUSINESS_UNIT', 'getBusinessUnitClass', 2],
            ['ORGANIZATION', 'getOrganizationClass', 1],
        ];
    }
}

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
        $this->configManager             = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->relationBuilder           = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Tools\RelationBuilder')
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

    public function testSupportsPostUpdate()
    {
        $this->configManager->expects($this->never())
            ->method('getProvider');

        $this->assertFalse(
            $this->extension->supports(ExtendConfigDumper::ACTION_POST_UPDATE)
        );
    }

    public function testSupportsPreUpdate()
    {
        $config1 = new Config(new EntityConfigId('ownership', 'Test\Entity1'));
        $config1->set('owner_type', 'USER');
        $config2 = new Config(new EntityConfigId('ownership', 'Test\Entity2'));
        $config2->set('owner_type', 'USER');
        $config3 = new Config(new EntityConfigId('ownership', 'Test\Entity3'));

        $extendConfig1 = new Config(new EntityConfigId('extend', 'Test\Entity1'));
        $extendConfig1->set('owner', ExtendScope::OWNER_CUSTOM);

        $this->setTargetEntityConfigsExpectations([$config1, $config2, $config3], [$extendConfig1]);

        $this->assertTrue(
            $this->extension->supports(ExtendConfigDumper::ACTION_PRE_UPDATE)
        );
    }

    public function testSupportsPreUpdateNoApplicableTargetEntities()
    {
        $config1 = new Config(new EntityConfigId('ownership', 'Test\Entity1'));
        $config1->set('owner_type', 'USER');
        $config2 = new Config(new EntityConfigId('ownership', 'Test\Entity2'));
        $config2->set('owner_type', 'USER');
        $config3 = new Config(new EntityConfigId('ownership', 'Test\Entity3'));

        $extendConfig1 = new Config(new EntityConfigId('extend', 'Test\Entity1'));
        $extendConfig1->set('owner', ExtendScope::OWNER_SYSTEM);

        $this->setTargetEntityConfigsExpectations([$config1, $config2, $config3], [$extendConfig1]);

        $this->assertFalse(
            $this->extension->supports(ExtendConfigDumper::ACTION_PRE_UPDATE)
        );
    }

    /**
     * @dataProvider preUpdateProvider
     */
    public function testPreUpdate($ownerType, $getOwnerClassMethodName)
    {
        $config1 = new Config(new EntityConfigId('ownership', 'Test\Entity1'));
        $config1->set('owner_type', $ownerType);
        $config1->set('owner_field_name', 'owner_field');
        $config2 = new Config(new EntityConfigId('ownership', 'Test\Entity2'));
        $config2->set('owner_type', $ownerType);
        $config3 = new Config(new EntityConfigId('ownership', 'Test\Entity3'));

        $extendConfig1 = new Config(new EntityConfigId('extend', 'Test\Entity1'));
        $extendConfig1->set('owner', ExtendScope::OWNER_CUSTOM);

        $this->setTargetEntityConfigsExpectations([$config1, $config2, $config3], [$extendConfig1]);

        $this->ownershipMetadataProvider->expects($this->once())
            ->method($getOwnerClassMethodName)
            ->will($this->returnValue('Test\Owner'));

        $this->relationBuilder->expects($this->once())
            ->method('addFieldConfig')
            ->with(
                'Test\Entity1',
                'owner_field',
                'manyToOne',
                [
                    'extend'    => [
                        'owner'         => ExtendScope::OWNER_SYSTEM,
                        'state'         => ExtendScope::STATE_NEW,
                        'extend'        => true,
                        'target_entity' => 'Test\Owner',
                        'target_field'  => 'id',
                        'relation_key'  => 'manyToOne|Test\Entity1|Test\Owner|owner_field',
                    ],
                    'entity'    => [
                        'label'       => 'oro.custom_entity.owner.label',
                        'description' => 'oro.custom_entity.owner.description',
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
        $this->relationBuilder->expects($this->once())
            ->method('addManyToOneRelation')
            ->with(
                'Test\Owner',
                'Test\Entity1',
                'owner_field',
                'manyToOne|Test\Entity1|Test\Owner|owner_field'
            );

        $extendConfigs = [];
        $this->extension->preUpdate($extendConfigs);
    }

    public function preUpdateProvider()
    {
        return [
            ['USER', 'getUserClass'],
            ['BUSINESS_UNIT', 'getBusinessUnitClass'],
            ['ORGANIZATION', 'getOrganizationClass'],
        ];
    }

    /**
     * @param Config[] $ownershipConfigs
     * @param Config[] $extendConfigs
     */
    protected function setTargetEntityConfigsExpectations(array $ownershipConfigs = [], array $extendConfigs = [])
    {
        $ownershipConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $ownershipConfigProvider->expects($this->once())
            ->method('getConfigs')
            ->will($this->returnValue($ownershipConfigs));

        $extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $extendConfigProvider->expects($this->any())
            ->method('hasConfig')
            ->will(
                $this->returnCallback(
                    function ($className, $fieldName) use ($extendConfigs) {
                        foreach ($extendConfigs as $extendConfig) {
                            if ($extendConfig->getId()->getClassName() === $className) {
                                return true;
                            }
                        }

                        return false;
                    }
                )
            );
        $extendConfigProvider->expects($this->any())
            ->method('getConfig')
            ->will(
                $this->returnCallback(
                    function ($className, $fieldName) use ($extendConfigs) {
                        foreach ($extendConfigs as $extendConfig) {
                            if ($extendConfig->getId()->getClassName() === $className) {
                                return $extendConfig;
                            }
                        }

                        throw new RuntimeException(sprintf('Entity "%s" is not configurable', $className));
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
    }
}

<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\EventListener\BeforeMapObjectSearchListener;
use Oro\Bundle\SearchBundle\Event\SearchMappingCollectEvent;

class BeforeMapObjectSearchListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var BeforeMapObjectSearchListener */
    protected $listener;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    protected $expectedConfig = [
        'Oro\TestBundle\Entity\Test'   => [
            'title_fields' => ['name', 'second'],
            'fields'       => [
                [
                    'name'          => 'name',
                    'target_type'   => 'text',
                    'target_fields' => ['name']
                ],
                [
                    'name'          => 'first',
                    'target_type'   => 'integer',
                    'target_fields' => ['first']
                ],
                [
                    'name'          => 'second',
                    'target_type'   => 'text',
                    'target_fields' => ['second']
                ]
            ]
        ],
        'Oro\TestBundle\Entity\Custom' => [
            'alias'           => null,
            'label'           => 'custom',
            'title_fields'    => ['string'],
            'route'           => [
                'name'       => 'oro_entity_view',
                'parameters' => [
                    'id'         => 'id',
                    'entityName' => '@Oro_TestBundle_Entity_Custom@'
                ]
            ],
            'search_template' => 'OroEntityExtendBundle:Search:result.html.twig',
            'fields'          => [
                [
                    'name'          => 'first',
                    'target_type'   => 'decimal',
                    'target_fields' => ['first']
                ],
                [
                    'name'          => 'string',
                    'target_type'   => 'text',
                    'target_fields' => ['string']
                ]
            ],
            'mode'            => 'normal'
        ]
    ];

    public function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new BeforeMapObjectSearchListener($this->configManager);
    }

    public function testPrepareEntityMapEvent()
    {
        $mappingConfig      = [
            'Oro\TestBundle\Entity\Test' => [
                'title_fields' => ['name'],
                'fields'       => [
                    [
                        'name'          => 'name',
                        'target_type'   => 'text',
                        'target_fields' => ['name']
                    ]
                ]
            ]
        ];
        $testEntityConfigId = new EntityConfigId('extend', 'Oro\TestBundle\Entity\Test');
        $testEntityConfig   = new Config($testEntityConfigId);
        $testEntityConfig->set('is_extend', true);
        $testEntityConfig->set('state', ExtendScope::STATE_ACTIVE);
        $testEntityConfig->set('owner', ExtendScope::OWNER_SYSTEM);
        $testEntityConfig->set('label', 'test');
        $testEntityFirstField       = new FieldConfigId('search', 'Oro\TestBundle\Entity\Test', 'first', 'integer');
        $testEntityFirstFieldConfig = new Config($testEntityFirstField);
        $testEntityFirstFieldConfig->set('searchable', true);
        $testEntitySecondField  = new FieldConfigId('search', 'Oro\TestBundle\Entity\Test', 'second', 'string');
        $testEntitySecondConfig = new Config($testEntitySecondField);
        $testEntitySecondConfig->set('searchable', true);
        $testEntitySecondConfig->set('title_field', true);
        $testEntitySearchConfigs = [$testEntityFirstFieldConfig, $testEntitySecondConfig];
        $customEntityConfigId    = new EntityConfigId('extend', 'Oro\TestBundle\Entity\Custom');
        $customEntityConfig      = new Config($customEntityConfigId);
        $customEntityConfig->set('is_extend', true);
        $customEntityConfig->set('state', ExtendScope::STATE_ACTIVE);
        $customEntityConfig->set('owner', ExtendScope::ORIGIN_CUSTOM);
        $customEntityConfig->set('label', 'custom');
        $customEntityFirstField       = new FieldConfigId('search', 'Oro\TestBundle\Entity\Custom', 'first', 'percent');
        $customEntityFirstFieldConfig = new Config($customEntityFirstField);
        $customEntityFirstFieldConfig->set('searchable', true);
        $customEntitySecondField  = new FieldConfigId('search', 'Oro\TestBundle\Entity\Custom', 'string', 'string');
        $customEntitySecondConfig = new Config($customEntitySecondField);
        $customEntitySecondConfig->set('searchable', true);
        $customEntitySecondConfig->set('title_field', true);
        $customEntitySearchConfigs = [$customEntityFirstFieldConfig, $customEntitySecondConfig];
        $extendConfigs             = [$testEntityConfig, $customEntityConfig];
        $searchProvider            = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $searchProvider->expects($this->once())
            ->method('getConfig')
            ->willReturn($customEntityFirstFieldConfig);
        $extendProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $extendProvider->expects($this->once())
            ->method('getConfigs')
            ->willReturn($extendConfigs);
        $this->configManager->expects($this->any())
            ->method('getConfigs')
            ->willReturnCallback(
                function ($configScope, $className) use ($testEntitySearchConfigs, $customEntitySearchConfigs) {
                    if ($className === 'Oro\TestBundle\Entity\Test') {
                        return $testEntitySearchConfigs;
                    }

                    return $customEntitySearchConfigs;
                }
            );
        $entityProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $entityProvider->expects($this->any())
            ->method('getConfig')
            ->willReturnCallback(
                function ($className) use ($testEntityConfig, $customEntityConfig) {
                    if ($className === 'Oro\TestBundle\Entity\Test') {
                        return $testEntityConfig;
                    }

                    return $customEntityConfig;
                }
            );
        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->willReturnCallback(
                function ($configScope) use ($extendProvider, $searchProvider, $entityProvider) {
                    if ($configScope === 'extend') {
                        return $extendProvider;
                    }
                    if ($configScope === 'search') {
                        return $searchProvider;
                    }
                    return $entityProvider;
                }
            );
        $event = new SearchMappingCollectEvent($mappingConfig);
        $this->listener->prepareEntityMapEvent($event);
        $this->assertEquals($this->expectedConfig, $event->getMappingConfig());
    }
}

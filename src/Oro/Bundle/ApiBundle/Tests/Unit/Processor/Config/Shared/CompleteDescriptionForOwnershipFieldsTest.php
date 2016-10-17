<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\Shared;

use Oro\Bundle\ApiBundle\Processor\Config\Shared\CompleteDescriptionForOwnershipFields;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;

class CompleteDescriptionForOwnershipFieldsTest extends ConfigProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configProvider;

    /** @var CompleteDescriptionForOwnershipFields */
    protected $processor;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new CompleteDescriptionForOwnershipFields($this->configProvider);
    }

    public function testWithNoTargetAction()
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id'],
            'fields'                 => [
                'id'           => null,
                'name'         => null,
                'owner'        => null,
                'organization' => null,
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            $config,
            $this->context->getResult()->toArray()
        );
    }

    public function testWithNonConfigurableEntity()
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id'],
            'fields'                 => [
                'id'           => null,
                'name'         => null,
                'owner'        => null,
                'organization' => null,
            ]
        ];

        $this->context->setTargetAction('get_list');
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            $config,
            $this->context->getResult()->toArray()
        );
    }

    public function testWithNoConfiguredOwnershipFields()
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id'],
            'fields'                 => [
                'id'           => null,
                'name'         => null,
                'owner'        => null,
                'organization' => null,
            ]
        ];

        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->with('Test\Class')
            ->willReturn(true);

        $entityConfigId = new EntityConfigId('ownership', 'Test\Class');
        $entityConfig = new Config($entityConfigId, []);

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with('Test\Class')
            ->willReturn($entityConfig);

        $this->context->setTargetAction('get_list');
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            $config,
            $this->context->getResult()->toArray()
        );
    }

    public function testDescriptionIsSetForOwnerField()
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id'],
            'fields'                 => [
                'id'           => null,
                'name'         => null,
                'owner'        => null,
                'organization' => null,
            ]
        ];

        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->with('Test\Class')
            ->willReturn(true);

        $entityConfigId = new EntityConfigId('ownership', 'Test\Class');
        $entityConfig = new Config(
            $entityConfigId,
            [
                'owner_field_name' => 'owner'
            ]
        );

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with('Test\Class')
            ->willReturn($entityConfig);

        $this->context->setTargetAction('get_list');
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'           => null,
                    'name'         => null,
                    'owner'        => [
                        'description' => CompleteDescriptionForOwnershipFields::OWNER_FIELD_DESCRIPTION
                    ],
                    'organization' => null,
                ]

            ],
            $this->context->getResult()->toArray()
        );
    }

    public function testDescriptionIsSetForOrganizationField()
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id'],
            'fields'                 => [
                'id'           => null,
                'name'         => null,
                'owner'        => null,
                'organization' => null,
            ]
        ];

        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->with('Test\Class')
            ->willReturn(true);

        $entityConfigId = new EntityConfigId('ownership', 'Test\Class');
        $entityConfig = new Config(
            $entityConfigId,
            [
                'organization_field_name' => 'organization'
            ]
        );

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with('Test\Class')
            ->willReturn($entityConfig);

        $this->context->setTargetAction('get_list');
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'           => null,
                    'name'         => null,
                    'owner'        => null,
                    'organization' => [
                        'description' => CompleteDescriptionForOwnershipFields::ORGANIZATION_FIELD_DESCRIPTION
                    ]
                ]

            ],
            $this->context->getResult()->toArray()
        );
    }

    public function testDescriptionIsSetForAllOwnershipFields()
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id'],
            'fields'                 => [
                'id'           => null,
                'name'         => null,
                'owner'        => null,
                'organization' => null,
            ]
        ];

        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->with('Test\Class')
            ->willReturn(true);

        $entityConfigId = new EntityConfigId('ownership', 'Test\Class');
        $entityConfig = new Config(
            $entityConfigId,
            [
                'owner_field_name'        => 'owner',
                'organization_field_name' => 'organization'
            ]
        );

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with('Test\Class')
            ->willReturn($entityConfig);

        $this->context->setTargetAction('get_list');
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'           => null,
                    'name'         => null,
                    'owner'        => [
                        'description' => CompleteDescriptionForOwnershipFields::OWNER_FIELD_DESCRIPTION
                    ],
                    'organization' => [
                        'description' => CompleteDescriptionForOwnershipFields::ORGANIZATION_FIELD_DESCRIPTION
                    ]
                ]

            ],
            $this->context->getResult()->toArray()
        );
    }

    public function testDescriptionIsSetForAllOwnershipFieldsWithDifferentNames()
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id'],
            'fields'                 => [
                'id'                     => null,
                'name'                   => null,
                'owner'                  => null,
                'different_owner'        => null,
                'organization'           => null,
                'different_organization' => null
            ]
        ];

        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->with('Test\Class')
            ->willReturn(true);

        $entityConfigId = new EntityConfigId('ownership', 'Test\Class');
        $entityConfig = new Config(
            $entityConfigId,
            [
                'owner_field_name'        => 'different_owner',
                'organization_field_name' => 'different_organization'
            ]
        );

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with('Test\Class')
            ->willReturn($entityConfig);

        $this->context->setTargetAction('get_list');
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'                     => null,
                    'name'                   => null,
                    'owner'                  => null,
                    'different_owner'        => [
                        'description' => CompleteDescriptionForOwnershipFields::OWNER_FIELD_DESCRIPTION
                    ],
                    'organization'           => null,
                    'different_organization' => [
                        'description' => CompleteDescriptionForOwnershipFields::ORGANIZATION_FIELD_DESCRIPTION
                    ]
                ]

            ],
            $this->context->getResult()->toArray()
        );
    }
}

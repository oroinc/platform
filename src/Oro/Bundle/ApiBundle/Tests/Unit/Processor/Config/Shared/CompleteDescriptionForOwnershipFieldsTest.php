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
                'owner'        => null,
                'organization' => null,
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            $config,
            $this->context->getResult()
        );
    }

    public function testForNonConfigurableEntity()
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id'],
            'fields'                 => [
                'id'           => null,
                'owner'        => null,
                'organization' => null,
            ]
        ];

        $this->context->setTargetAction('get_list');
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            $config,
            $this->context->getResult()
        );
    }

    public function testWithoutConfiguredOwnershipFields()
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id'],
            'fields'                 => [
                'id'           => null,
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
            $this->context->getResult()
        );
    }

    public function testDescriptionIsSetForOwnerField()
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id'],
            'fields'                 => [
                'id'           => null,
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
                    'owner'        => [
                        'description' => 'An Owner record represents the ownership capabilities of the record'
                    ],
                    'organization' => null,
                ]

            ],
            $this->context->getResult()
        );
    }

    public function testDescriptionIsSetForOrganizationField()
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id'],
            'fields'                 => [
                'id'           => null,
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
                    'owner'        => null,
                    'organization' => [
                        'description' => 'An Organization record represents a real enterprise, business, firm, '
                            . 'company or another organization, to which the record belongs'
                    ]
                ]

            ],
            $this->context->getResult()
        );
    }

    public function testDescriptionIsSetForRenamedOwnerField()
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id'],
            'fields'                 => [
                'id'           => null,
                'owner2'       => ['property_path' => 'owner1'],
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
                'owner_field_name' => 'owner1'
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
                    'owner2'       => [
                        'property_path' => 'owner1',
                        'description'   => 'An Owner record represents the ownership capabilities of the record'
                    ],
                    'organization' => null,
                ]

            ],
            $this->context->getResult()
        );
    }

    public function testDescriptionIsSetForRenamedOrganizationField()
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id'],
            'fields'                 => [
                'id'            => null,
                'owner'         => null,
                'organization2' => ['property_path' => 'organization1'],
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
                'organization_field_name' => 'organization1'
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
                    'id'            => null,
                    'owner'         => null,
                    'organization2' => [
                        'property_path' => 'organization1',
                        'description'   => 'An Organization record represents a real enterprise, business, firm, '
                            . 'company or another organization, to which the record belongs'
                    ]
                ]

            ],
            $this->context->getResult()
        );
    }
}

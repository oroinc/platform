<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\Shared;

use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\ExpandRelatedEntitiesConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Config\Shared\ExpandRelatedEntities;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\TestConfigSection;

class ExpandRelatedEntitiesTest extends ConfigProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configProvider;

    /** @var ExpandRelatedEntities */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\ApiBundle\Util\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configProvider = $this
            ->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new ExpandRelatedEntities(
            $this->doctrineHelper,
            $this->configProvider
        );
    }

    public function testProcessForAlreadyProcessedConfig()
    {
        $config = [
            'exclusion_policy' => 'all'
        ];

        $this->doctrineHelper->expects($this->never())
            ->method('isManageableEntityClass');

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);
    }

    public function testProcessForNotManageableEntity()
    {
        $config = [
            'fields' => [
                'field1'       => null,
                'association1' => [
                    'target_class' => 'Test\Association1Target'
                ],
                'association2' => [
                    'target_class'  => 'Test\Association2Target',
                    'property_path' => 'realAssociation2'
                ],
                'association3' => [
                    'target_class' => 'Test\Association3Target'
                ],
            ]
        ];

        $this->context->setExtras(
            [
                new ExpandRelatedEntitiesConfigExtra(
                    ['field1', 'association1', 'association2', 'association3', 'association4']
                ),
                new TestConfigSection('test_section')
            ]
        );

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityMetadataForClass');

        $this->configProvider->expects($this->exactly(3))
            ->method('getConfig')
            ->willReturnMap(
                [
                    [
                        'Test\Association1Target',
                        $this->context->getVersion(),
                        $this->context->getRequestType(),
                        $this->context->getPropagableExtras(),
                        $this->createRelationConfigObject(['exclusion_policy' => 'all'], ['attr' => 'val'])
                    ],
                    [
                        'Test\Association2Target',
                        $this->context->getVersion(),
                        $this->context->getRequestType(),
                        $this->context->getPropagableExtras(),
                        $this->createRelationConfigObject(['exclusion_policy' => 'all'], ['attr' => 'val'])
                    ],
                    [
                        'Test\Association3Target',
                        $this->context->getVersion(),
                        $this->context->getRequestType(),
                        $this->context->getPropagableExtras(),
                        $this->createRelationConfigObject(['exclusion_policy' => 'all'])
                    ],
                ]
            );

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'field1'       => null,
                    'association1' => [
                        'target_class'     => 'Test\Association1Target',
                        'exclusion_policy' => 'all',
                        'test_section'     => ['attr' => 'val']
                    ],
                    'association2' => [
                        'target_class'     => 'Test\Association2Target',
                        'property_path'    => 'realAssociation2',
                        'exclusion_policy' => 'all',
                        'test_section'     => ['attr' => 'val']
                    ],
                    'association3' => [
                        'target_class'     => 'Test\Association3Target',
                        'exclusion_policy' => 'all'
                    ],
                ]
            ],
            $this->context->getResult()
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessForManageableEntity()
    {
        $config = [
            'fields' => [
                'association2' => null,
                'association3' => [
                    'property_path' => 'realAssociation3'
                ]
            ]
        ];

        $this->context->setExtras(
            [
                new ExpandRelatedEntitiesConfigExtra(
                    ['field1', 'association1', 'association2', 'association3', 'association4']
                ),
                new TestConfigSection('test_section')
            ]
        );

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->exactly(5))
            ->method('hasAssociation')
            ->willReturnMap(
                [
                    ['field1', false],
                    ['association1', true],
                    ['association2', true],
                    ['realAssociation3', true],
                    ['association4', true],
                ]
            );
        $rootEntityMetadata->expects($this->exactly(4))
            ->method('getAssociationTargetClass')
            ->willReturnMap(
                [
                    ['association1', 'Test\Association1Target'],
                    ['association2', 'Test\Association2Target'],
                    ['realAssociation3', 'Test\Association3Target'],
                    ['association4', 'Test\Association4Target'],
                ]
            );

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->configProvider->expects($this->exactly(4))
            ->method('getConfig')
            ->willReturnMap(
                [
                    [
                        'Test\Association1Target',
                        $this->context->getVersion(),
                        $this->context->getRequestType(),
                        $this->context->getPropagableExtras(),
                        $this->createRelationConfigObject(['exclusion_policy' => 'all'], ['attr' => 'val'])
                    ],
                    [
                        'Test\Association2Target',
                        $this->context->getVersion(),
                        $this->context->getRequestType(),
                        $this->context->getPropagableExtras(),
                        $this->createRelationConfigObject(['exclusion_policy' => 'all'], ['attr' => 'val'])
                    ],
                    [
                        'Test\Association3Target',
                        $this->context->getVersion(),
                        $this->context->getRequestType(),
                        $this->context->getPropagableExtras(),
                        $this->createRelationConfigObject(['exclusion_policy' => 'all'])
                    ],
                    [
                        'Test\Association4Target',
                        $this->context->getVersion(),
                        $this->context->getRequestType(),
                        $this->context->getPropagableExtras(),
                        $this->createRelationConfigObject()
                    ],
                ]
            );

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'association1' => [
                        'exclusion_policy' => 'all',
                        'target_class'     => 'Test\Association1Target',
                        'test_section'     => ['attr' => 'val']
                    ],
                    'association2' => [
                        'exclusion_policy' => 'all',
                        'target_class'     => 'Test\Association2Target',
                        'test_section'     => ['attr' => 'val']
                    ],
                    'association3' => [
                        'exclusion_policy' => 'all',
                        'property_path'    => 'realAssociation3',
                        'target_class'     => 'Test\Association3Target'
                    ],
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessWhenThirdLevelEntityShouldBeExpanded()
    {
        $config = [];

        $this->context->setExtras(
            [
                new ExpandRelatedEntitiesConfigExtra(['association1.association11'])
            ]
        );

        $rootEntityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $rootEntityMetadata->expects($this->once())
            ->method('hasAssociation')
            ->with('association1')
            ->willReturn(true);
        $rootEntityMetadata->expects($this->once())
            ->method('getAssociationTargetClass')
            ->with('association1')
            ->willReturn('Test\Association1Target');

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($rootEntityMetadata);

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(
                'Test\Association1Target',
                $this->context->getVersion(),
                $this->context->getRequestType(),
                array_merge(
                    $this->context->getPropagableExtras(),
                    [new ExpandRelatedEntitiesConfigExtra(['association11'])]
                )
            )
            ->willReturn($this->createRelationConfigObject(['exclusion_policy' => 'all']));

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'association1' => [
                        'exclusion_policy' => 'all',
                        'target_class'     => 'Test\Association1Target'
                    ],
                ]
            ],
            $this->context->getResult()
        );
    }

    /**
     * @param array|null $definition
     * @param array|null $testSection
     *
     * @return Config
     */
    protected function createRelationConfigObject(array $definition = null, array $testSection = null)
    {
        $config = new Config();
        if (null !== $definition) {
            $config->setDefinition($this->createConfigObject($definition));
        }
        if (null !== $testSection) {
            $config->set('test_section', $testSection);
        }

        return $config;
    }
}

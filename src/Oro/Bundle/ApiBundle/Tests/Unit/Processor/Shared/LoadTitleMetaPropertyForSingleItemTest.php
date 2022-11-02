<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\Extra\ExpandRelatedEntitiesConfigExtra;
use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Processor\Shared\LoadTitleMetaProperty;
use Oro\Bundle\ApiBundle\Processor\Shared\LoadTitleMetaPropertyForCollection;
use Oro\Bundle\ApiBundle\Processor\Shared\LoadTitleMetaPropertyForSingleItem;
use Oro\Bundle\ApiBundle\Provider\EntityTitleProvider;
use Oro\Bundle\ApiBundle\Provider\ExpandedAssociationExtractor;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class LoadTitleMetaPropertyForSingleItemTest extends GetProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityTitleProvider */
    private $entityTitleProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ExpandedAssociationExtractor */
    private $expandedAssociationExtractor;

    /** @var LoadTitleMetaPropertyForCollection */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityTitleProvider = $this->createMock(EntityTitleProvider::class);
        $this->expandedAssociationExtractor = $this->createMock(ExpandedAssociationExtractor::class);

        $this->processor = new LoadTitleMetaPropertyForSingleItem(
            $this->entityTitleProvider,
            $this->expandedAssociationExtractor,
            $this->configProvider
        );
    }

    private function getConfig(array $identifierFieldNames = ['id']): EntityDefinitionConfig
    {
        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames($identifierFieldNames);
        foreach ($identifierFieldNames as $fieldName) {
            $config->addField($fieldName)->setDataType('integer');
        }

        return $config;
    }

    private function addTitleMetaProperty(EntityDefinitionConfig $config)
    {
        $titleField = $config->addField('__title__');
        $titleField->setMetaProperty(true);
        $titleField->setMetaPropertyResultName('title');
    }

    public function testProcessForNullData()
    {
        $this->processor->process($this->context);
    }

    public function testProcessForEmptyData()
    {
        $this->context->setResult([]);
        $this->processor->process($this->context);
    }

    public function testProcessForNotArrayData()
    {
        $this->context->setResult(123);
        $this->processor->process($this->context);
    }

    public function testProcessWhenTitleMetaPropertyWasNotRequested()
    {
        $config = $this->getConfig();

        $this->context->setClassName('Test\Entity');
        $this->context->setConfig($config);
        $this->context->setResult(['id' => 123]);
        $this->processor->process($this->context);
    }

    public function testProcessWhenTitlesAreAlreadyProcessed()
    {
        $config = $this->getConfig();
        $this->addTitleMetaProperty($config);

        $this->context->setProcessed(LoadTitleMetaProperty::OPERATION_NAME);
        $this->context->setClassName('Test\Entity');
        $this->context->setConfig($config);
        $this->context->setResult(['id' => 123]);
        $this->processor->process($this->context);
    }

    public function testProcessForPrimaryEntityOnly()
    {
        $config = $this->getConfig();
        $this->addTitleMetaProperty($config);

        $data = ['id' => 123];

        $titles = [
            ['entity' => 'Test\Entity', 'id' => 123, 'title' => 'title 123']
        ];

        $identifierMap = [
            'Test\Entity' => ['id', [123]]
        ];

        $this->expandedAssociationExtractor->expects(self::never())
            ->method('getExpandedAssociations');
        $this->entityTitleProvider->expects(self::once())
            ->method('getTitles')
            ->with($identifierMap)
            ->willReturn($titles);

        $this->context->setClassName('Test\Entity');
        $this->context->setConfig($config);
        $this->context->setResult($data);
        $this->processor->process($this->context);

        self::assertEquals(
            ['id' => 123, '__title__' => 'title 123'],
            $this->context->getResult()
        );
    }

    public function testProcessForExpandedEntities()
    {
        $config = $this->getConfig();
        $this->addTitleMetaProperty($config);
        $associationField = $config->addField('association');
        $associationField->setTargetClass('Test\TargetEntity1');
        $associationTargetConfig = $associationField->createAndSetTargetEntity();
        $associationTargetConfig->setIdentifierFieldNames(['id']);
        $associationTargetConfig->addField('id')->setDataType('integer');
        $associationTargetConfig->addField('name')->setDataType('string');

        $expandConfigExtra = new ExpandRelatedEntitiesConfigExtra([
            'association'
        ]);

        $data = [
            'id'          => 123,
            'association' => [
                'id'   => 1,
                'name' => 'association 1'
            ]
        ];

        $expandedAssociations = [
            'association' => $config->getField('association')
        ];

        $titles = [
            ['entity' => 'Test\Entity', 'id' => 123, 'title' => 'title 123'],
            ['entity' => 'Test\TargetEntity1', 'id' => 1, 'title' => 'association title 1']
        ];

        $identifierMap = [
            'Test\Entity'        => ['id', [123]],
            'Test\TargetEntity1' => ['id', [1]]
        ];

        $this->expandedAssociationExtractor->expects(self::exactly(2))
            ->method('getExpandedAssociations')
            ->willReturnCallback(function ($conf) use ($config, $associationTargetConfig, $expandedAssociations) {
                if ($conf == $config) {
                    return $expandedAssociations;
                }
                if ($conf == $associationTargetConfig) {
                    return [];
                }
                throw new \LogicException('Unexpected config');
            });
        $this->entityTitleProvider->expects(self::once())
            ->method('getTitles')
            ->with($identifierMap)
            ->willReturn($titles);

        $this->context->setClassName('Test\Entity');
        $this->context->setConfig($config);
        $this->context->addConfigExtra($expandConfigExtra);
        $this->context->setResult($data);
        $this->processor->process($this->context);

        self::assertEquals(
            [
                'id'          => 123,
                '__title__'   => 'title 123',
                'association' => [
                    'id'        => 1,
                    'name'      => 'association 1',
                    '__title__' => 'association title 1'
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForResourceBasedOnAnotherResource()
    {
        $config = $this->getConfig();
        $config->setParentResourceClass('Test\ParentEntity');
        $this->addTitleMetaProperty($config);

        $expandConfigExtra = new ExpandRelatedEntitiesConfigExtra([
            'association'
        ]);

        $data = ['id' => 123];

        $expandedAssociations = [];

        $titles = [
            ['entity' => 'Test\ParentEntity', 'id' => 123, 'title' => 'title 123']
        ];

        $identifierMap = [
            'Test\ParentEntity' => ['id', [123]]
        ];

        $this->expandedAssociationExtractor->expects(self::once())
            ->method('getExpandedAssociations')
            ->with(self::identicalTo($config))
            ->willReturn($expandedAssociations);
        $this->entityTitleProvider->expects(self::once())
            ->method('getTitles')
            ->with($identifierMap)
            ->willReturn($titles);

        $this->context->setClassName('Test\Entity');
        $this->context->setConfig($config);
        $this->context->addConfigExtra($expandConfigExtra);
        $this->context->setResult($data);
        $this->processor->process($this->context);

        self::assertEquals(
            ['id' => 123, '__title__' => 'title 123'],
            $this->context->getResult()
        );
    }

    public function testProcessForEntitiesWithRenamedIdentifierFields()
    {
        $config = $this->getConfig(['renamedId']);
        $config->getField('renamedId')->setPropertyPath('realId');
        $this->addTitleMetaProperty($config);
        $associationField = $config->addField('association');
        $associationField->setTargetClass('Test\TargetEntity1');
        $associationTargetConfig = $associationField->createAndSetTargetEntity();
        $associationTargetConfig->setIdentifierFieldNames(['associationRenamedId']);
        $associationIdField = $associationTargetConfig->addField('associationRenamedId');
        $associationIdField->setPropertyPath('associationRealId');
        $associationIdField->setDataType('integer');
        $associationTargetConfig->addField('name')->setDataType('string');

        $expandConfigExtra = new ExpandRelatedEntitiesConfigExtra([
            'association'
        ]);

        $data = [
            'renamedId'   => 123,
            'association' => [
                'associationRenamedId' => 1,
                'name'                 => 'association 1'
            ]
        ];

        $expandedAssociations = [
            'association' => $config->getField('association')
        ];

        $titles = [
            ['entity' => 'Test\Entity', 'id' => 123, 'title' => 'title 123'],
            ['entity' => 'Test\TargetEntity1', 'id' => 1, 'title' => 'association title 1']
        ];

        $identifierMap = [
            'Test\Entity'        => ['realId', [123]],
            'Test\TargetEntity1' => ['associationRealId', [1]]
        ];

        $this->expandedAssociationExtractor->expects(self::exactly(2))
            ->method('getExpandedAssociations')
            ->willReturnCallback(function ($conf) use ($config, $associationTargetConfig, $expandedAssociations) {
                if ($conf == $config) {
                    return $expandedAssociations;
                }
                if ($conf == $associationTargetConfig) {
                    return [];
                }
                throw new \LogicException('Unexpected config');
            });
        $this->entityTitleProvider->expects(self::once())
            ->method('getTitles')
            ->with($identifierMap)
            ->willReturn($titles);

        $this->context->setClassName('Test\Entity');
        $this->context->setConfig($config);
        $this->context->addConfigExtra($expandConfigExtra);
        $this->context->setResult($data);
        $this->processor->process($this->context);

        self::assertEquals(
            [
                'renamedId'   => 123,
                '__title__'   => 'title 123',
                'association' => [
                    'associationRenamedId' => 1,
                    'name'                 => 'association 1',
                    '__title__'            => 'association title 1'
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForAssociationWithoutConfiguredIdentifierField()
    {
        $config = $this->getConfig();
        $this->addTitleMetaProperty($config);
        $associationField = $config->addField('association');
        $associationField->setTargetClass('Test\TargetEntity1');
        $associationTargetConfig = $associationField->createAndSetTargetEntity();
        $associationTargetConfig->setIdentifierFieldNames(['associationId']);

        $expandConfigExtra = new ExpandRelatedEntitiesConfigExtra([
            'association'
        ]);

        $data = [
            'id'          => 123,
            'association' => [
                'associationId' => 1,
                'name'          => 'association 1'
            ]
        ];

        $expandedAssociations = [
            'association' => $config->getField('association')
        ];

        $titles = [
            ['entity' => 'Test\Entity', 'id' => 123, 'title' => 'title 123'],
            ['entity' => 'Test\TargetEntity1', 'id' => 1, 'title' => 'association title 1']
        ];

        $identifierMap = [
            'Test\Entity'        => ['id', [123]],
            'Test\TargetEntity1' => ['associationId', [1]]
        ];

        $this->expandedAssociationExtractor->expects(self::exactly(2))
            ->method('getExpandedAssociations')
            ->willReturnCallback(function ($conf) use ($config, $associationTargetConfig, $expandedAssociations) {
                if ($conf == $config) {
                    return $expandedAssociations;
                }
                if ($conf == $associationTargetConfig) {
                    return [];
                }
                throw new \LogicException('Unexpected config');
            });
        $this->entityTitleProvider->expects(self::once())
            ->method('getTitles')
            ->with($identifierMap)
            ->willReturn($titles);

        $this->context->setClassName('Test\Entity');
        $this->context->setConfig($config);
        $this->context->addConfigExtra($expandConfigExtra);
        $this->context->setResult($data);
        $this->processor->process($this->context);

        self::assertEquals(
            [
                'id'          => 123,
                '__title__'   => 'title 123',
                'association' => [
                    'associationId' => 1,
                    'name'          => 'association 1',
                    '__title__'     => 'association title 1'
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForAssociationWithoutConfiguredIdentifierFieldNames()
    {
        $config = $this->getConfig();
        $this->addTitleMetaProperty($config);
        $associationField = $config->addField('association');
        $associationField->setTargetClass('Test\TargetEntity1');
        $associationTargetConfig = $associationField->createAndSetTargetEntity();
        $associationTargetConfig->addField('associationId')->setDataType('integer');
        $associationTargetConfig->addField('name')->setDataType('string');

        $expandConfigExtra = new ExpandRelatedEntitiesConfigExtra([
            'association'
        ]);

        $data = [
            'id'          => 123,
            'association' => [
                'associationId' => 1,
                'name'          => 'association 1'
            ]
        ];

        $expandedAssociations = [
            'association' => $config->getField('association')
        ];

        $titles = [
            ['entity' => 'Test\Entity', 'id' => 123, 'title' => 'title 123']
        ];

        $identifierMap = [
            'Test\Entity' => ['id', [123]]
        ];

        $this->expandedAssociationExtractor->expects(self::once())
            ->method('getExpandedAssociations')
            ->with(self::identicalTo($config))
            ->willReturn($expandedAssociations);
        $this->entityTitleProvider->expects(self::once())
            ->method('getTitles')
            ->with($identifierMap)
            ->willReturn($titles);

        $this->context->setClassName('Test\Entity');
        $this->context->setConfig($config);
        $this->context->addConfigExtra($expandConfigExtra);
        $this->context->setResult($data);
        $this->processor->process($this->context);

        self::assertEquals(
            [
                'id'          => 123,
                '__title__'   => 'title 123',
                'association' => [
                    'associationId' => 1,
                    'name'          => 'association 1'
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForEntitiesWithCompositeIdentifier()
    {
        $config = $this->getConfig(['renamedId1', 'id2']);
        $config->getField('renamedId1')->setPropertyPath('id1');
        $this->addTitleMetaProperty($config);
        $associationField = $config->addField('association');
        $associationField->setTargetClass('Test\TargetEntity1');
        $associationTargetConfig = $associationField->createAndSetTargetEntity();
        $associationTargetConfig->setIdentifierFieldNames(['associationRenamedId1', 'associationId2']);
        $associationId1Field = $associationTargetConfig->addField('associationRenamedId1');
        $associationId1Field->setPropertyPath('associationId1');
        $associationId1Field->setDataType('integer');
        $associationId2Field = $associationTargetConfig->addField('associationId2');
        $associationId2Field->setDataType('integer');
        $associationTargetConfig->addField('name')->setDataType('string');

        $expandConfigExtra = new ExpandRelatedEntitiesConfigExtra([
            'association'
        ]);

        $data = [
            'renamedId1'  => 1,
            'id2'         => 2,
            'association' => [
                'associationRenamedId1' => 11,
                'associationId2'        => 22,
                'name'                  => 'association 1'
            ]
        ];

        $expandedAssociations = [
            'association' => $config->getField('association')
        ];

        $titles = [
            [
                'entity' => 'Test\Entity',
                'id'     => ['id1' => 1, 'id2' => 2],
                'title'  => 'title 123'
            ],
            [
                'entity' => 'Test\TargetEntity1',
                'id'     => ['associationId1' => 11, 'associationId2' => 22],
                'title'  => 'association title 1'
            ]
        ];

        $identifierMap = [
            'Test\Entity'        => [['id1', 'id2'], [[1, 2]]],
            'Test\TargetEntity1' => [['associationId1', 'associationId2'], [[11, 22]]]
        ];

        $this->expandedAssociationExtractor->expects(self::exactly(2))
            ->method('getExpandedAssociations')
            ->willReturnCallback(function ($conf) use ($config, $associationTargetConfig, $expandedAssociations) {
                if ($conf == $config) {
                    return $expandedAssociations;
                }
                if ($conf == $associationTargetConfig) {
                    return [];
                }
                throw new \LogicException('Unexpected config');
            });
        $this->entityTitleProvider->expects(self::once())
            ->method('getTitles')
            ->with($identifierMap)
            ->willReturn($titles);

        $this->context->setClassName('Test\Entity');
        $this->context->setConfig($config);
        $this->context->addConfigExtra($expandConfigExtra);
        $this->context->setResult($data);
        $this->processor->process($this->context);

        self::assertEquals(
            [
                'renamedId1'  => 1,
                'id2'         => 2,
                '__title__'   => 'title 123',
                'association' => [
                    'associationRenamedId1' => 11,
                    'associationId2'        => 22,
                    'name'                  => 'association 1',
                    '__title__'             => 'association title 1'
                ]
            ],
            $this->context->getResult()
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessForMultiTargetAssociation()
    {
        $config = $this->getConfig();
        $this->addTitleMetaProperty($config);
        $associationField = $config->addField('association');
        $associationField->setTargetClass(EntityIdentifier::class);
        $associationTargetConfig = $associationField->createAndSetTargetEntity();
        $associationTargetConfig->setIdentifierFieldNames(['id']);
        $associationTargetConfig->addField('id')->setDataType('string');

        $multiTargetAssociationConfig = $this->getConfig();
        $multiTargetAssociationConfig->addField('name')->setDataType('string');
        $nestedAssociationField = $multiTargetAssociationConfig->addField('nestedAssociation');
        $nestedAssociationField->setTargetClass('Test\TargetEntity2');
        $nestedAssociationTargetConfig = $nestedAssociationField->createAndSetTargetEntity();
        $nestedAssociationTargetConfig->setIdentifierFieldNames(['id']);
        $nestedAssociationTargetConfig->addField('id')->setDataType('integer');
        $nestedAssociationTargetConfig->addField('name')->setDataType('string');

        $expandConfigExtra = new ExpandRelatedEntitiesConfigExtra([
            'association'
        ]);

        $data = [
            'id'          => 123,
            'association' => [
                'id'                => 1,
                '__class__'         => 'Test\TargetEntity1',
                'name'              => 'association 1',
                'nestedAssociation' => [
                    'id'   => 11,
                    'name' => 'nested association 1'
                ]
            ]
        ];

        $expandedAssociations = [
            'association' => $config->getField('association')
        ];
        $expandedAssociationsForMultiTargetAssociation = [
            'nestedAssociation' => $multiTargetAssociationConfig->getField('nestedAssociation')
        ];

        $titles = [
            ['entity' => 'Test\Entity', 'id' => 123, 'title' => 'title 123'],
            ['entity' => 'Test\TargetEntity1', 'id' => 1, 'title' => 'association title 1'],
            ['entity' => 'Test\TargetEntity2', 'id' => 11, 'title' => 'nested association title 1']
        ];

        $identifierMap = [
            'Test\Entity'        => ['id', [123]],
            'Test\TargetEntity1' => ['id', [1]],
            'Test\TargetEntity2' => ['id', [11]]
        ];

        $configContainer = new Config();
        $configContainer->setDefinition($multiTargetAssociationConfig);
        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->willReturn($configContainer);
        $this->expandedAssociationExtractor->expects(self::exactly(4))
            ->method('getExpandedAssociations')
            ->willReturnOnConsecutiveCalls(
                $expandedAssociations,
                [],
                $expandedAssociationsForMultiTargetAssociation,
                []
            );
        $this->entityTitleProvider->expects(self::once())
            ->method('getTitles')
            ->with($identifierMap)
            ->willReturn($titles);

        $this->context->setClassName('Test\Entity');
        $this->context->setConfig($config);
        $this->context->addConfigExtra($expandConfigExtra);
        $this->context->setResult($data);
        $this->processor->process($this->context);

        self::assertEquals(
            [
                'id'          => 123,
                '__title__'   => 'title 123',
                'association' => [
                    'id'                => 1,
                    '__class__'         => 'Test\TargetEntity1',
                    'name'              => 'association 1',
                    '__title__'         => 'association title 1',
                    'nestedAssociation' => [
                        'id'        => 11,
                        'name'      => 'nested association 1',
                        '__title__' => 'nested association title 1'
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }
}

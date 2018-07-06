<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Shared\LoadTitleMetaProperty;
use Oro\Bundle\ApiBundle\Processor\Shared\LoadTitleMetaPropertyForCollection;
use Oro\Bundle\ApiBundle\Provider\EntityTitleProvider;
use Oro\Bundle\ApiBundle\Provider\ExpandedAssociationExtractor;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;

class LoadTitleMetaPropertyForCollectionTest extends GetListProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityTitleProvider */
    private $entityTitleProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ExpandedAssociationExtractor */
    private $expandedAssociationExtractor;

    /** @var LoadTitleMetaPropertyForCollection */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->entityTitleProvider = $this->createMock(EntityTitleProvider::class);
        $this->expandedAssociationExtractor = $this->createMock(ExpandedAssociationExtractor::class);

        $this->processor = new LoadTitleMetaPropertyForCollection(
            $this->entityTitleProvider,
            $this->expandedAssociationExtractor
        );
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
        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['id']);
        $idField = $config->addField('id');
        $idField->setDataType('integer');

        $this->context->setClassName('Test\Entity');
        $this->context->setConfig($config);
        $this->context->setResult([['id' => 123]]);
        $this->processor->process($this->context);
    }

    public function testProcessWhenTitlesAreAlreadyProcessed()
    {
        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['id']);
        $idField = $config->addField('id');
        $idField->setDataType('integer');
        $titleField = $config->addField('__title__');
        $titleField->setMetaProperty(true);
        $titleField->setMetaPropertyResultName('title');

        $this->context->setProcessed(LoadTitleMetaProperty::OPERATION_NAME);
        $this->context->setClassName('Test\Entity');
        $this->context->setConfig($config);
        $this->context->setResult([['id' => 123]]);
        $this->processor->process($this->context);
    }

    public function testProcessForPrimaryEntityOnly()
    {
        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['id']);
        $idField = $config->addField('id');
        $idField->setDataType('integer');
        $titleField = $config->addField('__title__');
        $titleField->setMetaProperty(true);
        $titleField->setMetaPropertyResultName('title');

        $data = [
            ['id' => 123]
        ];

        $expandedAssociations = [];

        $titles = [
            ['entity' => 'Test\Entity', 'id' => 123, 'title' => 'title 123']
        ];

        $identifierMap = [
            'Test\Entity' => ['id', [123]]
        ];

        $this->expandedAssociationExtractor->expects(self::exactly(2))
            ->method('getExpandedAssociations')
            ->with(self::identicalTo($config))
            ->willReturn($expandedAssociations);
        $this->entityTitleProvider->expects(self::once())
            ->method('getTitles')
            ->with($identifierMap)
            ->willReturn($titles);

        $this->context->setClassName('Test\Entity');
        $this->context->setConfig($config);
        $this->context->setResult($data);
        $this->processor->process($this->context);

        self::assertEquals(
            [
                ['id' => 123, '__title__' => 'title 123']
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForExpandedEntities()
    {
        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['id']);
        $idField = $config->addField('id');
        $idField->setDataType('integer');
        $titleField = $config->addField('__title__');
        $titleField->setMetaProperty(true);
        $titleField->setMetaPropertyResultName('title');
        $associationField = $config->addField('association');
        $associationField->setTargetClass('Test\TargetEntity1');
        $associationTargetConfig = $associationField->createAndSetTargetEntity();
        $associationTargetConfig->setIdentifierFieldNames(['id']);
        $associationTargetConfig->addField('id')->setDataType('integer');
        $associationTargetConfig->addField('name')->setDataType('string');

        $data = [
            [
                'id'          => 123,
                'association' => [
                    'id'   => 1,
                    'name' => 'association 1'
                ]
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

        $this->expandedAssociationExtractor->expects(self::exactly(4))
            ->method('getExpandedAssociations')
            ->willReturnMap(
                [
                    [$config, $expandedAssociations],
                    [$associationTargetConfig, []]
                ]
            );
        $this->entityTitleProvider->expects(self::once())
            ->method('getTitles')
            ->with($identifierMap)
            ->willReturn($titles);

        $this->context->setClassName('Test\Entity');
        $this->context->setConfig($config);
        $this->context->setResult($data);
        $this->processor->process($this->context);

        self::assertEquals(
            [
                [
                    'id'          => 123,
                    '__title__'   => 'title 123',
                    'association' => [
                        'id'        => 1,
                        'name'      => 'association 1',
                        '__title__' => 'association title 1'
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForResourceBasedOnAnotherResource()
    {
        $config = new EntityDefinitionConfig();
        $config->setParentResourceClass('Test\ParentEntity');
        $config->setIdentifierFieldNames(['id']);
        $idField = $config->addField('id');
        $idField->setDataType('integer');
        $titleField = $config->addField('__title__');
        $titleField->setMetaProperty(true);
        $titleField->setMetaPropertyResultName('title');

        $data = [
            ['id' => 123]
        ];

        $expandedAssociations = [];

        $titles = [
            ['entity' => 'Test\ParentEntity', 'id' => 123, 'title' => 'title 123']
        ];

        $identifierMap = [
            'Test\ParentEntity' => ['id', [123]]
        ];

        $this->expandedAssociationExtractor->expects(self::exactly(2))
            ->method('getExpandedAssociations')
            ->with(self::identicalTo($config))
            ->willReturn($expandedAssociations);
        $this->entityTitleProvider->expects(self::once())
            ->method('getTitles')
            ->with($identifierMap)
            ->willReturn($titles);

        $this->context->setClassName('Test\Entity');
        $this->context->setConfig($config);
        $this->context->setResult($data);
        $this->processor->process($this->context);

        self::assertEquals(
            [
                ['id' => 123, '__title__' => 'title 123']
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForEntitiesWithRenamedIdentifierFields()
    {
        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['renamedId']);
        $idField = $config->addField('renamedId');
        $idField->setDataType('integer');
        $idField->setPropertyPath('realId');
        $titleField = $config->addField('__title__');
        $titleField->setMetaProperty(true);
        $titleField->setMetaPropertyResultName('title');
        $associationField = $config->addField('association');
        $associationField->setTargetClass('Test\TargetEntity1');
        $associationTargetConfig = $associationField->createAndSetTargetEntity();
        $associationTargetConfig->setIdentifierFieldNames(['associationRenamedId']);
        $associationIdField = $associationTargetConfig->addField('associationRenamedId');
        $associationIdField->setPropertyPath('associationRealId');
        $associationIdField->setDataType('integer');
        $associationTargetConfig->addField('name')->setDataType('string');

        $data = [
            [
                'renamedId'   => 123,
                'association' => [
                    'associationRenamedId' => 1,
                    'name'                 => 'association 1'
                ]
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

        $this->expandedAssociationExtractor->expects(self::exactly(4))
            ->method('getExpandedAssociations')
            ->willReturnMap(
                [
                    [$config, $expandedAssociations],
                    [$associationTargetConfig, []]
                ]
            );
        $this->entityTitleProvider->expects(self::once())
            ->method('getTitles')
            ->with($identifierMap)
            ->willReturn($titles);

        $this->context->setClassName('Test\Entity');
        $this->context->setConfig($config);
        $this->context->setResult($data);
        $this->processor->process($this->context);

        self::assertEquals(
            [
                [
                    'renamedId'   => 123,
                    '__title__'   => 'title 123',
                    'association' => [
                        'associationRenamedId' => 1,
                        'name'                 => 'association 1',
                        '__title__'            => 'association title 1'
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForAssociationWithoutConfiguredIdentifierField()
    {
        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['id']);
        $config->addField('id')->setDataType('integer');
        $titleField = $config->addField('__title__');
        $titleField->setMetaProperty(true);
        $titleField->setMetaPropertyResultName('title');
        $associationField = $config->addField('association');
        $associationField->setTargetClass('Test\TargetEntity1');
        $associationTargetConfig = $associationField->createAndSetTargetEntity();
        $associationTargetConfig->setIdentifierFieldNames(['associationId']);

        $data = [
            [
                'id'          => 123,
                'association' => [
                    'associationId' => 1,
                    'name'          => 'association 1'
                ]
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

        $this->expandedAssociationExtractor->expects(self::exactly(4))
            ->method('getExpandedAssociations')
            ->willReturnMap(
                [
                    [$config, $expandedAssociations],
                    [$associationTargetConfig, []]
                ]
            );
        $this->entityTitleProvider->expects(self::once())
            ->method('getTitles')
            ->with($identifierMap)
            ->willReturn($titles);

        $this->context->setClassName('Test\Entity');
        $this->context->setConfig($config);
        $this->context->setResult($data);
        $this->processor->process($this->context);

        self::assertEquals(
            [
                [
                    'id'          => 123,
                    '__title__'   => 'title 123',
                    'association' => [
                        'associationId' => 1,
                        'name'          => 'association 1',
                        '__title__'     => 'association title 1'
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForAssociationWithoutConfiguredIdentifierFieldNames()
    {
        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['id']);
        $config->addField('id')->setDataType('integer');
        $titleField = $config->addField('__title__');
        $titleField->setMetaProperty(true);
        $titleField->setMetaPropertyResultName('title');
        $associationField = $config->addField('association');
        $associationField->setTargetClass('Test\TargetEntity1');
        $associationTargetConfig = $associationField->createAndSetTargetEntity();
        $associationTargetConfig->addField('associationId')->setDataType('integer');
        $associationTargetConfig->addField('name')->setDataType('string');

        $data = [
            [
                'id'          => 123,
                'association' => [
                    'associationId' => 1,
                    'name'          => 'association 1'
                ]
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

        $this->expandedAssociationExtractor->expects(self::exactly(2))
            ->method('getExpandedAssociations')
            ->willReturnMap(
                [
                    [$config, $expandedAssociations],
                    [$associationTargetConfig, []]
                ]
            );
        $this->entityTitleProvider->expects(self::once())
            ->method('getTitles')
            ->with($identifierMap)
            ->willReturn($titles);

        $this->context->setClassName('Test\Entity');
        $this->context->setConfig($config);
        $this->context->setResult($data);
        $this->processor->process($this->context);

        self::assertEquals(
            [
                [
                    'id'          => 123,
                    '__title__'   => 'title 123',
                    'association' => [
                        'associationId' => 1,
                        'name'          => 'association 1'
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForEntitiesWithCompositeIdentifier()
    {
        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['renamedId1', 'id2']);
        $id1Field = $config->addField('renamedId1');
        $id1Field->setDataType('integer');
        $id1Field->setPropertyPath('id1');
        $id2Field = $config->addField('id2');
        $id2Field->setDataType('integer');
        $titleField = $config->addField('__title__');
        $titleField->setMetaProperty(true);
        $titleField->setMetaPropertyResultName('title');
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

        $data = [
            [
                'renamedId1'  => 1,
                'id2'         => 2,
                'association' => [
                    'associationRenamedId1' => 11,
                    'associationId2'        => 22,
                    'name'                  => 'association 1'
                ]
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

        $this->expandedAssociationExtractor->expects(self::exactly(4))
            ->method('getExpandedAssociations')
            ->willReturnMap(
                [
                    [$config, $expandedAssociations],
                    [$associationTargetConfig, []]
                ]
            );
        $this->entityTitleProvider->expects(self::once())
            ->method('getTitles')
            ->with($identifierMap)
            ->willReturn($titles);

        $this->context->setClassName('Test\Entity');
        $this->context->setConfig($config);
        $this->context->setResult($data);
        $this->processor->process($this->context);

        self::assertEquals(
            [
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
                ]
            ],
            $this->context->getResult()
        );
    }
}

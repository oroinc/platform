<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Shared\LoadTitleMetaProperty;
use Oro\Bundle\ApiBundle\Processor\Shared\LoadTitleMetaPropertyForCollection;
use Oro\Bundle\ApiBundle\Provider\EntityTitleProvider;
use Oro\Bundle\ApiBundle\Provider\ExpandedAssociationExtractor;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;

class LoadTitleMetaPropertyForCollectionTest extends GetProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityTitleProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $expandedAssociationExtractor;

    /** @var LoadTitleMetaPropertyForCollection */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->entityTitleProvider = $this->getMockBuilder(EntityTitleProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->expandedAssociationExtractor = $this->getMockBuilder(ExpandedAssociationExtractor::class)
            ->disableOriginalConstructor()
            ->getMock();

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
            'Test\Entity' => [123]
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
        $associationField->setDataType('integer');
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
            ['entity' => 'Test\TargetEntity1', 'id' => 1, 'title' => 'association title 1'],
        ];

        $identifierMap = [
            'Test\Entity'        => [123],
            'Test\TargetEntity1' => [1],
        ];

        $this->expandedAssociationExtractor->expects(self::exactly(4))
            ->method('getExpandedAssociations')
            ->willReturnMap(
                [
                    [$config, $expandedAssociations],
                    [$associationTargetConfig, []],
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
            'Test\ParentEntity' => [123]
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
}

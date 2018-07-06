<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\ApiDoc\Parser;

use Oro\Bundle\ApiBundle\ApiDoc\ApiDocDataTypeConverter;
use Oro\Bundle\ApiBundle\ApiDoc\Parser\ApiDocMetadata;
use Oro\Bundle\ApiBundle\ApiDoc\Parser\ApiDocMetadataParser;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;

class ApiDocMetadataParserTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ValueNormalizer */
    private $valueNormalizer;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ApiDocDataTypeConverter */
    private $dataTypeConverter;

    /** @var ApiDocMetadataParser */
    private $parser;

    protected function setUp()
    {
        $this->valueNormalizer = $this->createMock(ValueNormalizer::class);
        $this->dataTypeConverter = $this->createMock(ApiDocDataTypeConverter::class);

        $this->dataTypeConverter->expects(self::any())
            ->method('convertDataType')
            ->willReturnArgument(0);

        $this->parser = new ApiDocMetadataParser($this->valueNormalizer, $this->dataTypeConverter);
    }

    public function testSupportsWithoutMetadata()
    {
        $item = [
            'class'   => null,
            'options' => []
        ];

        self::assertFalse($this->parser->supports($item));
    }

    public function testSupportsWithDirectionAndMetadata()
    {
        $item = [
            'class'   => null,
            'options' => [
                'direction' => 'input',
                'metadata'  => new ApiDocMetadata(
                    'test',
                    $this->createMock(EntityMetadata::class),
                    $this->createMock(EntityDefinitionConfig::class),
                    new RequestType([])
                )
            ]
        ];

        self::assertTrue($this->parser->supports($item));
    }

    public function testSupportsWithMetadataButWithoutDirection()
    {
        $item = [
            'class'   => null,
            'options' => [
                'metadata' => new ApiDocMetadata(
                    'test',
                    $this->createMock(EntityMetadata::class),
                    $this->createMock(EntityDefinitionConfig::class),
                    new RequestType([])
                )
            ]
        ];

        self::assertFalse($this->parser->supports($item));
    }

    public function testSupportsWithUnknownMetadata()
    {
        $item = [
            'class'   => null,
            'options' => [
                'direction' => 'input',
                'metadata'  => new \stdClass()
            ]
        ];

        self::assertFalse($this->parser->supports($item));
    }

    public function testParseIdentifierFieldWithGeneratorForCreateAction()
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata();
        $metadata->setIdentifierFieldNames(['id']);
        $metadata->setHasIdentifierGenerator(true);
        $metadata->addField(new FieldMetadata('id'))->setDataType('integer');

        $config = new EntityDefinitionConfig();
        $config->addField('id')->setDescription('Field Description');

        $result = $this->parser->parse([
            'options' => [
                'direction' => 'input',
                'metadata'  => new ApiDocMetadata('create', $metadata, $config, $requestType)
            ]
        ]);

        self::assertEquals(
            [
                'id' => [
                    'required'    => true,
                    'dataType'    => 'integer',
                    'description' => 'Field Description',
                    'readonly'    => true
                ]
            ],
            $result
        );
    }

    public function testParseIdentifierFieldWithoutGeneratorForCreateAction()
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata();
        $metadata->setIdentifierFieldNames(['id']);
        $metadata->addField(new FieldMetadata('id'))->setDataType('integer');

        $config = new EntityDefinitionConfig();
        $config->addField('id')->setDescription('Field Description');

        $result = $this->parser->parse([
            'options' => [
                'direction' => 'input',
                'metadata'  => new ApiDocMetadata('create', $metadata, $config, $requestType)
            ]
        ]);

        self::assertEquals(
            [
                'id' => [
                    'required'    => true,
                    'dataType'    => 'integer',
                    'description' => 'Field Description'
                ]
            ],
            $result
        );
    }

    public function testParseIdentifierFieldForNotCreateAction()
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata();
        $metadata->setIdentifierFieldNames(['id']);
        $metadata->setHasIdentifierGenerator(true);
        $metadata->addField(new FieldMetadata('id'))->setDataType('integer');

        $config = new EntityDefinitionConfig();
        $config->addField('id')->setDescription('Field Description');

        $result = $this->parser->parse([
            'options' => [
                'direction' => 'input',
                'metadata'  => new ApiDocMetadata('update', $metadata, $config, $requestType)
            ]
        ]);

        self::assertEquals(
            [
                'id' => [
                    'required'    => true,
                    'dataType'    => 'integer',
                    'description' => 'Field Description'
                ]
            ],
            $result
        );
    }

    public function testParseNullableField()
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata();
        $metadata->setIdentifierFieldNames(['id']);

        $field = $metadata->addField(new FieldMetadata('field1'));
        $field->setDataType('string');
        $field->setIsNullable(true);

        $config = new EntityDefinitionConfig();
        $config->addField('field1')->setDescription('Field Description');

        $result = $this->parser->parse([
            'options' => [
                'direction' => 'input',
                'metadata'  => new ApiDocMetadata('create', $metadata, $config, $requestType)
            ]
        ]);

        self::assertEquals(
            [
                'field1' => [
                    'required'    => false,
                    'dataType'    => 'string',
                    'description' => 'Field Description'
                ]
            ],
            $result
        );
    }

    public function testParseNotNullableField()
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata();
        $metadata->setIdentifierFieldNames(['id']);

        $field = $metadata->addField(new FieldMetadata('field1'));
        $field->setDataType('string');

        $config = new EntityDefinitionConfig();
        $config->addField('field1')->setDescription('Field Description');

        $result = $this->parser->parse([
            'options' => [
                'direction' => 'input',
                'metadata'  => new ApiDocMetadata('create', $metadata, $config, $requestType)
            ]
        ]);

        self::assertEquals(
            [
                'field1' => [
                    'required'    => true,
                    'dataType'    => 'string',
                    'description' => 'Field Description'
                ]
            ],
            $result
        );
    }

    public function testParseNullableAssociation()
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata();
        $metadata->setIdentifierFieldNames(['id']);

        $association = $metadata->addAssociation(new AssociationMetadata('association1'));
        $association->setDataType('integer');
        $association->setTargetClassName('Test\TargetClass');
        $association->setIsNullable(true);

        $this->valueNormalizer->expects(self::any())
            ->method('normalizeValue')
            ->with('Test\TargetClass', 'entityType', self::identicalTo($requestType), false)
            ->willReturn('targets');

        $config = new EntityDefinitionConfig();
        $config->addField('association1')->setDescription('Association Description');

        $result = $this->parser->parse([
            'options' => [
                'direction' => 'input',
                'metadata'  => new ApiDocMetadata('create', $metadata, $config, $requestType)
            ]
        ]);

        self::assertEquals(
            [
                'association1' => [
                    'required'    => false,
                    'dataType'    => 'integer',
                    'description' => 'Association Description',
                    'actualType'  => null,
                    'subType'     => 'targets'
                ]
            ],
            $result
        );
    }

    public function testParseNotNullableAssociation()
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata();
        $metadata->setIdentifierFieldNames(['id']);

        $association = $metadata->addAssociation(new AssociationMetadata('association1'));
        $association->setDataType('integer');
        $association->setTargetClassName('Test\TargetClass');

        $this->valueNormalizer->expects(self::any())
            ->method('normalizeValue')
            ->with('Test\TargetClass', 'entityType', self::identicalTo($requestType), false)
            ->willReturn('targets');

        $config = new EntityDefinitionConfig();
        $config->addField('association1')->setDescription('Association Description');

        $result = $this->parser->parse([
            'options' => [
                'direction' => 'input',
                'metadata'  => new ApiDocMetadata('create', $metadata, $config, $requestType)
            ]
        ]);

        self::assertEquals(
            [
                'association1' => [
                    'required'    => true,
                    'dataType'    => 'integer',
                    'description' => 'Association Description',
                    'actualType'  => null,
                    'subType'     => 'targets'
                ]
            ],
            $result
        );
    }

    public function testParseCollectionAssociation()
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata();
        $metadata->setIdentifierFieldNames(['id']);

        $association = $metadata->addAssociation(new AssociationMetadata('association1'));
        $association->setDataType('integer');
        $association->setTargetClassName('Test\TargetClass');
        $association->setIsCollection(true);

        $this->valueNormalizer->expects(self::any())
            ->method('normalizeValue')
            ->with('Test\TargetClass', 'entityType', self::identicalTo($requestType), false)
            ->willReturn('targets');

        $config = new EntityDefinitionConfig();
        $config->addField('association1')->setDescription('Association Description');

        $result = $this->parser->parse([
            'options' => [
                'direction' => 'input',
                'metadata'  => new ApiDocMetadata('create', $metadata, $config, $requestType)
            ]
        ]);

        self::assertEquals(
            [
                'association1' => [
                    'required'    => true,
                    'dataType'    => 'integer',
                    'description' => 'Association Description',
                    'actualType'  => 'collection',
                    'subType'     => 'targets'
                ]
            ],
            $result
        );
    }

    public function testParseAssociationAsField()
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata();
        $metadata->setIdentifierFieldNames(['id']);

        $association = $metadata->addAssociation(new AssociationMetadata('association1'));
        $association->setDataType('object');
        $association->setTargetClassName('Test\TargetClass');

        $this->valueNormalizer->expects(self::any())
            ->method('normalizeValue')
            ->with('Test\TargetClass', 'entityType', self::identicalTo($requestType), false)
            ->willReturn('targets');

        $config = new EntityDefinitionConfig();
        $config->addField('association1')->setDescription('Association Description');

        $result = $this->parser->parse([
            'options' => [
                'direction' => 'input',
                'metadata'  => new ApiDocMetadata('create', $metadata, $config, $requestType)
            ]
        ]);

        self::assertEquals(
            [
                'association1' => [
                    'required'    => true,
                    'dataType'    => 'object',
                    'description' => 'Association Description'
                ]
            ],
            $result
        );
    }

    public function testParseAssociationIsPartOfIdentifierWithGenerator()
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata();
        $metadata->setIdentifierFieldNames(['id', 'association1']);
        $metadata->setHasIdentifierGenerator(true);

        $association = $metadata->addAssociation(new AssociationMetadata('association1'));
        $association->setDataType('integer');
        $association->setTargetClassName('Test\TargetClass');

        $this->valueNormalizer->expects(self::any())
            ->method('normalizeValue')
            ->with('Test\TargetClass', 'entityType', self::identicalTo($requestType), false)
            ->willReturn('targets');

        $config = new EntityDefinitionConfig();
        $config->addField('association1')->setDescription('Association Description');

        $result = $this->parser->parse([
            'options' => [
                'direction' => 'input',
                'metadata'  => new ApiDocMetadata('create', $metadata, $config, $requestType)
            ]
        ]);

        self::assertEquals(
            [
                'association1' => [
                    'required'    => true,
                    'dataType'    => 'integer',
                    'description' => 'Association Description',
                    'readonly'    => true,
                    'actualType'  => null,
                    'subType'     => 'targets'
                ]
            ],
            $result
        );
    }

    public function testParseInputOnlyFieldForInputDefinition()
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata();
        $field = $metadata->addField(new FieldMetadata('field1'));
        $field->setDataType('string');
        $field->setDirection(true, false);

        $config = new EntityDefinitionConfig();
        $config->addField('field1')->setDescription('Field Description');

        $result = $this->parser->parse([
            'options' => [
                'direction' => 'input',
                'metadata'  => new ApiDocMetadata('create', $metadata, $config, $requestType)
            ]
        ]);

        self::assertEquals(
            [
                'field1' => [
                    'required'    => true,
                    'dataType'    => 'string',
                    'description' => 'Field Description'
                ]
            ],
            $result
        );
    }

    public function testParseInputOnlyFieldForOutputDefinition()
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata();
        $field = $metadata->addField(new FieldMetadata('field1'));
        $field->setDataType('string');
        $field->setDirection(true, false);

        $config = new EntityDefinitionConfig();
        $config->addField('field1')->setDescription('Field Description');

        $result = $this->parser->parse([
            'options' => [
                'direction' => 'output',
                'metadata'  => new ApiDocMetadata('create', $metadata, $config, $requestType)
            ]
        ]);

        self::assertEquals([], $result);
    }

    public function testParseOutputOnlyFieldForOutputDefinition()
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata();
        $field = $metadata->addField(new FieldMetadata('field1'));
        $field->setDataType('string');
        $field->setDirection(false, true);

        $config = new EntityDefinitionConfig();
        $config->addField('field1')->setDescription('Field Description');

        $result = $this->parser->parse([
            'options' => [
                'direction' => 'output',
                'metadata'  => new ApiDocMetadata('create', $metadata, $config, $requestType)
            ]
        ]);

        self::assertEquals(
            [
                'field1' => [
                    'required'    => true,
                    'dataType'    => 'string',
                    'description' => 'Field Description'
                ]
            ],
            $result
        );
    }

    public function testParseOutputOnlyFieldForInputDefinition()
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata();
        $field = $metadata->addField(new FieldMetadata('field1'));
        $field->setDataType('string');
        $field->setDirection(false, true);

        $config = new EntityDefinitionConfig();
        $config->addField('field1')->setDescription('Field Description');

        $result = $this->parser->parse([
            'options' => [
                'direction' => 'input',
                'metadata'  => new ApiDocMetadata('create', $metadata, $config, $requestType)
            ]
        ]);

        self::assertEquals([], $result);
    }

    public function testParseInputOnlyAssociationForInputDefinition()
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata();
        $association = $metadata->addAssociation(new AssociationMetadata('association1'));
        $association->setDataType('integer');
        $association->setTargetClassName('Test\TargetClass');
        $association->setDirection(true, false);

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('Test\TargetClass', 'entityType', self::identicalTo($requestType), false)
            ->willReturn('targets');

        $config = new EntityDefinitionConfig();
        $config->addField('association1')->setDescription('Association Description');

        $result = $this->parser->parse([
            'options' => [
                'direction' => 'input',
                'metadata'  => new ApiDocMetadata('create', $metadata, $config, $requestType)
            ]
        ]);

        self::assertEquals(
            [
                'association1' => [
                    'required'    => true,
                    'dataType'    => 'integer',
                    'description' => 'Association Description',
                    'actualType'  => null,
                    'subType'     => 'targets'
                ]
            ],
            $result
        );
    }

    public function testParseInputOnlyAssociationForOutputDefinition()
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata();
        $association = $metadata->addAssociation(new AssociationMetadata('association1'));
        $association->setDataType('integer');
        $association->setTargetClassName('Test\TargetClass');
        $association->setDirection(true, false);

        $this->valueNormalizer->expects(self::never())
            ->method('normalizeValue');

        $config = new EntityDefinitionConfig();
        $config->addField('association1')->setDescription('Association Description');

        $result = $this->parser->parse([
            'options' => [
                'direction' => 'output',
                'metadata'  => new ApiDocMetadata('create', $metadata, $config, $requestType)
            ]
        ]);

        self::assertEquals([], $result);
    }

    public function testParseOutputOnlyAssociationForOutputDefinition()
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata();
        $association = $metadata->addAssociation(new AssociationMetadata('association1'));
        $association->setDataType('integer');
        $association->setTargetClassName('Test\TargetClass');
        $association->setDirection(false, true);

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('Test\TargetClass', 'entityType', self::identicalTo($requestType), false)
            ->willReturn('targets');

        $config = new EntityDefinitionConfig();
        $config->addField('association1')->setDescription('Association Description');

        $result = $this->parser->parse([
            'options' => [
                'direction' => 'output',
                'metadata'  => new ApiDocMetadata('create', $metadata, $config, $requestType)
            ]
        ]);

        self::assertEquals(
            [
                'association1' => [
                    'required'    => true,
                    'dataType'    => 'integer',
                    'description' => 'Association Description',
                    'actualType'  => null,
                    'subType'     => 'targets'
                ]
            ],
            $result
        );
    }

    public function testParseOutputOnlyAssociationForInputDefinition()
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata();
        $association = $metadata->addAssociation(new AssociationMetadata('association1'));
        $association->setDataType('integer');
        $association->setTargetClassName('Test\TargetClass');
        $association->setDirection(false, true);

        $this->valueNormalizer->expects(self::never())
            ->method('normalizeValue');

        $config = new EntityDefinitionConfig();
        $config->addField('association1')->setDescription('Association Description');

        $result = $this->parser->parse([
            'options' => [
                'direction' => 'input',
                'metadata'  => new ApiDocMetadata('create', $metadata, $config, $requestType)
            ]
        ]);

        self::assertEquals([], $result);
    }
}

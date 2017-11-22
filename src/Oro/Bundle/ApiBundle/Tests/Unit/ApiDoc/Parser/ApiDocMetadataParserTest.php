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

class ApiDocMetadataParserTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|ValueNormalizer */
    protected $valueNormalizer;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ApiDocDataTypeConverter */
    protected $dataTypeConverter;

    /** @var ApiDocMetadataParser */
    protected $parser;

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

        $this->assertFalse($this->parser->supports($item));
    }

    public function testSupportsWithMetadata()
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

        $this->assertTrue($this->parser->supports($item));
    }

    public function testSupportsWithUnknownMetadata()
    {
        $item = [
            'class'   => null,
            'options' => [
                'metadata' => new \stdClass()
            ]
        ];

        $this->assertFalse($this->parser->supports($item));
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
                'metadata' => new ApiDocMetadata('create', $metadata, $config, $requestType)
            ]
        ]);

        $this->assertEquals(
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
                'metadata' => new ApiDocMetadata('create', $metadata, $config, $requestType)
            ]
        ]);

        $this->assertEquals(
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
                'metadata' => new ApiDocMetadata('update', $metadata, $config, $requestType)
            ]
        ]);

        $this->assertEquals(
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
                'metadata' => new ApiDocMetadata('create', $metadata, $config, $requestType)
            ]
        ]);

        $this->assertEquals(
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
                'metadata' => new ApiDocMetadata('create', $metadata, $config, $requestType)
            ]
        ]);

        $this->assertEquals(
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

        $this->valueNormalizer->expects($this->any())
            ->method('normalizeValue')
            ->with('Test\TargetClass', 'entityType', self::identicalTo($requestType), false)
            ->willReturn('targets');

        $config = new EntityDefinitionConfig();
        $config->addField('association1')->setDescription('Association Description');

        $result = $this->parser->parse([
            'options' => [
                'metadata' => new ApiDocMetadata('create', $metadata, $config, $requestType)
            ]
        ]);

        $this->assertEquals(
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

        $this->valueNormalizer->expects($this->any())
            ->method('normalizeValue')
            ->with('Test\TargetClass', 'entityType', self::identicalTo($requestType), false)
            ->willReturn('targets');

        $config = new EntityDefinitionConfig();
        $config->addField('association1')->setDescription('Association Description');

        $result = $this->parser->parse([
            'options' => [
                'metadata' => new ApiDocMetadata('create', $metadata, $config, $requestType)
            ]
        ]);

        $this->assertEquals(
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

        $this->valueNormalizer->expects($this->any())
            ->method('normalizeValue')
            ->with('Test\TargetClass', 'entityType', self::identicalTo($requestType), false)
            ->willReturn('targets');

        $config = new EntityDefinitionConfig();
        $config->addField('association1')->setDescription('Association Description');

        $result = $this->parser->parse([
            'options' => [
                'metadata' => new ApiDocMetadata('create', $metadata, $config, $requestType)
            ]
        ]);

        $this->assertEquals(
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

        $this->valueNormalizer->expects($this->any())
            ->method('normalizeValue')
            ->with('Test\TargetClass', 'entityType', self::identicalTo($requestType), false)
            ->willReturn('targets');

        $config = new EntityDefinitionConfig();
        $config->addField('association1')->setDescription('Association Description');

        $result = $this->parser->parse([
            'options' => [
                'metadata' => new ApiDocMetadata('create', $metadata, $config, $requestType)
            ]
        ]);

        $this->assertEquals(
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

        $this->valueNormalizer->expects($this->any())
            ->method('normalizeValue')
            ->with('Test\TargetClass', 'entityType', self::identicalTo($requestType), false)
            ->willReturn('targets');

        $config = new EntityDefinitionConfig();
        $config->addField('association1')->setDescription('Association Description');

        $result = $this->parser->parse([
            'options' => [
                'metadata' => new ApiDocMetadata('create', $metadata, $config, $requestType)
            ]
        ]);

        $this->assertEquals(
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
}

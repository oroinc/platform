<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\ApiDoc\Parser;

use Oro\Bundle\ApiBundle\ApiDoc\ApiDocDataTypeConverter;
use Oro\Bundle\ApiBundle\ApiDoc\Parser\ApiDocMetadata;
use Oro\Bundle\ApiBundle\ApiDoc\Parser\ApiDocMetadataParser;
use Oro\Bundle\ApiBundle\ApiDoc\RestDocViewDetector;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetaPropertyMetadata;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ApiDocMetadataParserTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ValueNormalizer */
    private $valueNormalizer;

    /** @var ApiDocMetadataParser */
    private $parser;

    protected function setUp(): void
    {
        $this->valueNormalizer = $this->createMock(ValueNormalizer::class);

        $docViewDetector = $this->createMock(RestDocViewDetector::class);
        $docViewDetector->expects(self::any())
            ->method('getView')
            ->willReturn('test_view');

        $dataTypeConverter = $this->createMock(ApiDocDataTypeConverter::class);
        $dataTypeConverter->expects(self::any())
            ->method('convertDataType')
            ->with(self::anything(), 'test_view')
            ->willReturnArgument(0);

        $this->parser = new ApiDocMetadataParser(
            $this->valueNormalizer,
            $docViewDetector,
            $dataTypeConverter
        );
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

        $metadata = new EntityMetadata('Test\Entity');
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
                    'actualType'  => 'integer',
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

        $metadata = new EntityMetadata('Test\Entity');
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
                    'actualType'  => 'integer',
                    'description' => 'Field Description'
                ]
            ],
            $result
        );
    }

    public function testParseIdentifierFieldForNotCreateAction()
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata('Test\Entity');
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
                    'actualType'  => 'integer',
                    'description' => 'Field Description'
                ]
            ],
            $result
        );
    }

    public function testParseMetaProperty()
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);

        $field = $metadata->addMetaProperty(new MetaPropertyMetadata('property1'));
        $field->setDataType('string');

        $config = new EntityDefinitionConfig();
        $config->addField('property1')->setDescription('Property Description');

        $result = $this->parser->parse([
            'options' => [
                'direction' => 'input',
                'metadata'  => new ApiDocMetadata('create', $metadata, $config, $requestType)
            ]
        ]);

        self::assertEquals(
            [
                'property1' => [
                    'required'    => false,
                    'dataType'    => 'string',
                    'actualType'  => 'string',
                    'description' => 'Property Description'
                ]
            ],
            $result
        );
    }

    public function testParseClassNameMetaProperty()
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);

        $field = $metadata->addMetaProperty(new MetaPropertyMetadata(ConfigUtil::CLASS_NAME));
        $field->setDataType('string');

        $config = new EntityDefinitionConfig();
        $config->addField(ConfigUtil::CLASS_NAME);

        $result = $this->parser->parse([
            'options' => [
                'direction' => 'input',
                'metadata'  => new ApiDocMetadata('create', $metadata, $config, $requestType)
            ]
        ]);

        self::assertSame([], $result);
    }

    public function testParseRenamedClassNameMetaProperty()
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);

        $field = $metadata->addMetaProperty(new MetaPropertyMetadata('renamedClassName'));
        $field->setDataType('string');
        $field->setPropertyPath(ConfigUtil::CLASS_NAME);

        $config = new EntityDefinitionConfig();
        $config->addField('renamedClassName')->setPropertyPath(ConfigUtil::CLASS_NAME);

        $result = $this->parser->parse([
            'options' => [
                'direction' => 'input',
                'metadata'  => new ApiDocMetadata('create', $metadata, $config, $requestType)
            ]
        ]);

        self::assertSame([], $result);
    }

    public function testParseNullableField()
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata('Test\Entity');
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
                    'actualType'  => 'string',
                    'description' => 'Field Description'
                ]
            ],
            $result
        );
    }

    public function testParseNotNullableField()
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata('Test\Entity');
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
                    'actualType'  => 'string',
                    'description' => 'Field Description'
                ]
            ],
            $result
        );
    }

    public function testParseNullableAssociation()
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata('Test\Entity');
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
                    'actualType'  => 'model',
                    'subType'     => 'targets'
                ]
            ],
            $result
        );
    }

    public function testParseNotNullableAssociation()
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata('Test\Entity');
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
                    'actualType'  => 'model',
                    'subType'     => 'targets'
                ]
            ],
            $result
        );
    }

    public function testParseCollectionAssociation()
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata('Test\Entity');
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

        $metadata = new EntityMetadata('Test\Entity');
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
                    'actualType'  => 'object',
                    'description' => 'Association Description'
                ]
            ],
            $result
        );
    }

    public function testParseAssociationIsPartOfIdentifierWithGenerator()
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata('Test\Entity');
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
                    'actualType'  => 'model',
                    'subType'     => 'targets'
                ]
            ],
            $result
        );
    }

    public function testParseInputOnlyFieldForInputDefinition()
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata('Test\Entity');
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
                    'actualType'  => 'string',
                    'description' => 'Field Description'
                ]
            ],
            $result
        );
    }

    public function testParseInputOnlyFieldForOutputDefinition()
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata('Test\Entity');
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

        $metadata = new EntityMetadata('Test\Entity');
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
                    'actualType'  => 'string',
                    'description' => 'Field Description'
                ]
            ],
            $result
        );
    }

    public function testParseOutputOnlyFieldForInputDefinition()
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata('Test\Entity');
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

        $metadata = new EntityMetadata('Test\Entity');
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
                    'actualType'  => 'model',
                    'subType'     => 'targets'
                ]
            ],
            $result
        );
    }

    public function testParseInputOnlyAssociationForOutputDefinition()
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata('Test\Entity');
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

        $metadata = new EntityMetadata('Test\Entity');
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
                    'actualType'  => 'model',
                    'subType'     => 'targets'
                ]
            ],
            $result
        );
    }

    public function testParseOutputOnlyAssociationForInputDefinition()
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata('Test\Entity');
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

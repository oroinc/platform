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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ApiDocMetadataParserTest extends TestCase
{
    private ValueNormalizer&MockObject $valueNormalizer;
    private ApiDocMetadataParser $parser;

    #[\Override]
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
            ->willReturnCallback(function (string $dataType) {
                if ('guid' === $dataType) {
                    return 'string';
                }

                return $dataType;
            });

        $this->parser = new ApiDocMetadataParser(
            $this->valueNormalizer,
            $docViewDetector,
            $dataTypeConverter
        );
    }

    public function testSupportsWithoutMetadata(): void
    {
        $item = [
            'class'   => null,
            'options' => []
        ];

        self::assertFalse($this->parser->supports($item));
    }

    public function testSupportsWithDirectionAndMetadata(): void
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

    public function testSupportsWithMetadataButWithoutDirection(): void
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

    public function testSupportsWithUnknownMetadata(): void
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

    public function testParseIdentifierFieldWithGeneratorForCreateAction(): void
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);
        $metadata->setHasIdentifierGenerator(true);
        $metadata->addField(new FieldMetadata('id'))->setDataType('guid');

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
                    'dataType'    => 'string',
                    'actualType'  => 'guid',
                    'description' => 'Field Description',
                    'readonly'    => true
                ]
            ],
            $result
        );
    }

    public function testParseIdentifierFieldWithoutGeneratorForCreateAction(): void
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);
        $metadata->addField(new FieldMetadata('id'))->setDataType('guid');

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
                    'dataType'    => 'string',
                    'actualType'  => 'guid',
                    'description' => 'Field Description'
                ]
            ],
            $result
        );
    }

    public function testParseIdentifierFieldForNotCreateAction(): void
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);
        $metadata->setHasIdentifierGenerator(true);
        $metadata->addField(new FieldMetadata('id'))->setDataType('guid');

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
                    'dataType'    => 'string',
                    'actualType'  => 'guid',
                    'description' => 'Field Description'
                ]
            ],
            $result
        );
    }

    public function testParseMetaProperty(): void
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);

        $metadata->addMetaProperty(new MetaPropertyMetadata('property1', 'guid'));

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
                    'actualType'  => 'guid',
                    'description' => 'Property Description'
                ]
            ],
            $result
        );
    }

    public function testParseClassNameMetaProperty(): void
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);

        $metadata->addMetaProperty(new MetaPropertyMetadata(ConfigUtil::CLASS_NAME, 'guid'));

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

    public function testParseRenamedClassNameMetaProperty(): void
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);

        $metadata->addMetaProperty(new MetaPropertyMetadata('renamedClassName', 'guid', ConfigUtil::CLASS_NAME));

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

    public function testParseNullableField(): void
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);

        $field = $metadata->addField(new FieldMetadata('field1'));
        $field->setDataType('guid');
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
                    'actualType'  => 'guid',
                    'description' => 'Field Description'
                ]
            ],
            $result
        );
    }

    public function testParseNotNullableField(): void
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);

        $field = $metadata->addField(new FieldMetadata('field1'));
        $field->setDataType('guid');

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
                    'actualType'  => 'guid',
                    'description' => 'Field Description'
                ]
            ],
            $result
        );
    }

    public function testParseNullableAssociation(): void
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);

        $association = $metadata->addAssociation(new AssociationMetadata('association1'));
        $association->setDataType('guid');
        $association->setTargetClassName('Test\TargetClass');
        $association->setIsNullable(true);

        $this->valueNormalizer->expects(self::any())
            ->method('normalizeValue')
            ->with('Test\TargetClass', 'entityType', self::identicalTo($requestType))
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
                    'dataType'    => 'string',
                    'description' => 'Association Description',
                    'actualType'  => 'model',
                    'subType'     => 'targets'
                ]
            ],
            $result
        );
    }

    public function testParseNotNullableAssociation(): void
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);

        $association = $metadata->addAssociation(new AssociationMetadata('association1'));
        $association->setDataType('guid');
        $association->setTargetClassName('Test\TargetClass');

        $this->valueNormalizer->expects(self::any())
            ->method('normalizeValue')
            ->with('Test\TargetClass', 'entityType', self::identicalTo($requestType))
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
                    'dataType'    => 'string',
                    'description' => 'Association Description',
                    'actualType'  => 'model',
                    'subType'     => 'targets'
                ]
            ],
            $result
        );
    }

    public function testParseCollectionAssociation(): void
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);

        $association = $metadata->addAssociation(new AssociationMetadata('association1'));
        $association->setDataType('guid');
        $association->setTargetClassName('Test\TargetClass');
        $association->setIsCollection(true);

        $this->valueNormalizer->expects(self::any())
            ->method('normalizeValue')
            ->with('Test\TargetClass', 'entityType', self::identicalTo($requestType))
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
                    'dataType'    => 'string',
                    'description' => 'Association Description',
                    'actualType'  => 'collection',
                    'subType'     => 'targets'
                ]
            ],
            $result
        );
    }

    public function testParseAssociationAsField(): void
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);

        $association = $metadata->addAssociation(new AssociationMetadata('association1'));
        $association->setDataType('object');
        $association->setTargetClassName('Test\TargetClass');

        $this->valueNormalizer->expects(self::any())
            ->method('normalizeValue')
            ->with('Test\TargetClass', 'entityType', self::identicalTo($requestType))
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

    public function testParseAssociationIsPartOfIdentifierWithGenerator(): void
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id', 'association1']);
        $metadata->setHasIdentifierGenerator(true);

        $association = $metadata->addAssociation(new AssociationMetadata('association1'));
        $association->setDataType('guid');
        $association->setTargetClassName('Test\TargetClass');

        $this->valueNormalizer->expects(self::any())
            ->method('normalizeValue')
            ->with('Test\TargetClass', 'entityType', self::identicalTo($requestType))
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
                    'dataType'    => 'string',
                    'description' => 'Association Description',
                    'readonly'    => true,
                    'actualType'  => 'model',
                    'subType'     => 'targets'
                ]
            ],
            $result
        );
    }

    public function testParseInputOnlyFieldForInputDefinition(): void
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata('Test\Entity');
        $field = $metadata->addField(new FieldMetadata('field1'));
        $field->setDataType('guid');
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
                    'actualType'  => 'guid',
                    'description' => 'Field Description'
                ]
            ],
            $result
        );
    }

    public function testParseInputOnlyFieldForOutputDefinition(): void
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata('Test\Entity');
        $field = $metadata->addField(new FieldMetadata('field1'));
        $field->setDataType('guid');
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

    public function testParseOutputOnlyFieldForOutputDefinition(): void
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata('Test\Entity');
        $field = $metadata->addField(new FieldMetadata('field1'));
        $field->setDataType('guid');
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
                    'actualType'  => 'guid',
                    'description' => 'Field Description'
                ]
            ],
            $result
        );
    }

    public function testParseOutputOnlyFieldForInputDefinition(): void
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata('Test\Entity');
        $field = $metadata->addField(new FieldMetadata('field1'));
        $field->setDataType('guid');
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

    public function testParseInputOnlyAssociationForInputDefinition(): void
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata('Test\Entity');
        $association = $metadata->addAssociation(new AssociationMetadata('association1'));
        $association->setDataType('guid');
        $association->setTargetClassName('Test\TargetClass');
        $association->setDirection(true, false);

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('Test\TargetClass', 'entityType', self::identicalTo($requestType))
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
                    'dataType'    => 'string',
                    'description' => 'Association Description',
                    'actualType'  => 'model',
                    'subType'     => 'targets'
                ]
            ],
            $result
        );
    }

    public function testParseInputOnlyAssociationForOutputDefinition(): void
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata('Test\Entity');
        $association = $metadata->addAssociation(new AssociationMetadata('association1'));
        $association->setDataType('guid');
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

    public function testParseOutputOnlyAssociationForOutputDefinition(): void
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata('Test\Entity');
        $association = $metadata->addAssociation(new AssociationMetadata('association1'));
        $association->setDataType('guid');
        $association->setTargetClassName('Test\TargetClass');
        $association->setDirection(false, true);

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('Test\TargetClass', 'entityType', self::identicalTo($requestType))
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
                    'dataType'    => 'string',
                    'description' => 'Association Description',
                    'actualType'  => 'model',
                    'subType'     => 'targets'
                ]
            ],
            $result
        );
    }

    public function testParseOutputOnlyAssociationForInputDefinition(): void
    {
        $requestType = new RequestType([]);

        $metadata = new EntityMetadata('Test\Entity');
        $association = $metadata->addAssociation(new AssociationMetadata('association1'));
        $association->setDataType('guid');
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

<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\ApiDoc\Parser;

use Oro\Bundle\ApiBundle\ApiDoc\Parser\ApiDocMetadata;
use Oro\Bundle\ApiBundle\ApiDoc\Parser\ApiDocMetadataParser;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Request\RequestType;

class ApiDocMetadataParserTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $valueNormalizer;

    /** @var ApiDocMetadataParser */
    protected $parser;

    protected function setUp()
    {
        $this->valueNormalizer = $this->getMockBuilder('Oro\Bundle\ApiBundle\Request\ValueNormalizer')
            ->disableOriginalConstructor()
            ->getMock();
        $this->parser = new ApiDocMetadataParser($this->valueNormalizer);
    }

    /**
     * @dataProvider  supportsDataProvider
     *
     * @param array $inputData
     * @param bool  $result
     */
    public function testSupports($inputData, $result)
    {
        $this->assertEquals($result, $this->parser->supports($inputData));
    }

    public function supportsDataProvider()
    {
        $metadata = new EntityMetadata();
        $config = $this->getMockBuilder('Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig')
            ->disableOriginalConstructor()
            ->getMock();
        $requestType = new RequestType([]);
        $apiMetadata = new ApiDocMetadata('test', $metadata, $config, $requestType);
        return [
            [[], false],
            [['class' => 'testClass', 'options' => ['metadata' => $apiMetadata]], false],
            [['class' => ApiDocMetadata::class], false],
            [['class' => ApiDocMetadata::class, 'options' => []], false],
            [['class' => ApiDocMetadata::class, 'options' => ['metadata' => new \stdClass()]], false],
            [['class' => ApiDocMetadata::class, 'options' => ['metadata' => $apiMetadata]], true],
        ];
    }

    public function testParse()
    {
        $metadata = new EntityMetadata();
        $metadata->setIdentifierFieldNames(['id']);

        $metadata->addField(new FieldMetadata('id'))->setDataType('integer');
        $metadata->addField(new FieldMetadata('firstName'))->setDataType('string');
        $metadata->addField(new FieldMetadata('lastName'))->setDataType('string');

        $contactsAssociation = new AssociationMetadata('contacts');
        $contactsAssociation->setTargetClassName('ContactClass');
        $contactsAssociation->setIsCollection(true);
        $metadata->addAssociation($contactsAssociation);

        $accountAssociation = new AssociationMetadata('defaultAccount');
        $accountAssociation->setTargetClassName('AccountClass');
        $accountAssociation->setIsCollection(false);
        $metadata->addAssociation($accountAssociation);

        $requestType = new RequestType([]);

        $this->valueNormalizer->expects($this->any())
            ->method('normalizeValue')
            ->willReturnMap([
                ['ContactClass', 'entityType', $requestType, false, 'contacts'],
                ['AccountClass', 'entityType', $requestType, false, 'accounts'],
            ]);

        $config = new EntityDefinitionConfig();
        $config->addField('id')->setDescription('Id Field');
        $config->addField('firstName')->setDescription('firstName Field');
        $config->addField('lastName')->setDescription('lastName Field');
        $config->addField('contacts')->setDescription('contacts Field');
        $config->addField('defaultAccount')->setDescription('defaultAccount Field');

        $result = $this->parser->parse(
            ['options' => ['metadata' => new ApiDocMetadata('create', $metadata, $config, $requestType)]]
        );

        $expectedResult = [
            'id' => [
                'required' => true,
                'dataType' => 'integer',
                'description' => 'Id Field',
                'readonly' => true,
                'isRelation' => false,
                'isCollection' => false
            ],
            'firstName' => [
                'required' => true,
                'dataType' => 'string',
                'description' => 'firstName Field',
                'readonly' => false,
                'isRelation' => false,
                'isCollection' => false
            ],
            'lastName' => [
                'required' => true,
                'dataType' => 'string',
                'description' => 'lastName Field',
                'readonly' => false,
                'isRelation' => false,
                'isCollection' => false
            ],
            'contacts' => [
                'required' => true,
                'dataType' => 'array of contacts',
                'description' => 'contacts Field',
                'readonly' => false,
                'isRelation' => true,
                'isCollection' => true
            ],
            'defaultAccount' => [
                'required' => true,
                'dataType' => 'accounts',
                'description' => 'defaultAccount Field',
                'readonly' => false,
                'isRelation' => true,
                'isCollection' => false
            ],
        ];

        $this->assertEquals($result, $expectedResult);
    }
}

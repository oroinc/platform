<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetMetadata;

use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\NormalizeMetadata;

class NormalizeMetadataTest extends MetadataProcessorTestCase
{
    /** @var NormalizeMetadata */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new NormalizeMetadata();
    }

    /**
     * @param string $fieldName
     *
     * @return FieldMetadata
     */
    protected function createFieldMetadata($fieldName)
    {
        $fieldMetadata = new FieldMetadata();
        $fieldMetadata->setName($fieldName);

        return $fieldMetadata;
    }

    /**
     * @param string $associationName
     * @param string $targetClass
     *
     * @return AssociationMetadata
     */
    protected function createAssociationMetadata($associationName, $targetClass)
    {
        $associationMetadata = new AssociationMetadata();
        $associationMetadata->setName($associationName);
        $associationMetadata->setTargetClassName($targetClass);

        return $associationMetadata;
    }

    public function testProcessWithoutMetadata()
    {
        $this->processor->process($this->context);
    }

    public function testProcessWithoutConfig()
    {
        $this->context->setResult(new EntityMetadata());
        $this->processor->process($this->context);
    }

    public function testProcess()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1'       => null,
                'field2'       => [
                    'exclude' => true
                ],
                'field3'       => [
                    'property_path' => 'realField3'
                ],
                'association1' => [
                    'exclude' => true
                ],
                'association2' => [
                    'property_path' => 'realAssociation2'
                ],
                'association3' => [
                    'property_path' => 'realAssociation3'
                ],
            ]
        ];

        $metadata = new EntityMetadata();
        $metadata->addField($this->createFieldMetadata('field1'));
        $metadata->addField($this->createFieldMetadata('field2'));
        $metadata->addField($this->createFieldMetadata('field3'));
        $metadata->addField($this->createFieldMetadata('field4'));
        $metadata->addAssociation(
            $this->createAssociationMetadata('association1', 'Test\Association1Target')
        );
        $metadata->addAssociation(
            $this->createAssociationMetadata('association2', 'Test\Association2Target')
        );
        $metadata->addAssociation(
            $this->createAssociationMetadata('realAssociation3', 'Test\Association3Target')
        );
        $metadata->addAssociation(
            $this->createAssociationMetadata('association4', 'Test\Association4Target')
        );

        $this->context->setConfig($this->createConfigObject($config));
        $this->context->setResult($metadata);
        $this->processor->process($this->context);

        $expectedMetadata = new EntityMetadata();
        $expectedMetadata->addField($this->createFieldMetadata('field1'));
        $expectedMetadata->addField($this->createFieldMetadata('field3'));
        $expectedMetadata->addAssociation(
            $this->createAssociationMetadata('association2', 'Test\Association2Target')
        );
        $expectedMetadata->addAssociation(
            $this->createAssociationMetadata('association3', 'Test\Association3Target')
        );

        $this->assertEquals($expectedMetadata, $this->context->getResult());
    }
}

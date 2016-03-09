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
        $field1   = new FieldMetadata();
        $field1->setName('field1');
        $metadata->addField($field1);
        $field2 = new FieldMetadata();
        $field2->setName('field2');
        $metadata->addField($field2);
        $field3 = new FieldMetadata();
        $field3->setName('field3');
        $metadata->addField($field3);
        $field4 = new FieldMetadata();
        $field4->setName('field4');
        $metadata->addField($field4);
        $association1 = new AssociationMetadata();
        $association1->setTargetClassName('Test\Association1Target');
        $association1->setName('association1');
        $metadata->addAssociation($association1);
        $association2 = new AssociationMetadata();
        $association2->setTargetClassName('Test\Association2Target');
        $association2->setName('association2');
        $metadata->addAssociation($association2);
        $association3 = new AssociationMetadata();
        $association3->setTargetClassName('Test\Association3Target');
        $association3->setName('realAssociation3');
        $metadata->addAssociation($association3);
        $association4 = new AssociationMetadata();
        $association4->setTargetClassName('Test\Association4Target');
        $association4->setName('association4');
        $metadata->addAssociation($association4);

        $this->context->setConfig($this->createConfigObject($config));
        $this->context->setResult($metadata);
        $this->processor->process($this->context);

        $expectedMetadata = new EntityMetadata();
        $expectedField1   = new FieldMetadata();
        $expectedField1->setName('field1');
        $expectedMetadata->addField($expectedField1);
        $expectedField3 = new FieldMetadata();
        $expectedField3->setName('field3');
        $expectedMetadata->addField($expectedField3);
        $expectedAssociation2 = new AssociationMetadata();
        $expectedAssociation2->setTargetClassName('Test\Association2Target');
        $expectedAssociation2->setName('association2');
        $expectedMetadata->addAssociation($expectedAssociation2);
        $expectedAssociation3 = new AssociationMetadata();
        $expectedAssociation3->setTargetClassName('Test\Association3Target');
        $expectedAssociation3->setName('association3');
        $expectedMetadata->addAssociation($expectedAssociation3);

        $this->assertEquals($expectedMetadata, $this->context->getResult());
    }
}

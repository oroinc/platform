<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Metadata;

use Oro\Bundle\EntityMergeBundle\Metadata\DoctrineMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;

class EntityMetadataTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider constructorProvider
     */
    public function testConstruct($options, $fieldMetadata, $expectedExceptionMessage)
    {
        $this->setExpectedException(
            '\Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException',
            $expectedExceptionMessage
        );
        $metadata = new EntityMetadata($options, $fieldMetadata);
    }

    public function constructorProvider()
    {
        return [
            'both_invalid' => [
                'options' => null,
                'fieldMetadata' => null,
                'expectedExceptionMessage' => 'Options argument should have array type',
            ],
            'first_invalid' => [
                'options' => true,
                'fieldMetadata' => [],
                'expectedExceptionMessage' => 'Options argument should have array type',
            ],
            'second_invalid' => [
                'options' => [],
                'fieldMetadata' => 'string',
                'expectedExceptionMessage' => 'FieldMetadata argument should have array type',
            ],
        ];
    }

    public function testConstructEmptyArguments()
    {
        $metadata = new EntityMetadata();
    }

    public function testConstructBothValid()
    {
        $metadata = new EntityMetadata(['code' => 'value'], ['metadata']);
    }

    public function testGetFieldMetadata()
    {
        $fieldMetadata = ['metadata'];
        $metadata = new EntityMetadata(['code' => 'value'], $fieldMetadata);

        $this->assertEquals($fieldMetadata, $metadata->getFieldMetadata());
    }

    public function testGetClassName()
    {
        $metadata = new EntityMetadata();
        $this->assertNull($metadata->getClassName());
    }

    public function testDoctrineMetadataExistsWithNotSetField()
    {
        $metadata = new EntityMetadata();
        $doctrineMetadata = new DoctrineMetadata();
        $metadata->set(DoctrineMetadata::OPTION_NAME, $doctrineMetadata);

        $this->assertNull($metadata->getClassName());
    }

    public function testDoctrineMetadataExists()
    {
        $metadata = new EntityMetadata();
        $doctrineMetadata = new DoctrineMetadata();

        $className = 'className';
        $doctrineMetadata->set('name', $className);
        $metadata->set(DoctrineMetadata::OPTION_NAME, $doctrineMetadata);

        $this->assertEquals($className, $metadata->getClassName());
    }
}

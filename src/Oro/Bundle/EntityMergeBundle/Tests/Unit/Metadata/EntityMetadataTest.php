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
            '\Exception',
            $expectedExceptionMessage
        );
        $metadata = new EntityMetadata($options, $fieldMetadata);
    }

    public function constructorProvider()
    {
        return [
            'both_invalid'   => [
                'options'                  => null,
                'fieldMetadata'            => null,
                'expectedExceptionMessage' => 'must be of the type array, null given',
            ],
            'first_invalid'  => [
                'options'                  => true,
                'fieldMetadata'            => [],
                'expectedExceptionMessage' => 'must be of the type array, boolean given',
            ],
            'second_invalid' => [
                'options'                  => [],
                'fieldMetadata'            => 'string',
                'expectedExceptionMessage' => 'must be of the type array, string given',
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

    public function testGetFieldsMetadata()
    {
        $fieldMetadata = ['metadata'];
        $metadata      = new EntityMetadata(['code' => 'value'], $fieldMetadata);

        $this->assertEquals($fieldMetadata, $metadata->getFieldsMetadata());
    }

    /**
     * @expectedException \Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage DoctrineMetadata not set
     */
    public function testGetClassName()
    {
        $metadata = new EntityMetadata();
        $this->assertNull($metadata->getClassName());
    }

    /**
     * @expectedException \Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Class name not set
     */
    public function testDoctrineMetadataExistsWithNotSetField()
    {
        $metadata         = new EntityMetadata();
        $doctrineMetadata = new DoctrineMetadata();
        $metadata->setDoctrineMetadata($doctrineMetadata);

        $this->assertNull($metadata->getClassName());
    }

    public function testDoctrineMetadataExists()
    {
        $metadata         = new EntityMetadata();
        $doctrineMetadata = new DoctrineMetadata();

        $className = 'className';
        $doctrineMetadata->set('name', $className);
        $metadata->setDoctrineMetadata($doctrineMetadata);

        $this->assertEquals($className, $metadata->getClassName());
    }
}

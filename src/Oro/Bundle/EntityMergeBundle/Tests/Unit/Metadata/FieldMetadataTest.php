<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Metadata;

use Oro\Bundle\EntityMergeBundle\Metadata\DoctrineMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;

class FieldMetadataTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage DoctrineMetadata not set
     */
    public function testDoctrineOptionNotExists()
    {
        $metadata = new FieldMetadata();
        $this->assertNull($metadata->getFieldName());
    }

    /**
     * @expectedException \Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Field name not set
     */
    public function testDoctrineMetadataExistsWithNotSetField()
    {
        $metadata         = new FieldMetadata();
        $doctrineMetadata = new DoctrineMetadata();
        $metadata->setDoctrineMetadata($doctrineMetadata);

        $this->assertNull($metadata->getFieldName());
    }

    public function testDoctrineMetadataExists()
    {
        $metadata         = new FieldMetadata();
        $doctrineMetadata = new DoctrineMetadata();

        $fieldName = 'new_field';
        $doctrineMetadata->set('fieldName', $fieldName);
        $metadata->setDoctrineMetadata($doctrineMetadata);

        $this->assertEquals($fieldName, $metadata->getFieldName());
    }
}

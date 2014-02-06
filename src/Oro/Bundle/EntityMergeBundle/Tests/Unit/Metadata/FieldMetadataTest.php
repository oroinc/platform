<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Metadata;

use Oro\Bundle\EntityMergeBundle\Metadata\DoctrineMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;

class FieldMetadataTest extends \PHPUnit_Framework_TestCase
{
    public function testDoctrineOptionNotExists()
    {
        $metadata = new FieldMetadata();
        $this->assertNull($metadata->getFieldName());
    }

    public function testDoctrineMetadataExistsWithNotSetField()
    {
        $metadata = new FieldMetadata();
        $doctrineMetadata = new DoctrineMetadata();
        $metadata->set(DoctrineMetadata::OPTION_NAME, $doctrineMetadata);

        $this->assertNull($metadata->getFieldName());
    }

    public function testDoctrineMetadataExists()
    {
        $metadata = new FieldMetadata();
        $doctrineMetadata = new DoctrineMetadata();

        $fieldName = 'new_field';
        $doctrineMetadata->set('fieldName', $fieldName);
        $metadata->set(DoctrineMetadata::OPTION_NAME, $doctrineMetadata);

        $this->assertEquals($fieldName, $metadata->getFieldName());
    }
}

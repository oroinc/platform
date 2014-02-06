<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Metadata;

use Oro\Bundle\EntityMergeBundle\Metadata\DoctrineMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\CollectionMetadata;

class CollectionMetadataTest extends \PHPUnit_Framework_TestCase
{
    public function testDoctrineOptionNotExists()
    {
        $metadata = new CollectionMetadata();
        $this->assertNull($metadata->getFieldName());
    }

    public function testDoctrineMetadataExistsWithNotSetField()
    {
        $metadata = new CollectionMetadata();
        $doctrineMetadata = new DoctrineMetadata();
        $metadata->set(DoctrineMetadata::OPTION_NAME, $doctrineMetadata);

        $this->assertNull($metadata->getFieldName());
    }

    public function testDoctrineMetadataExists()
    {
        $metadata = new CollectionMetadata();
        $doctrineMetadata = new DoctrineMetadata();

        $fieldName = 'new_field';
        $doctrineMetadata->set('fieldName', $fieldName);
        $metadata->set(DoctrineMetadata::OPTION_NAME, $doctrineMetadata);

        $this->assertEquals($fieldName, $metadata->getFieldName());
    }
}

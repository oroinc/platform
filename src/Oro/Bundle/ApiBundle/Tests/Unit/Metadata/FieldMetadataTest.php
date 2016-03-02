<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Metadata;

use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;

class FieldMetadataTest extends \PHPUnit_Framework_TestCase
{
    public function testGettersSetters()
    {
        $fieldMetadata = new FieldMetadata();

        $this->assertNull($fieldMetadata->getName());
        $this->assertNull($fieldMetadata->getDataType());

        $fieldMetadata->setName('fieldName');
        $fieldMetadata->setDataType('fieldType');

        $this->assertSame('fieldName', $fieldMetadata->getName());
        $this->assertSame('fieldType', $fieldMetadata->getDataType());
    }
}

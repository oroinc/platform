<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\ORM;

use Oro\Bundle\EntityBundle\ORM\ShortClassMetadata;

class ShortClassMetadataTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructor()
    {
        $metadata = new ShortClassMetadata('Test\Entity');
        $this->assertEquals('Test\Entity', $metadata->name);
        $this->assertFalse($metadata->isMappedSuperclass);
        $this->assertFalse($metadata->hasAssociations);

        $metadata = new ShortClassMetadata('Test\Entity', true);
        $this->assertEquals('Test\Entity', $metadata->name);
        $this->assertTrue($metadata->isMappedSuperclass);
        $this->assertFalse($metadata->hasAssociations);

        $metadata = new ShortClassMetadata('Test\Entity', true, true);
        $this->assertEquals('Test\Entity', $metadata->name);
        $this->assertTrue($metadata->isMappedSuperclass);
        $this->assertTrue($metadata->hasAssociations);
    }

    public function testSerialize()
    {
        $metadata = new ShortClassMetadata('Test\Entity');
        $this->assertEquals($metadata, unserialize(serialize($metadata)));

        $metadata = new ShortClassMetadata('Test\Entity', true, false);
        $this->assertEquals($metadata, unserialize(serialize($metadata)));

        $metadata = new ShortClassMetadata('Test\Entity', true, true);
        $this->assertEquals($metadata, unserialize(serialize($metadata)));
    }

    public function testSetState()
    {
        $metadata = ShortClassMetadata::__set_state([
            'name'               => 'Test\Entity',
            'isMappedSuperclass' => true,
            'hasAssociations'    => true
        ]);
        $this->assertEquals('Test\Entity', $metadata->name);
        $this->assertTrue($metadata->isMappedSuperclass);
    }
}

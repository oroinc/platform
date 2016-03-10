<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\ORM;

use Oro\Bundle\EntityBundle\ORM\ShortClassMetadata;

class ShortClassMetadataTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $metadata = new ShortClassMetadata('Test\Entity');
        $this->assertEquals('Test\Entity', $metadata->name);
        $this->assertFalse($metadata->isMappedSuperclass);

        $metadata = new ShortClassMetadata('Test\Entity', true);
        $this->assertEquals('Test\Entity', $metadata->name);
        $this->assertTrue($metadata->isMappedSuperclass);
    }

    public function testSerialize()
    {
        $metadata = new ShortClassMetadata('Test\Entity', true);
        $unserialized = unserialize(serialize($metadata));
        $this->assertEquals($metadata, $unserialized);
    }

    public function testSetState()
    {
        $metadata = ShortClassMetadata::__set_state(['name' => 'Test\Entity', 'isMappedSuperclass' => true]);
        $this->assertEquals('Test\Entity', $metadata->name);
        $this->assertTrue($metadata->isMappedSuperclass);
    }
}

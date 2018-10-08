<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\ORM;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\ORM\DiscriminatorMapListener;

class DiscriminatorMapListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DiscriminatorMapListener */
    protected $listener;

    protected function setUp()
    {
        $this->listener = new DiscriminatorMapListener();
    }

    public function testEmptyClasses()
    {
        /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject $em */
        $em = $this->createMock('Doctrine\ORM\EntityManagerInterface');

        $metadata = new ClassMetadata('\stdClass');
        $event = new LoadClassMetadataEventArgs($metadata, $em);

        $this->listener->loadClassMetadata($event);

        $this->assertEmpty($metadata->discriminatorMap);
    }

    public function testNotSingleTable()
    {
        /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject $em */
        $em = $this->createMock('Doctrine\ORM\EntityManagerInterface');

        $metadata = new ClassMetadata('\stdClass');
        $event = new LoadClassMetadataEventArgs($metadata, $em);

        $this->listener->addClass('key', '\stdClass');
        $this->listener->loadClassMetadata($event);

        $this->assertEmpty($metadata->discriminatorMap);
    }

    public function testSingleTableNotRoot()
    {
        /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject $em */
        $em = $this->createMock('Doctrine\ORM\EntityManagerInterface');

        $metadata = new ClassMetadata('\stdClass');
        $metadata->inheritanceType = ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE;
        $metadata->rootEntityName = '\stdClass2';
        $event = new LoadClassMetadataEventArgs($metadata, $em);

        $this->listener->addClass('key', '\stdClass');
        $this->listener->loadClassMetadata($event);

        $this->assertEmpty($metadata->discriminatorMap);
    }

    public function testSingleTableRoot()
    {
        /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject $em */
        $em = $this->createMock('Doctrine\ORM\EntityManagerInterface');

        $metadata = new ClassMetadata('\stdClass');
        $metadata->inheritanceType = ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE;
        $event = new LoadClassMetadataEventArgs($metadata, $em);

        $this->listener->addClass('key', '\stdClass');
        $this->listener->loadClassMetadata($event);

        $this->assertEquals(['key' => 'stdClass'], $metadata->discriminatorMap);
    }

    public function testMapOverride()
    {
        /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject $em */
        $em = $this->createMock('Doctrine\ORM\EntityManagerInterface');

        $metadata = new ClassMetadata('\stdClass');
        $metadata->inheritanceType = ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE;
        $metadata->discriminatorMap = [
            'key' => 'class',
            'other' => 'second',
        ];
        $event = new LoadClassMetadataEventArgs($metadata, $em);

        $this->listener->addClass('key', '\stdClass');
        $this->listener->loadClassMetadata($event);

        $this->assertEquals(['key' => 'stdClass', 'other' => 'second'], $metadata->discriminatorMap);
    }
}

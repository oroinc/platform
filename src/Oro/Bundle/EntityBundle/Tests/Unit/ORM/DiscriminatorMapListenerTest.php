<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\ORM;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\ORM\DiscriminatorMapListener;

class DiscriminatorMapListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DiscriminatorMapListener */
    private $listener;

    protected function setUp(): void
    {
        $this->listener = new DiscriminatorMapListener();
    }

    public function testEmptyClasses()
    {
        $em = $this->createMock(EntityManagerInterface::class);

        $metadata = new ClassMetadata(\stdClass::class);
        $event = new LoadClassMetadataEventArgs($metadata, $em);

        $this->listener->loadClassMetadata($event);

        $this->assertEmpty($metadata->discriminatorMap);
    }

    public function testNotSingleTable()
    {
        $em = $this->createMock(EntityManagerInterface::class);

        $metadata = new ClassMetadata(\stdClass::class);
        $event = new LoadClassMetadataEventArgs($metadata, $em);

        $this->listener->addClass('key', \stdClass::class);
        $this->listener->loadClassMetadata($event);

        $this->assertEmpty($metadata->discriminatorMap);
    }

    public function testSingleTableNotRoot()
    {
        $em = $this->createMock(EntityManagerInterface::class);

        $metadata = new ClassMetadata(\stdClass::class);
        $metadata->inheritanceType = ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE;
        $metadata->rootEntityName = '\stdClass2';
        $event = new LoadClassMetadataEventArgs($metadata, $em);

        $this->listener->addClass('key', \stdClass::class);
        $this->listener->loadClassMetadata($event);

        $this->assertEmpty($metadata->discriminatorMap);
    }

    public function testSingleTableRoot()
    {
        $em = $this->createMock(EntityManagerInterface::class);

        $metadata = new ClassMetadata(\stdClass::class);
        $metadata->inheritanceType = ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE;
        $event = new LoadClassMetadataEventArgs($metadata, $em);

        $this->listener->addClass('key', \stdClass::class);
        $this->listener->loadClassMetadata($event);

        $this->assertEquals(['key' => 'stdClass'], $metadata->discriminatorMap);
    }

    public function testMapOverride()
    {
        $em = $this->createMock(EntityManagerInterface::class);

        $metadata = new ClassMetadata(\stdClass::class);
        $metadata->inheritanceType = ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE;
        $metadata->discriminatorMap = [
            'key' => 'class',
            'other' => 'second',
        ];
        $event = new LoadClassMetadataEventArgs($metadata, $em);

        $this->listener->addClass('key', \stdClass::class);
        $this->listener->loadClassMetadata($event);

        $this->assertEquals(['key' => 'stdClass', 'other' => 'second'], $metadata->discriminatorMap);
    }
}

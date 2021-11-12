<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Metadata;

use Oro\Bundle\EntityMergeBundle\Metadata\DoctrineMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\MetadataFactory;

class MetadataFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MetadataFactory
     */
    protected $factory;

    protected function setUp(): void
    {
        $this->factory = new MetadataFactory();
    }

    public function testCreateEntityMetadata()
    {
        $options = array('foo' => 'bar');
        $doctrineMetadata = $this->createMock(DoctrineMetadata::class);

        $metadata = $this->factory->createEntityMetadata($options, $doctrineMetadata);
        $this->assertInstanceOf(EntityMetadata::class, $metadata);
        $this->assertEquals($options, $metadata->all());
        $this->assertEquals($doctrineMetadata, $metadata->getDoctrineMetadata());
    }

    public function testCreateEntityMetadataFromArray()
    {
        $options = array('foo' => 'bar');
        $doctrineMetadata = array('doctrineOption' => 'test');

        $metadata = $this->factory->createEntityMetadata($options, $doctrineMetadata);
        $this->assertInstanceOf(EntityMetadata::class, $metadata);
        $this->assertInstanceOf(
            DoctrineMetadata::class,
            $metadata->getDoctrineMetadata()
        );
    }

    //@codingStandardsIgnoreStart
    //@codingStandardsIgnoreEnd
    public function testCreateEntityMetadataFails()
    {
        $this->expectException(\Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf(
            '$doctrineMetadata must be an array of "%s", but "stdClass" given.',
            \Oro\Bundle\EntityMergeBundle\Metadata\DoctrineMetadata::class
        ));

        $options = array('foo' => 'bar');
        $doctrineMetadata = new \stdClass();

        $this->factory->createEntityMetadata($options, $doctrineMetadata);
    }

    public function testCreateFieldMetadata()
    {
        $options = array('foo' => 'bar');
        $doctrineMetadata = $this->createMock(DoctrineMetadata::class);

        $metadata = $this->factory->createFieldMetadata($options, $doctrineMetadata);
        $this->assertInstanceOf(FieldMetadata::class, $metadata);
        $this->assertEquals($options, $metadata->all());
        $this->assertEquals($doctrineMetadata, $metadata->getDoctrineMetadata());
    }

    public function testCreateFieldMetadataFromArray()
    {
        $options = array('foo' => 'bar');
        $doctrineMetadata = array('doctrineOption' => 'test');

        $metadata = $this->factory->createFieldMetadata($options, $doctrineMetadata);
        $this->assertInstanceOf(FieldMetadata::class, $metadata);
        $this->assertInstanceOf(
            DoctrineMetadata::class,
            $metadata->getDoctrineMetadata()
        );
    }

    //@codingStandardsIgnoreStart
    //@codingStandardsIgnoreEnd
    public function testCreateFieldMetadataFails()
    {
        $this->expectException(\Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf(
            '$doctrineMetadata must be an array of "%s", but "stdClass" given.',
            \Oro\Bundle\EntityMergeBundle\Metadata\DoctrineMetadata::class
        ));

        $options = array('foo' => 'bar');
        $doctrineMetadata = new \stdClass();

        $this->factory->createFieldMetadata($options, $doctrineMetadata);
    }

    public function testCreateDoctrineMetadata()
    {
        $options = array('foo' => 'bar');
        $doctrineMetadata = $this->createMock(DoctrineMetadata::class);

        $metadata = $this->factory->createFieldMetadata($options, $doctrineMetadata);
        $this->assertInstanceOf(FieldMetadata::class, $metadata);
        $this->assertEquals($options, $metadata->all());
        $this->assertEquals($doctrineMetadata, $metadata->getDoctrineMetadata());
    }
}

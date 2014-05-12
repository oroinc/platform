<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Metadata;

use Oro\Bundle\EntityMergeBundle\Metadata\MetadataFactory;

class MetadataFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MetadataFactory
     */
    protected $factory;

    protected function setUp()
    {
        $this->factory = new MetadataFactory();
    }

    public function testCreateEntityMetadata()
    {
        $options = array('foo' => 'bar');
        $doctrineMetadata = $this->getMock('Oro\\Bundle\\EntityMergeBundle\\Metadata\\DoctrineMetadata');

        $metadata = $this->factory->createEntityMetadata($options, $doctrineMetadata);
        $this->assertInstanceOf('Oro\\Bundle\\EntityMergeBundle\\Metadata\\EntityMetadata', $metadata);
        $this->assertEquals($options, $metadata->all());
        $this->assertEquals($doctrineMetadata, $metadata->getDoctrineMetadata());
    }

    public function testCreateEntityMetadataFromArray()
    {
        $options = array('foo' => 'bar');
        $doctrineMetadata = array('doctrineOption' => 'test');

        $metadata = $this->factory->createEntityMetadata($options, $doctrineMetadata);
        $this->assertInstanceOf('Oro\\Bundle\\EntityMergeBundle\\Metadata\\EntityMetadata', $metadata);
        $this->assertInstanceOf(
            'Oro\\Bundle\\EntityMergeBundle\\Metadata\\DoctrineMetadata',
            $metadata->getDoctrineMetadata()
        );
    }

    //@codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage $doctrineMetadata must be an array of "Oro\Bundle\EntityMergeBundle\Metadata\DoctrineMetadata", but "stdClass" given.
     */
    //@codingStandardsIgnoreEnd
    public function testCreateEntityMetadataFails()
    {
        $options = array('foo' => 'bar');
        $doctrineMetadata = new \stdClass();

        $this->factory->createEntityMetadata($options, $doctrineMetadata);
    }

    public function testCreateFieldMetadata()
    {
        $options = array('foo' => 'bar');
        $doctrineMetadata = $this->getMock('Oro\\Bundle\\EntityMergeBundle\\Metadata\\DoctrineMetadata');

        $metadata = $this->factory->createFieldMetadata($options, $doctrineMetadata);
        $this->assertInstanceOf('Oro\\Bundle\\EntityMergeBundle\\Metadata\\FieldMetadata', $metadata);
        $this->assertEquals($options, $metadata->all());
        $this->assertEquals($doctrineMetadata, $metadata->getDoctrineMetadata());
    }

    public function testCreateFieldMetadataFromArray()
    {
        $options = array('foo' => 'bar');
        $doctrineMetadata = array('doctrineOption' => 'test');

        $metadata = $this->factory->createFieldMetadata($options, $doctrineMetadata);
        $this->assertInstanceOf('Oro\\Bundle\\EntityMergeBundle\\Metadata\\FieldMetadata', $metadata);
        $this->assertInstanceOf(
            'Oro\\Bundle\\EntityMergeBundle\\Metadata\\DoctrineMetadata',
            $metadata->getDoctrineMetadata()
        );
    }

    //@codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage $doctrineMetadata must be an array of "Oro\Bundle\EntityMergeBundle\Metadata\DoctrineMetadata", but "stdClass" given.
     */
    //@codingStandardsIgnoreEnd
    public function testCreateFieldMetadataFails()
    {
        $options = array('foo' => 'bar');
        $doctrineMetadata = new \stdClass();

        $this->factory->createFieldMetadata($options, $doctrineMetadata);
    }

    public function testCreateDoctrineMetadata()
    {
        $options = array('foo' => 'bar');
        $doctrineMetadata = $this->getMock('Oro\\Bundle\\EntityMergeBundle\\Metadata\\DoctrineMetadata');

        $metadata = $this->factory->createFieldMetadata($options, $doctrineMetadata);
        $this->assertInstanceOf('Oro\\Bundle\\EntityMergeBundle\\Metadata\\FieldMetadata', $metadata);
        $this->assertEquals($options, $metadata->all());
        $this->assertEquals($doctrineMetadata, $metadata->getDoctrineMetadata());
    }
}

<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Metadata;

use Doctrine\ORM\Mapping\ClassMetadataInfo;

use Oro\Bundle\EntityMergeBundle\Metadata\DoctrineMetadata;

class DoctrineMetadataTest extends \PHPUnit_Framework_TestCase
{
    const CLASS_NAME = 'Namespace\Entity';

    /**
     * @var array
     */
    protected $options;

    /**
     * @var DoctrineMetadata
     */
    protected $doctrineMetadata;

    protected function setUp()
    {
        $this->doctrineMetadata = new DoctrineMetadata();
    }

    public function testGetFieldName()
    {
        $expectedFieldName = 'test';
        $this->doctrineMetadata->set('fieldName', $expectedFieldName);
        $this->assertEquals($expectedFieldName, $this->doctrineMetadata->getFieldName());
    }

    /**
     * @expectedException \Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Option "fieldName" not exists
     */
    public function testGetFieldNameFails()
    {
        $this->doctrineMetadata->getFieldName();
    }

    public function testIsField()
    {
        $this->assertTrue($this->doctrineMetadata->isField());

        $this->doctrineMetadata->set('targetEntity', self::CLASS_NAME);
        $this->assertFalse($this->doctrineMetadata->isField());

        $this->doctrineMetadata->set('joinColumns', []);
        $this->assertFalse($this->doctrineMetadata->isField());
    }

    public function testIsAssociation()
    {
        $this->assertFalse($this->doctrineMetadata->isAssociation());

        $this->doctrineMetadata->set('targetEntity', self::CLASS_NAME);
        $this->assertTrue($this->doctrineMetadata->isAssociation());

        $this->doctrineMetadata->set('joinColumns', []);
        $this->assertTrue($this->doctrineMetadata->isAssociation());
    }

    public function testIsOneToOne()
    {
        $this->assertFalse($this->doctrineMetadata->isOneToOne());

        $this->doctrineMetadata->set('type', ClassMetadataInfo::ONE_TO_ONE);
        $this->assertTrue($this->doctrineMetadata->isOneToOne());
    }

    public function testIsOneToMany()
    {
        $this->assertFalse($this->doctrineMetadata->isOneToMany());

        $this->doctrineMetadata->set('type', ClassMetadataInfo::ONE_TO_MANY);
        $this->assertTrue($this->doctrineMetadata->isOneToMany());
    }

    public function testIsManyToMany()
    {
        $this->assertFalse($this->doctrineMetadata->isManyToMany());

        $this->doctrineMetadata->set('type', ClassMetadataInfo::MANY_TO_MANY);
        $this->assertTrue($this->doctrineMetadata->isManyToMany());
    }

    public function testIsManyToOne()
    {
        $this->assertFalse($this->doctrineMetadata->isManyToOne());

        $this->doctrineMetadata->set('type', ClassMetadataInfo::MANY_TO_ONE);
        $this->assertTrue($this->doctrineMetadata->isManyToOne());
    }

    public function testIsTypeEqual()
    {
        $expectedType = ClassMetadataInfo::ONE_TO_MANY;
        $this->doctrineMetadata->set('type', $expectedType);
        $this->assertTrue($this->doctrineMetadata->isTypeEqual($expectedType));
        $this->assertFalse($this->doctrineMetadata->isTypeEqual(ClassMetadataInfo::ONE_TO_ONE));
    }

    public function testOrphanRemoval()
    {
        $this->assertFalse($this->doctrineMetadata->isOrphanRemoval());

        $this->doctrineMetadata->set('orphanRemoval', true);
        $this->assertTrue($this->doctrineMetadata->isOrphanRemoval());
    }
}

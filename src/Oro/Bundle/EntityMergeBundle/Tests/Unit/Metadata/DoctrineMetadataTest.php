<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Metadata;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Oro\Bundle\EntityMergeBundle\Metadata\DoctrineMetadata;

class DoctrineMetadataTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY = 'Namespace\Entity';

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
        $this->doctrineMetadata = new DoctrineMetadata(self::ENTITY);
    }

    public function testIsField()
    {
        $this->assertTrue($this->doctrineMetadata->isField());

        $this->doctrineMetadata->set('targetEntity', self::ENTITY);
        $this->assertTrue($this->doctrineMetadata->isField());

        $this->doctrineMetadata->set('joinColumns', []);
        $this->assertFalse($this->doctrineMetadata->isField());
    }

    public function testIsAssociation()
    {
        $this->assertFalse($this->doctrineMetadata->isAssociation());

        $this->doctrineMetadata->set('targetEntity', self::ENTITY);
        $this->assertFalse($this->doctrineMetadata->isAssociation());

        $this->doctrineMetadata->set('joinColumns', []);
        $this->assertTrue($this->doctrineMetadata->isAssociation());
    }

    public function testIsCollection()
    {
        $this->assertFalse($this->doctrineMetadata->isCollection());

        $this->doctrineMetadata->set('type', ClassMetadataInfo::MANY_TO_ONE);
        $this->assertFalse($this->doctrineMetadata->isCollection());

        $this->doctrineMetadata->set('type', ClassMetadataInfo::ONE_TO_MANY);
        $this->assertTrue($this->doctrineMetadata->isCollection());
    }

    public function testIsMappedBySourceEntity()
    {
        $this->assertTrue($this->doctrineMetadata->isMappedBySourceEntity());

        $this->doctrineMetadata->set('targetEntity', self::ENTITY);
        $this->doctrineMetadata->set('joinColumns', []);
        $this->assertFalse($this->doctrineMetadata->isMappedBySourceEntity());

        $this->doctrineMetadata->set('sourceEntity', self::ENTITY);
        $this->assertTrue($this->doctrineMetadata->isMappedBySourceEntity());
    }
}

<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Metadata;

use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityConfigBundle\Metadata\FieldMetadata;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\Fixture\DemoEntity;

class FieldMetadataTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EntityMetadata
     */
    protected $entityMetadata;

    /**
     * @var FieldMetadata
     */
    protected $fieldMetadata;

    protected function setUp()
    {
        $this->entityMetadata       = new EntityMetadata(DemoEntity::ENTITY_NAME);
        $this->entityMetadata->mode = ConfigModel::MODE_DEFAULT;


        $this->fieldMetadata       = new FieldMetadata(new \ReflectionClass(DemoEntity::ENTITY_NAME), 'name');
        $this->fieldMetadata->mode = ConfigModel::MODE_DEFAULT;
    }

    public function testSerialize()
    {
        $this->assertEquals($this->fieldMetadata, unserialize(serialize($this->fieldMetadata)));
    }
}

<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Metadata;

use Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadata;

class EntitySecurityMetadataTest extends \PHPUnit_Framework_TestCase
{
    /** @var EntitySecurityMetadata */
    protected $entity;

    protected function setUp()
    {
        $this->entity = new EntitySecurityMetadata(
            'SomeType',
            'SomeClass',
            'SomeGroup',
            'SomeLabel'
        );
    }

    protected function tearDown()
    {
        unset($this->entity);
    }

    public function testGetters()
    {
        static::assertEquals('SomeType', $this->entity->getSecurityType());
        static::assertEquals('SomeClass', $this->entity->getClassName());
        static::assertEquals('SomeGroup', $this->entity->getGroup());
        static::assertEquals('SomeLabel', $this->entity->getLabel());
    }

    public function testSerialize()
    {
        $data        = serialize($this->entity);
        $emptyEntity = unserialize($data);

        static::assertEquals('SomeType', $emptyEntity->getSecurityType());
        static::assertEquals('SomeClass', $emptyEntity->getClassName());
        static::assertEquals('SomeGroup', $emptyEntity->getGroup());
        static::assertEquals('SomeLabel', $emptyEntity->getLabel());
    }
}

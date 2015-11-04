<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\SecurityBundle\Entity\AclObjectIdentity;

class AclObjectIdentityTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY_ID          = 2;
    const CLASS_ID           = 3;
    const OBJECT_IDENTIFIER  = 'entity';
    const ENTRIES_INHERITING = true;

    /**
     * @var AclObjectIdentity
     */
    protected $entity;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->entity = new AclObjectIdentity();
    }

    public function testGettersSetters()
    {
        $class = new \ReflectionClass($this->entity);
        $prop = $class->getProperty('id');
        $prop->setAccessible(true);
        $prop->setValue($this->entity, self::ENTITY_ID);
        $this->assertEquals(self::ENTITY_ID, $this->entity->getId());

        $children = new ArrayCollection([new AclObjectIdentity()]);
        $this->entity->setChildren($children);
        $this->assertEquals($children, $this->entity->getChildren());

        $parent = new AclObjectIdentity();
        $this->entity->setParent($parent);
        $this->assertEquals($parent, $this->entity->getParent());

        $this->entity->setClassId(self::CLASS_ID);
        $this->assertEquals(self::CLASS_ID, $this->entity->getClassId());

        $this->entity->setObjectIdentifier(self::OBJECT_IDENTIFIER);
        $this->assertEquals(self::OBJECT_IDENTIFIER, $this->entity->getObjectIdentifier());

        $this->entity->setEntriesInheriting(self::ENTRIES_INHERITING);
        $this->assertEquals(self::ENTRIES_INHERITING, $this->entity->getEntriesInheriting());
    }
}

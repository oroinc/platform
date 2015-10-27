<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Entity;

use Oro\Bundle\SecurityBundle\Entity\AclSecurityIdentity;

class AclSecurityIdentityTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY_ID = 2;
    const IDENTIFIER = 'OroCRM\Bundle\ContactBundle\Entity\Contact';
    const USERNAME = true;

    /**
     * @var AclSecurityIdentity
     */
    protected $entity;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->entity = new AclSecurityIdentity();
    }

    public function testGettersSetters()
    {
        $class = new \ReflectionClass($this->entity);
        $prop = $class->getProperty('id');
        $prop->setAccessible(true);
        $prop->setValue($this->entity, self::ENTITY_ID);
        $this->assertEquals(self::ENTITY_ID, $this->entity->getId());

        $this->entity->setIdentifier(self::IDENTIFIER);
        $this->assertEquals(self::IDENTIFIER, $this->entity->getIdentifier());

        $this->entity->setUsername(self::USERNAME);
        $this->assertEquals(self::USERNAME, $this->entity->getUsername());
    }
}

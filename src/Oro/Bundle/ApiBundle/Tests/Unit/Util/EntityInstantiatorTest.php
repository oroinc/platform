<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;
use Oro\Bundle\ApiBundle\Util\EntityInstantiator;

class EntityInstantiatorTest extends OrmRelatedTestCase
{
    const ENTITY_NAMESPACE = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\\';

    /** @var EntityInstantiator */
    protected $entityInstantiator;

    protected function setUp()
    {
        parent::setUp();

        $this->entityInstantiator = new EntityInstantiator($this->doctrineHelper);
    }

    public function testInstantiateEntityWithoutConstructor()
    {
        $this->assertInstanceOf(
            self::ENTITY_NAMESPACE . 'Group',
            $this->entityInstantiator->instantiate(self::ENTITY_NAMESPACE . 'Group')
        );
    }

    public function testInstantiateEntityWithConstructorWithRequiredArguments()
    {
        $this->assertInstanceOf(
            self::ENTITY_NAMESPACE . 'Category',
            $this->entityInstantiator->instantiate(self::ENTITY_NAMESPACE . 'Category')
        );
    }

    public function testInstantiateEntityWithConstructorWithoutRequiredArgumentsAndWithToMany()
    {
        $entity = $this->entityInstantiator->instantiate(self::ENTITY_NAMESPACE . 'User');
        $this->assertInstanceOf(
            self::ENTITY_NAMESPACE . 'User',
            $entity
        );
        $this->assertInstanceOf(
            'Doctrine\Common\Collections\ArrayCollection',
            $entity->getGroups()
        );
    }

    public function testInstantiateEntityWithConstructorWithRequiredArgumentsAndWithToMany()
    {
        $entity = $this->entityInstantiator->instantiate(self::ENTITY_NAMESPACE . 'Company');
        $this->assertInstanceOf(
            self::ENTITY_NAMESPACE . 'Company',
            $entity
        );
        $this->assertInstanceOf(
            'Doctrine\Common\Collections\ArrayCollection',
            $entity->getGroups()
        );
    }
}

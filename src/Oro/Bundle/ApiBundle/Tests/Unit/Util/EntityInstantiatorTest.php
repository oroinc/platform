<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Category;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Company;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\UserProfile;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\UserProfileWithConstructor;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;
use Oro\Bundle\ApiBundle\Util\EntityInstantiator;

class EntityInstantiatorTest extends OrmRelatedTestCase
{
    /** @var EntityInstantiator */
    private $entityInstantiator;

    protected function setUp()
    {
        parent::setUp();

        $this->entityInstantiator = new EntityInstantiator($this->doctrineHelper);
    }

    public function testInstantiateEntityWithoutConstructor()
    {
        self::assertInstanceOf(
            Group::class,
            $this->entityInstantiator->instantiate(Group::class)
        );
    }

    public function testInstantiateObjectWithoutConstructor()
    {
        $this->notManageableClassNames = [Group::class];

        self::assertInstanceOf(
            Group::class,
            $this->entityInstantiator->instantiate(Group::class)
        );
    }

    public function testInstantiateEntityThatHasConstructorWithRequiredArguments()
    {
        self::assertInstanceOf(
            Category::class,
            $this->entityInstantiator->instantiate(Category::class)
        );
    }

    public function testInstantiateObjectThatHasConstructorWithRequiredArguments()
    {
        $this->notManageableClassNames = [Category::class];

        self::assertInstanceOf(
            Category::class,
            $this->entityInstantiator->instantiate(Category::class)
        );
    }

    public function testInstantiateEntityThatHasConstructorWithoutRequiredArgumentsAndWithToManyAssociation()
    {
        /** @var User $entity */
        $entity = $this->entityInstantiator->instantiate(User::class);
        self::assertInstanceOf(User::class, $entity);
        self::assertInstanceOf(ArrayCollection::class, $entity->getGroups());
    }

    public function testInstantiateObjectThatHasConstructorWithoutRequiredArgumentsAndWithToManyAssociation()
    {
        $this->notManageableClassNames = [User::class];

        /** @var User $entity */
        $object = $this->entityInstantiator->instantiate(User::class);
        self::assertInstanceOf(User::class, $object);
        self::assertInstanceOf(ArrayCollection::class, $object->getGroups());
    }

    public function testInstantiateEntityThatHasConstructorWithRequiredArgumentsAndWithToManyAssociation()
    {
        /** @var Company $entity */
        $entity = $this->entityInstantiator->instantiate(Company::class);
        self::assertInstanceOf(Company::class, $entity);
        self::assertInstanceOf(ArrayCollection::class, $entity->getGroups());
    }

    public function testInstantiateObjectThatHasConstructorWithRequiredArgumentsAndWithToManyAssociation()
    {
        $this->notManageableClassNames = [Company::class];

        /** @var Company $entity */
        $object = $this->entityInstantiator->instantiate(Company::class);
        self::assertInstanceOf(Company::class, $object);
        self::assertNull($object->getGroups());
    }

    public function testInstantiateModelInheritedFromEntityWithoutOwnConstructor()
    {
        $this->notManageableClassNames = [UserProfile::class];

        /** @var UserProfile $entity */
        $entity = $this->entityInstantiator->instantiate(UserProfile::class);
        self::assertInstanceOf(UserProfile::class, $entity);
        self::assertInstanceOf(ArrayCollection::class, $entity->getGroups());
    }

    public function testInstantiateModelInheritedFromEntityWhenModelHasOwnConstructorWithRequiredParameters()
    {
        $this->notManageableClassNames = [UserProfileWithConstructor::class];

        /** @var UserProfileWithConstructor $entity */
        $entity = $this->entityInstantiator->instantiate(UserProfileWithConstructor::class);
        self::assertInstanceOf(UserProfileWithConstructor::class, $entity);
        self::assertInstanceOf(ArrayCollection::class, $entity->getGroups());
    }
}

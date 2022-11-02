<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\NotificationBundle\Entity\RecipientList;
use Oro\Bundle\UserBundle\Entity\Group;
use Oro\Bundle\UserBundle\Entity\User;

class RecipientListTest extends \PHPUnit\Framework\TestCase
{
    private RecipientList $entity;

    protected function setUp(): void
    {
        $this->entity = new RecipientList();
    }

    public function testEmptyRecipientList(): void
    {
        // get id should return null cause this entity was not loaded from DB
        self::assertNull($this->entity->getId());

        self::assertInstanceOf(ArrayCollection::class, $this->entity->getUsers());
        self::assertInstanceOf(ArrayCollection::class, $this->entity->getGroups());
    }

    public function testSetterGetterForUsers(): void
    {
        // test adding through array collection interface
        $user = $this->createMock(User::class);
        $this->entity->getUsers()->add($user);

        self::assertContains($user, $this->entity->getUsers());

        // clear collection
        $this->entity->getUsers()->clear();
        self::assertTrue($this->entity->getUsers()->isEmpty());

        // test setter
        $this->entity->addUser($user);
        self::assertContains($user, $this->entity->getUsers());

        // remove group
        $this->entity->removeUser($user);
        self::assertTrue($this->entity->getUsers()->isEmpty());
    }

    public function testSetterGetterForGroups(): void
    {
        // test adding through array collection interface
        $group = $this->createMock(Group::class);
        $this->entity->getGroups()->add($group);

        self::assertContains($group, $this->entity->getGroups());

        // clear collection
        $this->entity->getGroups()->clear();
        self::assertTrue($this->entity->getGroups()->isEmpty());

        // test setter
        $this->entity->addGroup($group);
        self::assertContains($group, $this->entity->getGroups());

        // remove group
        $this->entity->removeGroup($group);
        self::assertTrue($this->entity->getGroups()->isEmpty());
    }

    public function testSetterGetterForEmail(): void
    {
        self::assertNull($this->entity->getEmail());

        $this->entity->setEmail('test');
        self::assertEquals('test', $this->entity->getEmail());
    }

    public function testSetterGetterForEntityEmails(): void
    {
        $entityFields = ['field1', 'field2'];

        self::assertEquals([], $this->entity->getEntityEmails());

        $this->entity->setEntityEmails($entityFields);
        self::assertEquals($entityFields, $this->entity->getEntityEmails());
    }

    public function testToString(): void
    {
        $group = $this->createMock(Group::class);
        $user = $this->createMock(User::class);

        // test when email filled
        $this->entity->setEmail('test email');
        self::assertIsString($this->entity->__toString());
        self::assertNotEmpty($this->entity->__toString());
        // clear email
        $this->entity->setEmail(null);

        // test when users filled
        $this->entity->addUser($user);
        self::assertIsString($this->entity->__toString());
        self::assertNotEmpty($this->entity->__toString());
        // clear users
        $this->entity->getUsers()->clear();

        // test when groups filled
        $this->entity->addGroup($group);
        self::assertIsString($this->entity->__toString());
        self::assertNotEmpty($this->entity->__toString());
        // clear groups
        $this->entity->getGroups()->clear();

        // should be empty if nothing filled
        self::assertEmpty($this->entity->__toString());
    }
}

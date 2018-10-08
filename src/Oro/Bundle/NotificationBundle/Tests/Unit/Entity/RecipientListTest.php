<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Entity;

use Oro\Bundle\NotificationBundle\Entity\RecipientList;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class RecipientListTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var RecipientList
     */
    protected $entity;

    protected function setUp()
    {
        $this->entity = new RecipientList();
    }

    protected function tearDown()
    {
        unset($this->entity);
    }

    public function testEmptyRecipientList()
    {
        // get id should return null cause this entity was not loaded from DB
        $this->assertNull($this->entity->getId());

        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $this->entity->getUsers());
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $this->entity->getGroups());
    }

    public function testSetterGetterForUsers()
    {
        // test adding through array collection interface
        $user = $this->createMock('Oro\Bundle\UserBundle\Entity\User');
        $this->entity->getUsers()->add($user);

        $this->assertContains($user, $this->entity->getUsers());

        // clear collection
        $this->entity->getUsers()->clear();
        $this->assertTrue($this->entity->getUsers()->isEmpty());

        // test setter
        $this->entity->addUser($user);
        $this->assertContains($user, $this->entity->getUsers());


        // remove group
        $this->entity->removeUser($user);
        $this->assertTrue($this->entity->getUsers()->isEmpty());
    }

    public function testSetterGetterForGroups()
    {
        // test adding through array collection interface
        $group = $this->createMock('Oro\Bundle\UserBundle\Entity\Group');
        $this->entity->getGroups()->add($group);

        $this->assertContains($group, $this->entity->getGroups());

        // clear collection
        $this->entity->getGroups()->clear();
        $this->assertTrue($this->entity->getGroups()->isEmpty());

        // test setter
        $this->entity->addGroup($group);
        $this->assertContains($group, $this->entity->getGroups());


        // remove group
        $this->entity->removeGroup($group);
        $this->assertTrue($this->entity->getGroups()->isEmpty());
    }

    public function testSetterGetterForEmail()
    {
        $this->assertNull($this->entity->getEmail());

        $this->entity->setEmail('test');
        $this->assertEquals('test', $this->entity->getEmail());
    }

    public function testSetterGetterForEntityEmails()
    {
        $entityFields = ['field1', 'field2'];

        $this->assertEquals([], $this->entity->getEntityEmails());

        $this->entity->setEntityEmails($entityFields);
        $this->assertEquals($entityFields, $this->entity->getEntityEmails());
    }

    public function testToString()
    {
        $group = $this->createMock('Oro\Bundle\UserBundle\Entity\Group');
        $user = $this->createMock('Oro\Bundle\UserBundle\Entity\User');

        // test when email filled
        $this->entity->setEmail('test email');
        $this->assertInternalType('string', $this->entity->__toString());
        $this->assertNotEmpty($this->entity->__toString());
        // clear email
        $this->entity->setEmail(null);

        // test when users filled
        $this->entity->addUser($user);
        $this->assertInternalType('string', $this->entity->__toString());
        $this->assertNotEmpty($this->entity->__toString());
        // clear users
        $this->entity->getUsers()->clear();

        // test when groups filled
        $this->entity->addGroup($group);
        $this->assertInternalType('string', $this->entity->__toString());
        $this->assertNotEmpty($this->entity->__toString());
        // clear groups
        $this->entity->getGroups()->clear();

        // should be empty if nothing filled
        $this->assertEmpty($this->entity->__toString());
    }

    public function testNotValidData()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|ExecutionContextInterface $context */
        $context = $this->createMock(ExecutionContextInterface::class);

        $context->expects($this->once())
            ->method('getPropertyPath')
            ->will($this->returnValue('testPath'));

        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $context->expects($this->once())
            ->method('buildViolation')
            ->willReturn($builder);
        $builder->expects($this->once())
            ->method('atPath')
            ->willReturnSelf();
        $builder->expects($this->once())
            ->method('addViolation');

        $this->entity->isValid($context);
    }

    public function testValidData()
    {
        $group = $this->createMock('Oro\Bundle\UserBundle\Entity\Group');
        $user = $this->createMock('Oro\Bundle\UserBundle\Entity\User');

        /** @var \PHPUnit\Framework\MockObject\MockObject|ExecutionContextInterface $context */
        $context = $this->createMock(ExecutionContextInterface::class);

        $context->expects($this->never())
            ->method('getPropertyPath');
        $context->expects($this->never())
            ->method('buildViolation');

        // Only users
        $this->entity->addUser($user);
        $this->entity->isValid($context);
        // clear users
        $this->entity->getUsers()->clear();

        // Only groups
        $this->entity->addGroup($group);
        $this->entity->isValid($context);
        // clear groups
        $this->entity->getGroups()->clear();

        // Only email
        $this->entity->setEmail('test Email');
        $this->entity->isValid($context);
        $this->entity->setEmail(null);

        // Only entity emails
        $this->entity->setEntityEmails(['field1']);
        $this->entity->isValid($context);
        $this->entity->setEntityEmails([]);
    }
}

<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Form\EventListener\ChangeRoleSubscriber;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class ChangeRoleSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ChangeRoleSubscriber
     */
    protected $changeRoleSubscriber;

    protected function setUp()
    {
        $this->changeRoleSubscriber = new ChangeRoleSubscriber();
    }

    public function testGetSubscribedEvents()
    {
        $this->assertEquals(
            [FormEvents::SUBMIT => ['onSubmit', 10]],
            ChangeRoleSubscriber::getSubscribedEvents()
        );
    }

    public function testSubmit()
    {
        $role = $this->createMock(Role::class);
        $user = $this->createMock(User::class);

        $user->expects($this->exactly(1))
            ->method('addRole')
            ->with($role);

        $user->expects($this->exactly(1))
            ->method('removeRole')
            ->with($role);

        /** @var Form|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->getMockBuilder(Form::class)
            ->disableOriginalConstructor()
            ->getMock();

        $child = $this->getMockBuilder(Form::class)
            ->disableOriginalConstructor()
            ->getMock();

        $child->expects($this->exactly(2))
            ->method('getData')
            ->willReturn([$user]);

        $form->expects($this->at(0))
            ->method('get')
            ->with('appendUsers')
            ->willReturn($child);

        $form->expects($this->at(1))
            ->method('get')
            ->with('removeUsers')
            ->willReturn($child);

        $event = new FormEvent($form, $role);
        $this->changeRoleSubscriber->onSubmit($event);
    }
}

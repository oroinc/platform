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
    /** @var ChangeRoleSubscriber */
    private $changeRoleSubscriber;

    protected function setUp(): void
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

        $user->expects($this->once())
            ->method('addUserRole')
            ->with($role);
        $user->expects($this->once())
            ->method('removeUserRole')
            ->with($role);

        $form = $this->createMock(Form::class);
        $child = $this->createMock(Form::class);

        $child->expects($this->exactly(2))
            ->method('getData')
            ->willReturn([$user]);

        $form->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['appendUsers', $child],
                ['removeUsers', $child]
            ]);

        $event = new FormEvent($form, $role);
        $this->changeRoleSubscriber->onSubmit($event);
    }
}

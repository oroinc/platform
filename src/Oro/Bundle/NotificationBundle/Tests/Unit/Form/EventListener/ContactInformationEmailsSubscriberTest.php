<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\NotificationBundle\Entity\EmailNotification;
use Oro\Bundle\NotificationBundle\Entity\RecipientList;
use Oro\Bundle\NotificationBundle\Form\EventListener\ContactInformationEmailsSubscriber;
use Oro\Bundle\NotificationBundle\Provider\ContactInformationEmailsProvider;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;

class ContactInformationEmailsSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContactInformationEmailsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $contactInformationEmailsProvider;

    /** @var ContactInformationEmailsSubscriber */
    private $subscriber;

    protected function setUp(): void
    {
        $this->contactInformationEmailsProvider = $this->createMock(ContactInformationEmailsProvider::class);

        $this->subscriber = new ContactInformationEmailsSubscriber($this->contactInformationEmailsProvider);
    }

    public function testPreSetData()
    {
        $emailFields = ['emailAddress', 'secondaryEmail', 'thirdEmail'];

        $this->contactInformationEmailsProvider->expects($this->once())
            ->method('getRecipients')
            ->with(RecipientList::class)
            ->willReturn($emailFields);

        $notification = $this->createMock(EmailNotification::class);
        $notification->expects($this->any())
            ->method('getEntityName')
            ->willReturn(RecipientList::class);
        $notification->expects($this->any())
            ->method('hasEntityName')
            ->willReturn(true);

        $recipientListForm = $this->createMock(FormInterface::class);
        $recipientListForm->expects($this->once())
            ->method('add')
            ->with(
                'entityEmails',
                ChoiceType::class,
                [
                    'label' => 'oro.notification.emailnotification.contact_emails.label',
                    'required' => false,
                    'multiple' => true,
                    'expanded' => true,
                    'choices' => $emailFields,
                    'tooltip' => 'oro.notification.emailnotification.additional_emails.tooltip',
                ]
            );

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('offsetGet')
            ->with('recipientList')
            ->willReturn($recipientListForm);

        $event = $this->createMock(FormEvent::class);
        $event->expects($this->once())
            ->method('getData')
            ->willReturn($notification);

        $event->expects($this->once())
            ->method('getForm')
            ->willReturn($form);

        $this->subscriber->preSetData($event);
    }

    public function testPreSubmitData()
    {
        $emailFields = ['emailAddress', 'secondaryEmail', 'thirdEmail'];

        $this->contactInformationEmailsProvider->expects($this->once())
            ->method('getRecipients')
            ->with(RecipientList::class)
            ->willReturn($emailFields);

        $notification = $this->createMock(EmailNotification::class);
        $notification->expects($this->any())
            ->method('getEntityName')
            ->willReturn(RecipientList::class);
        $notification->expects($this->any())
            ->method('hasEntityName')
            ->willReturn(true);

        $recipientListForm = $this->createMock(FormInterface::class);
        $recipientListForm->expects($this->once())
            ->method('add')
            ->with(
                'entityEmails',
                ChoiceType::class,
                [
                    'label' => 'oro.notification.emailnotification.contact_emails.label',
                    'required' => false,
                    'multiple' => true,
                    'expanded' => true,
                    'choices' => $emailFields,
                    'tooltip' => 'oro.notification.emailnotification.additional_emails.tooltip',
                ]
            );

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('offsetGet')
            ->with('recipientList')
            ->willReturn($recipientListForm);

        $event = $this->createMock(FormEvent::class);
        $event->expects($this->once())
            ->method('getData')
            ->willReturn(['entityName' => RecipientList::class]);

        $event->expects($this->once())
            ->method('getForm')
            ->willReturn($form);

        $this->subscriber->preSubmit($event);
    }
}

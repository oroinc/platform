<?php

namespace Oro\Bundle\NotificationBundle\Form\EventListener;

use Oro\Bundle\NotificationBundle\Entity\EmailNotification;
use Oro\Bundle\NotificationBundle\Provider\ContactInformationEmailsProvider;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

class ContactInformationEmailsSubscriber implements EventSubscriberInterface
{
    /**
     * @var ContactInformationEmailsProvider
     */
    private $contactInformationEmailsProvider;

    /**
     * @param ContactInformationEmailsProvider $contactInformationEmailsProvider
     */
    public function __construct(
        ContactInformationEmailsProvider $contactInformationEmailsProvider
    ) {
        $this->contactInformationEmailsProvider = $contactInformationEmailsProvider;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::PRE_SUBMIT   => 'preSubmit',
        ];
    }

    /**
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        /** @var EmailNotification $eventObject */
        $eventObject = $event->getData();
        $entityName = null;

        if (null !== $eventObject && $eventObject->hasEntityName()) {
            $entityName = $eventObject->getEntityName();
        }

        $this->initAdditionalRecipientChoices($entityName, $event->getForm());
    }

    /**
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        /** @var EmailNotification $eventObject */
        $data = $event->getData();
        $entityName = null;

        if (!empty($data['entityName'])) {
            $entityName = $data['entityName'];
        }

        $this->initAdditionalRecipientChoices($entityName, $event->getForm());
    }

    /**
     * @param $entityName
     * @param FormInterface $form
     */
    private function initAdditionalRecipientChoices($entityName, FormInterface $form)
    {
        $choices = [];

        if ($entityName !== null) {
            $choices = $this->contactInformationEmailsProvider->getRecipients($entityName);
        }

        $form->offsetGet('recipientList')->add(
            'entityEmails',
            ChoiceType::class,
            [
                'label' => 'oro.notification.emailnotification.contact_emails.label',
                'required' => false,
                'multiple' => true,
                'expanded' => true,
                'choices' => $choices,
                'tooltip' => 'oro.notification.emailnotification.additional_emails.tooltip',
            ]
        );
    }
}

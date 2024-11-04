<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\EmailBundle\Form\Model\EmailAttachment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Form type for collection of Email Attachments.
 */
class EmailAttachmentsType extends AbstractType
{
    #[\Override]
    public function getParent(): ?string
    {
        return CollectionType::class;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_email_attachments';
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::SUBMIT, [$this, 'sanitizeAttachments']);
    }

    public function sanitizeAttachments(FormEvent $event)
    {
        /** @var Collection $attachments */
        $attachments = $event->getData();
        /** @var EmailAttachment $attachment */
        foreach ($attachments as $attachment) {
            if (!$attachment) {
                $attachments->removeElement($attachment);
            }
        }

        $event->setData($attachments);
    }
}

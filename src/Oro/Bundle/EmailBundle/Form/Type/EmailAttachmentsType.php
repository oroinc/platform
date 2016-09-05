<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Doctrine\Common\Collections\Collection;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\EmailBundle\Form\Model\EmailAttachment;

class EmailAttachmentsType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'collection';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_email_attachments';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::SUBMIT, [$this, 'sanitizeAttachments']);
    }

    /**
     * @param FormEvent $event
     */
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

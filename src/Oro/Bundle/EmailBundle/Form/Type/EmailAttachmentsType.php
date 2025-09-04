<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\EmailBundle\Form\Model\EmailAttachment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

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

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $emailAttachments = $form->getData();

        $view->vars['entity_attachments_array'] = [];
        if (is_iterable($emailAttachments)) {
            $indexes = [];
            foreach ($emailAttachments as $emailAttachment) {
                if ($emailAttachment instanceof EmailAttachment) {
                    $id = $emailAttachment->getId();
                    if ($emailAttachment->getType() === EmailAttachment::TYPE_EMAIL_TEMPLATE_ATTACHMENT) {
                        // Index must be scoped to the email attachment ID and start from 0.
                        $indexes[$id] = ($indexes[$id] ?? -1) + 1;
                        $id .= ':' . $indexes[$id];
                    }

                    $view->vars['entity_attachments_array'][] = [
                        'id' => $id,
                        'type' => $emailAttachment->getType(),
                        'fileName' => $emailAttachment->getFileName(),
                        'icon' => $emailAttachment->getIcon(),
                        'errors' => $emailAttachment->getErrors(),
                    ];
                }
            }
        }

        $availableAttachments = $form->getParent()?->getData()?->getAttachmentsAvailable();

        $view->vars['attachments_available_array'] = [];
        if (is_iterable($availableAttachments)) {
            foreach ($availableAttachments as $emailAttachment) {
                $view->vars['attachments_available_array'][] = [
                    'id' => $emailAttachment->getId(),
                    'type' => $emailAttachment->getType(),
                    'fileName' => $emailAttachment->getFileName(),
                    'fileSize' => $emailAttachment->getFileSize(),
                    'modified' => $emailAttachment->getModified(),
                    'icon' => $emailAttachment->getIcon(),
                    'preview' => $emailAttachment->getPreview(),
                ];
            }
        }
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('allow_delete', true);
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

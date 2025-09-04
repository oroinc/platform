<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Form\Type;

use Oro\Bundle\AttachmentBundle\Form\Type\FileType;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateAttachment;
use Oro\Bundle\EmailBundle\Twig\EmailTemplateAttachmentVariablesProvider;
use Oro\Bundle\FormBundle\Form\Type\Select2ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for an email template attachment.
 */
final class EmailTemplateAttachmentType extends AbstractType
{
    public const string UPLOAD_FILE = '__upload_file__';

    public function __construct(
        private readonly EmailTemplateAttachmentVariablesProvider $emailTemplateAttachmentVariablesProvider
    ) {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'filePlaceholder',
            Select2ChoiceType::class,
            [
                'required' => false,
                'choices' => $this->loadChoices($options['entity_class']),
                'placeholder' => 'oro.email.emailtemplateattachment.file_placeholder.placeholder',
                'empty_data' => null,
            ]
        );
        $builder->add('file', FileType::class);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $emailTemplateAttachment = $event->getData();
            if ($emailTemplateAttachment?->getFile()) {
                $emailTemplateAttachment->setFilePlaceholder(self::UPLOAD_FILE);
            }
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            if ($data['filePlaceholder'] === self::UPLOAD_FILE) {
                $data['filePlaceholder'] = null;
                $event->setData($data);
            }
        });
    }

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        if ($form->get('file')->getData()) {
            $view['filePlaceholder']->vars['value'] = self::UPLOAD_FILE;
        }
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->define('entity_class')
            ->default(null)
            ->allowedTypes('string', 'null')
            ->info('FQCN of the entity to fetch available email template attachment variables from.');

        $resolver->setDefaults([
            'data_class' => EmailTemplateAttachment::class,
            'error_bubbling' => false,
        ]);
    }

    private function loadChoices(?string $entityClass): array
    {
        $choices = [
            'oro.email.emailtemplateattachment.file_placeholder.choices.upload_file_group' => [
                'oro.email.emailtemplateattachment.file_placeholder.choices.upload_file' => self::UPLOAD_FILE,
            ],
        ];

        if ($entityClass) {
            $attachmentVariables = $this->emailTemplateAttachmentVariablesProvider
                ->getAttachmentVariables($entityClass);

            $choices = array_combine(array_column($attachmentVariables, 'label'), array_keys($attachmentVariables)) +
                $choices;
        }

        return $choices;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_email_emailtemplate_attachment';
    }
}

<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for a collection of email template attachments.
 */
final class EmailTemplateAttachmentCollectionType extends AbstractType
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->define('entity_class')
            ->default(null)
            ->allowedTypes('string', 'null');

        $resolver->setDefaults([
            'entry_type' => EmailTemplateAttachmentType::class,
            'entry_options' => function (Options $options) {
                return [
                    'label' => false,
                    'required' => false,
                    'entity_class' => $options['entity_class'],
                ];
            },
            'allow_add' => true,
            'allow_delete' => true,
            'show_form_when_empty' => false,
        ]);
    }

    #[\Override]
    public function getParent(): string
    {
        return CollectionType::class;
    }
}

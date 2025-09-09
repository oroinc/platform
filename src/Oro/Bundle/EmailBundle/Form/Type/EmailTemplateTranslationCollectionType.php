<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for collection of EmailTemplateTranslation
 */
class EmailTemplateTranslationCollectionType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('default', EmailTemplateTranslationType::class, [
            'wysiwyg_enabled' => $options['wysiwyg_enabled'],
            'wysiwyg_options' => $options['wysiwyg_options'],
            'block_name' => 'template',
            'entity_class' => $options['entity_class'],
        ]);

        foreach ($options['localizations'] as $localization) {
            $builder->add($localization->getId(), EmailTemplateTranslationType::class, [
                'localization' => $localization,
                'wysiwyg_enabled' => $options['wysiwyg_enabled'],
                'wysiwyg_options' => $options['wysiwyg_options'],
                'block_name' => 'template',
                'entity_class' => $options['entity_class'],
            ]);
        }
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'localizations' => [],
            'wysiwyg_enabled' => false,
            'wysiwyg_options' => [],
        ]);

        $resolver
            ->define('entity_class')
            ->default(null)
            ->allowedTypes('string', 'null')
            ->info('FQCN of the entity to fetch available email template attachment variables from.');
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_email_emailtemplate_localizations';
    }
}

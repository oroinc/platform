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
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('default', EmailTemplateTranslationType::class, [
            'wysiwyg_enabled' => $options['wysiwyg_enabled'],
            'wysiwyg_options' => $options['wysiwyg_options'],
            'block_name' => 'template',
        ]);

        foreach ($options['localizations'] as $localization) {
            $builder->add($localization->getId(), EmailTemplateTranslationType::class, [
                'localization' => $localization,
                'wysiwyg_enabled' => $options['wysiwyg_enabled'],
                'wysiwyg_options' => $options['wysiwyg_options'],
                'block_name' => 'template',
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'localizations' => [],
            'wysiwyg_enabled' => false,
            'wysiwyg_options' => [],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'oro_email_emailtemplate_localizations';
    }
}

<?php

namespace Oro\Bundle\TranslationBundle\Form\Type;

use Oro\Bundle\TranslationBundle\Form\DataMapper\GedmoTranslationMapper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Translations locales (gedmo)
 */
class GedmoTranslationsLocalesType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $isDefaultTranslation = ('defaultLocale' === $builder->getName());

        // Custom mapper for translations
        if (!$isDefaultTranslation) {
            $builder->setDataMapper(new GedmoTranslationMapper());
        }

        foreach ($options['locales'] as $locale) {
            if (isset($options['fields_options'][$locale])) {
                $builder->add($locale, TranslationsFieldsType::class, [
                    'fields' => $options['fields_options'][$locale],
                    'translation_class' => $options['translation_class'],
                    'inherit_data' => $isDefaultTranslation,
                ]);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'locales' => [],
            'fields_options' => [],
            'translation_class' => null
        ]);
    }
}

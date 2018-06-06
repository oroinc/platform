<?php

namespace Oro\Bundle\LocaleBundle\Form\Type;

use Oro\Bundle\LocaleBundle\Form\DataTransformer\MultipleValueTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LocalizedPropertyType extends AbstractType
{
    const NAME = 'oro_locale_localized_property';

    const FIELD_DEFAULT = 'default';
    const FIELD_LOCALIZATIONS = 'localizations';

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
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $formType    = $options['entry_type'];
        $formOptions = $options['entry_options'];

        $builder
            ->add(
                self::FIELD_DEFAULT,
                $formType,
                array_merge($formOptions, ['label' => 'oro.locale.fallback.value.default'])
            )
            ->add(self::FIELD_LOCALIZATIONS, LocalizationCollectionType::class, [
                'entry_type' => $formType, 'entry_options' => $formOptions
            ]);

        $builder->addViewTransformer(new MultipleValueTransformer(self::FIELD_DEFAULT, self::FIELD_LOCALIZATIONS));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'entry_type',
        ]);

        $resolver->setDefaults([
            'entry_options' => [],
        ]);
    }
}

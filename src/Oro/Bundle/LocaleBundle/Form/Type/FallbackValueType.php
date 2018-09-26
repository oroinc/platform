<?php

namespace Oro\Bundle\LocaleBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use Oro\Bundle\LocaleBundle\Form\DataTransformer\FallbackValueTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Manage value for given localization.
 */
class FallbackValueType extends AbstractType
{
    const NAME = 'oro_locale_fallback_value';

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
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'entry_type',
        ]);

        $resolver->setDefaults([
            'data_class' => null,
            'entry_options' => [],
            'fallback_type' => FallbackPropertyType::class,
            'fallback_type_localization' => null,
            'fallback_type_parent_localization' => null,
            'enabled_fallbacks' => [],
            'group_fallback_fields' => null,
            'exclude_parent_localization' => false
        ]);

        $resolver->setNormalizer('group_fallback_fields', function (Options $options, $value) {
            if ($value !== null) {
                return $value;
            }

            return in_array($options['entry_type'], [TextareaType::class, OroRichTextType::class], true);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $valueOptions = array_merge($options['entry_options'], ['required' => false]);

        $builder
            ->add('value', $options['entry_type'], $valueOptions)
            ->add(
                'use_fallback',
                CheckboxType::class,
                [
                    'label' => $options['exclude_parent_localization']
                        ? 'oro.locale.fallback.use_fallback_to_default_value.label'
                        : 'oro.locale.fallback.use_fallback.label'
                ]
            )
            ->add(
                'fallback',
                $options['fallback_type'],
                [
                    'enabled_fallbacks' => $options['enabled_fallbacks'],
                    'localization' => $options['fallback_type_localization'],
                    'parent_localization' => $options['fallback_type_parent_localization'],
                    'required' => false
                ]
            );

        $builder->addViewTransformer(new FallbackValueTransformer());

        // disable validation is field uses fallback (because in this case value is null)
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($options, $valueOptions) {
            $data = $event->getData();
            if (is_array($data) && !empty($data['fallback'])) {
                $event->getForm()
                    ->remove('value')
                    ->add('value', $options['entry_type'], array_merge($valueOptions, ['validation_groups' => false]));
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['group_fallback_fields'] = $options['group_fallback_fields'];
        $view->vars['exclude_parent_localization'] = $options['exclude_parent_localization'];
    }
}

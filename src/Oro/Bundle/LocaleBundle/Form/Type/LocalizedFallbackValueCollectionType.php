<?php

namespace Oro\Bundle\LocaleBundle\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Form\DataTransformer\LocalizedFallbackValueCollectionTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Represents collection of localized fallback values.
 * Allows to customize mail field (string or text), actual entity class and subform elements.
 */
class LocalizedFallbackValueCollectionType extends AbstractType
{
    const NAME = 'oro_locale_localized_fallback_value_collection';

    const FIELD_VALUES = 'values';
    const FIELD_IDS    = 'ids';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
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
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            self::FIELD_VALUES,
            LocalizedPropertyType::class,
            [
                'entry_type' => $options['entry_type'],
                'entry_options' => $options['entry_options'],
                'exclude_parent_localization' => $options['exclude_parent_localization'],
                'use_tabs' => $options['use_tabs']
            ]
        )->add(
            self::FIELD_IDS,
            CollectionType::class,
            ['entry_type' => HiddenType::class]
        );

        $builder->addViewTransformer(
            new LocalizedFallbackValueCollectionTransformer($this->registry, $options['field'], $options['value_class'])
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'field' => 'string',                            // field used to store data - string or text
            'value_class' => LocalizedFallbackValue::class, // entity value class name used to store a data
            'entry_type' => TextType::class,                // value form type
            'entry_options' => [],                          // value form options
            'exclude_parent_localization' => false,
            'use_tabs' => false,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        if ($options['use_tabs']) {
            array_splice($view->vars['block_prefixes'], -1, 0, [$this->getBlockPrefix() . '_tabs']);
        }
    }
}

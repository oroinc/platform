<?php

namespace Oro\Bundle\FilterBundle\Form\Type\Filter;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SelectRowFilterType extends AbstractChoiceType
{
    const NAME = 'oro_type_selectrow_filter';

    const NOT_SELECTED_VALUE = 0;
    const SELECTED_VALUE     = 1;

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $emptyData = function ($form, $submittedData) {
            if ($submittedData === null) {
                return $submittedData;
            } elseif ($submittedData === '') {
                return array();
            }

            return null;
        };

        $builder->add('in', HiddenType::class, array('empty_data' => $emptyData));
        $builder->add('out', HiddenType::class, array('empty_data' => $emptyData));
    }

    /**
     * {@inheritDoc}
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
     * {@inheritDoc}
     */
    public function getParent()
    {
        return ChoiceFilterType::class;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'field_type' => ChoiceType::class,
                'field_options' => [
                    'choices' => [
                        $this->translator->trans('oro.filter.form.label_not_selected') => self::NOT_SELECTED_VALUE,
                        $this->translator->trans('oro.filter.form.label_selected') => self::SELECTED_VALUE,
                    ],
                ],
            ]
        );
    }
}

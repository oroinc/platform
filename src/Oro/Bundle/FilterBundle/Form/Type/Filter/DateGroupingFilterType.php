<?php

namespace Oro\Bundle\FilterBundle\Form\Type\Filter;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class DateGroupingFilterType extends AbstractChoiceType
{
    const NAME = 'oro_type_date_grouping_filter';
    const TYPE_DAY = 'day';
    const TYPE_MONTH = 'month';
    const TYPE_QUARTER = 'quarter';
    const TYPE_YEAR = 'year';

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
        return ChoiceFilterType::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'field_type'       => 'choice',
                'field_options'    => ['choices' => $this->getGroupChoices()],
                'default_value'    => null,
                'null_value'       => null,
                'class'            => null,
                'populate_default' => true,
            )
        );
    }

    /**
     * {@inheritDoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        parent::finishView($view, $form, $options);
        if (isset($options['populate_default'])) {
            $view->vars['populate_default'] = $options['populate_default'];
            $view->vars['default_value']    = $options['default_value'];
        }
        if (!empty($options['null_value'])) {
            $view->vars['null_value'] = $options['null_value'];
        }

        if (!empty($options['class'])) {
            $view->vars['class'] = $options['class'];
        }
    }

    protected function getGroupChoices()
    {
        return [
            self::TYPE_DAY,
            self::TYPE_MONTH,
            self::TYPE_QUARTER,
            self::TYPE_YEAR,
        ];
    }
}

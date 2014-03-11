<?php

namespace Oro\Bundle\FilterBundle\Form\Type\Filter;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use Oro\Bundle\FilterBundle\Form\Type\DateRangeType;

class DateRangeFilterType extends AbstractDateFilterType
{
    const NAME = 'oro_type_date_range_filter';

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return FilterType::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);

        $resolver->setDefaults(
            array(
                'field_type'       => DateRangeType::NAME,
                'widget_options'   => [
                    'showDatevariables' => true,
                    'showTime'          => false,
                    'showTimepicker'    => false,
                ],
                'operator_choices' => $this->getOperatorChoices(),
                'type_values'      => $this->getTypeValues(),
            )
        );
    }

    /**
     * @param FormView      $view
     * @param FormInterface $form
     * @param array         $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['type_values'] = $options['type_values'];

        parent::buildView($view, $form, $options);
    }
}

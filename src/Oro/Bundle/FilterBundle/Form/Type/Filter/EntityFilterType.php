<?php

namespace Oro\Bundle\FilterBundle\Form\Type\Filter;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\Options;

class EntityFilterType extends AbstractChoiceType
{
    const NAME = 'oro_type_entity_filter';

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
        return ChoiceFilterType::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'field_type'    => 'entity',
                'field_options' => array(),
                'translatable'  => false,
            )
        );

        $resolver->setNormalizers(
            array(
                'field_type' => function (Options $options, $value) {
                    if (!empty($options['translatable'])) {
                        $value = 'translatable_entity';
                    }

                    return $value;
                }
            )
        );
    }

//    /**
//     * {@inheritDoc}
//     */
//    public function finishView(FormView $view, FormInterface $form, array $options)
//    {
//        parent::finishView($view, $form, $options);
//        if (isset($options['populate_default'])) {
//            $view->vars['populate_default'] = $options['populate_default'];
//            $view->vars['default_value']    = $options['default_value'];
//        }
//        if (!empty($options['null_value'])) {
//            $view->vars['null_value'] = $options['null_value'];
//        }
//
//        if (!empty($options['class'])) {
//            $view->vars['class'] = $options['class'];
//        }
//    }
}

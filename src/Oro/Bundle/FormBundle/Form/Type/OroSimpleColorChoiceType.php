<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\FormBundle\Utils\FormUtils;

class OroSimpleColorChoiceType extends AbstractSimpleColorPickerType
{
    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);

        $resolver
            ->setDefaults(
                [
                    'choices' => []
                ]
            )
            ->setNormalizers(
                [
                    'choices' => function (Options $options, $choices) {
                        return $options['color_schema'] === 'custom'
                            ? $choices
                            : $this->getColors($options['color_schema']);
                    }
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        FormUtils::appendClass($view, 'no-input-widget');
        $view->vars['translatable']      = $options['translatable'];
        $view->vars['allow_empty_color'] = $options['allow_empty_color'];
        $view->vars['empty_color']       = $options['empty_color'];
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
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
        return 'oro_simple_color_choice';
    }
}

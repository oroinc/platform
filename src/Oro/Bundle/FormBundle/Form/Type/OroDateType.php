<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OroDateType extends AbstractType
{
    const NAME = 'oro_date';

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if (!empty($options['placeholder'])) {
            $view->vars['attr']['placeholder'] = $options['placeholder'];
        }

        // jquery date/datetime pickers support only year ranges
        if (!empty($options['years'])) {
            $view->vars['years'] = sprintf('%d:%d', min($options['years']), max($options['years']));
        }

        $view->vars['minDate'] = $options['minDate'];
        $view->vars['maxDate'] = $options['maxDate'];
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'model_timezone' => 'UTC',
                'view_timezone'  => 'UTC',
                'format'         => 'yyyy-MM-dd', // ISO format
                'widget'         => 'single_text',
                'placeholder'    => 'oro.form.click_here_to_select',
                'years'          => [],
                'minDate'        => null,
                'maxDate'        => null,
            ]
        );

        // remove buggy 'placeholder' normalizer. The placeholder must be a string if 'widget' === 'single_text'
        $resolver->setNormalizer(
            'placeholder',
            function (Options $options, $placeholder) {
                return $placeholder;
            }
        );

        $resolver->setAllowedTypes('minDate', ['string', 'null']);
        $resolver->setAllowedTypes('maxDate', ['string', 'null']);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return DateType::class;
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
}

<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class OroTimeIntervalType
 *
 * @deprecated Use OroDurationType instead
 */
class OroTimeIntervalType extends AbstractType
{
    const NAME = 'oro_time_interval';

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
        return 'time';
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'widget'         => 'single_text',
                'with_seconds'   => true,
                'model_timezone' => 'UTC',
                'view_timezone'  => 'UTC',
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if ('single_text' === $options['widget']) {
            $view->vars['type'] = 'text';
        }
    }
}

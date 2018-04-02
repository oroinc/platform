<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @deprecated since 1.10. Use {@see Oro\Bundle\FormBundle\Form\Type\OroDurationType} instead
 */
class OroTimeIntervalType extends AbstractType
{
    const NAME = 'oro_time_interval';

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
    public function getParent()
    {
        return TimeType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'widget'         => 'single_text',
            'with_seconds'   => true,
            'model_timezone' => 'UTC',
            'view_timezone'  => 'UTC',
        ]);
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

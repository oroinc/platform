<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\DataTransformer\DurationToStringTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Time interval (duration) field type
 * It can store the user input into a separate field 'input_property_path' from the parent form data
 * and later populate the form with it's value.
 * If the form data is an entity, specify 'input_property_path' option with the entity field name.
 * If the form data is an array, specify the array key.
 * Default is 'null' (disabled)
 */
class OroTimeIntervalType extends AbstractType
{
    const NAME = 'oro_time_interval';


    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new DurationToStringTransformer());
        // We need to validate user input before it is passed to the model transformer
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'preSubmit']);
    }

    public function preSubmit(FormEvent $event)
    {
        if ($this->isValidDuration($event->getData())) {
            return;
        }
        $event->getForm()->addError(new FormError('Value is not in a valid duration format'));
    }

    /**
     * @param string $duration
     *
     * @return bool
     */
    protected function isValidDuration($duration)
    {
        $regexJIRAFormat =
            '/^' .
            '(?:(?:(\d+(?:\.\d)?)?)h(?:[\s]*|$))?' .
            '(?:(?:(\d+(?:\.\d)?)?)m(?:[\s]*|$))?' .
            '(?:(?:(\d+(?:\.\d)?)?)s)?' .
            '$/i';
        $regexColumnFormat =
            '/^' .
            '((\d{1,3}:)?\d{1,3}:)?\d{1,3}' .
            '$/i';

        return preg_match($regexJIRAFormat, $duration) || preg_match($regexColumnFormat, $duration);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'tooltip' => 'oro.form.oro_time_interval.tooltip',
                'type' => 'text',
            ]
        );
    }

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
        return 'text';
    }
}

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
 * Duration field type
 * Accepts numeric values (seconds), JIRA style (##h ##m ##s) and column style (##:##:##) duration encodings.
 *
 * @see DurationToStringTransformer for more details
 */
class OroDurationType extends AbstractType
{
    const NAME = 'oro_duration';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new DurationToStringTransformer());
        // We need to validate user input before it is passed to the model transformer
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'preSubmit']);
    }

    /**
     * Event listener callback to handle validation before data is submitted.
     *
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        if ($this->isValidDuration($event->getData())) {
            return;
        }
        $event->getForm()->addError(new FormError('Value is not in a valid duration format'));
    }

    /**
     * Checks whether string is in a recognizable duration format
     *
     * @param string $value
     * @return bool
     */
    protected function isValidDuration($value)
    {
        $regexJIRAFormat =
            '/^' .
            '(?:(?:(\d+(?:\.\d)?)?)h(?:[\s]*|$))?' .
            '(?:(?:(\d+(?:\.\d)?)?)m(?:[\s]*|$))?' .
            '(?:(?:(\d+(?:\.\d)?)?)s?)?' .
            '$/i';
        $regexColumnFormat =
            '/^' .
            '((\d{1,3}:)?\d{1,3}:)?\d{1,3}' .
            '$/i';

        return preg_match($regexJIRAFormat, $value) || preg_match($regexColumnFormat, $value);
    }

    /** {@inheritdoc} */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'tooltip' => 'oro.form.oro_duration.tooltip',
                'type' => 'text',
            ]
        );
    }

    /** {@inheritdoc} */
    public function getName()
    {
        return self::NAME;
    }

    /** {@inheritdoc} */
    public function getParent()
    {
        return 'text';
    }
}

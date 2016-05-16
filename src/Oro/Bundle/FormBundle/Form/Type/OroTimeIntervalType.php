<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\DataTransformer\DurationToStringTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;

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

        if ($options['input_property_path']) {
            $this->addFieldListeners($builder, $options);
        }
    }


    private function addFieldListeners(FormBuilderInterface $builder, array $options)
    {
        // populate the field with the connected field data
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($options) {
                $data = $event->getData();
                if (is_object($event->getForm()->getParent())) {
                    $data = $event->getForm()->getParent()->getData();
                }

                $value = '';
                // data is array
                if (is_array($data) && array_key_exists($options['input_property_path'], $data)) {
                    $value = $data[$options['input_property_path']];
                }

                // data is entity
                $accessor = PropertyAccess::createPropertyAccessor();
                if ($accessor->isReadable($data, $options['input_property_path'])) {
                    $value = $accessor->getValue($data, $options['input_property_path']);
                }

                $event->setData((string) $value);
            }
        );

        // store the submitted input data into the connected field
        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($options) {
                if (!is_object($event->getForm()->getParent())) {
                    return;
                }
                $value = $event->getData();
                $data = $event->getForm()->getParent()->getData();
                // data is array
                if (is_array($data)) {
                    $data[$options['input_property_path']] = $value;
                }

                // data is entity
                $accessor = PropertyAccess::createPropertyAccessor();
                if ($accessor->isWritable($data, $options['input_property_path'])) {
                    $accessor->setValue($data, $options['input_property_path'], $value);
                }
            }
        );
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
                'input_property_path' => null, // where to store user input
            ]
        );

        $resolver->setAllowedTypes('input_property_path', ['string', 'null']);
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

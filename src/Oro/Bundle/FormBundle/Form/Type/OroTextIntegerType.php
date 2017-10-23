<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * Jquery validation does not work with number=type fields, so in order to enable it and still parse the value as an int
 * this form type has been added
 */
class OroTextIntegerType extends IntegerType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_text_integer';
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'text_integer';
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(
            [
                'constraints' => [
                    new Regex(
                        [
                            'pattern' => '/^[\d+]*$/',
                            'message' => 'This value should contain only numbers.',
                        ]
                    ),
                ],
            ]
        );
    }
}

<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * FormType mapping for array on TextType
 *
 * @see https://github.com/symfony/symfony/issues/29809
 */
class OroUnstructuredTextType extends TextType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'multiple' => true,
        ]);
    }
}

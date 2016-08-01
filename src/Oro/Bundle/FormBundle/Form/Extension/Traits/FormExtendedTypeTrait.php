<?php

namespace Oro\Bundle\FormBundle\Form\Extension\Traits;

trait FormExtendedTypeTrait
{
    /**
     * Provide backward compatibility between Symfony versions < 2.8 and 2.8+
     *
     * @return string
     */
    public function getExtendedType()
    {
        return method_exists('Symfony\Component\Form\AbstractType', 'getBlockPrefix')
            ? 'Symfony\Component\Form\Extension\Core\Type\FormType'
            : 'form';
    }
}

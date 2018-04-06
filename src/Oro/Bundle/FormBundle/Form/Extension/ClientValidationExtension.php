<?php

namespace Oro\Bundle\FormBundle\Form\Extension;

use Oro\Bundle\FormBundle\Form\Extension\Traits\FormExtendedTypeTrait;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClientValidationExtension extends AbstractTypeExtension
{
    use FormExtendedTypeTrait;
    
    /**
     * Add the client_validation option
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined('client_validation');
    }

    /**
     * Pass the client validation flag to the view
     *
     * @param FormView      $view
     * @param FormInterface $form
     * @param array         $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        // set an "client_validation" variable that will be available when rendering this field
        $view->vars['client_validation'] = isset($options['client_validation'])
            ? (bool) $options['client_validation']
            : true;
    }
}

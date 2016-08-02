<?php

namespace Oro\Bundle\FormBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\FormBundle\Form\Extension\Traits\FormExtendedTypeTrait;

class ClientValidationExtension extends AbstractTypeExtension
{
    use FormExtendedTypeTrait;
    
    /**
     * Add the client_validation option
     *
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setOptional(array('client_validation'));
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

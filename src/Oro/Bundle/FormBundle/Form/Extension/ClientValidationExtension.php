<?php

namespace Oro\Bundle\FormBundle\Form\Extension;

use Oro\Bundle\FormBundle\Form\Extension\Traits\FormExtendedTypeTrait;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Manages the client_validation option for form fields and passes it to the view.
 */
class ClientValidationExtension extends AbstractTypeExtension
{
    use FormExtendedTypeTrait;

    /**
     * Add the client_validation option
     */
    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined('client_validation');
    }

    /**
     * Pass the client validation flag to the view
     */
    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        // set an "client_validation" variable that will be available when rendering this field
        $view->vars['client_validation'] = isset($options['client_validation'])
            ? (bool) $options['client_validation']
            : true;
    }
}

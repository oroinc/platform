<?php

namespace Oro\Bundle\OrganizationBundle\Form\Type;

use Oro\Bundle\SoapBundle\Form\EventListener\PatchSubscriber;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Provides a form type for business unit entities in API contexts.
 *
 * Extends the standard {@see BusinessUnitType} with API-specific functionality, including support
 * for PATCH requests through the {@see PatchSubscriber} event listener. This form type is configured
 * to disable CSRF protection and is intended for use in REST API endpoints.
 */
class BusinessUnitApiType extends BusinessUnitType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder->addEventSubscriber(new PatchSubscriber());
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(
            array(
                'data_class'      => 'Oro\Bundle\OrganizationBundle\Entity\BusinessUnit',
                'csrf_token_id'   => 'business_unit',
                'csrf_protection' => false
            )
        );
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'business_unit';
    }
}

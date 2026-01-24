<?php

namespace Oro\Bundle\TagBundle\Form\Type;

use Oro\Bundle\SoapBundle\Form\EventListener\PatchSubscriber;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * API form type for tag entities with PATCH request support.
 *
 * This form type extends the standard {@see TagType} to provide API-specific functionality, including support
 * for partial updates via PATCH requests. It disables CSRF protection for API contexts and configures
 * the form to work with the Tag entity class.
 */
class TagApiType extends TagType
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
        $resolver->setDefaults(
            array(
                'data_class'      => 'Oro\Bundle\TagBundle\Entity\Tag',
                'csrf_token_id'   => 'tag',
                'csrf_protection' => false
            )
        );
    }
}

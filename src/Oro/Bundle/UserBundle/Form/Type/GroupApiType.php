<?php

namespace Oro\Bundle\UserBundle\Form\Type;

use Oro\Bundle\SoapBundle\Form\EventListener\PatchSubscriber;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The fort type for Group entity REST API.
 */
class GroupApiType extends GroupType
{
    public function addEntityFields(FormBuilderInterface $builder)
    {
        $builder->addEventSubscriber(new PatchSubscriber());
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(['csrf_protection' => false]);
    }

    #[\Override]
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'group';
    }
}

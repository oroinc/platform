<?php

namespace Oro\Bundle\UserBundle\Form\Type;

use Oro\Bundle\UserBundle\Form\EventListener\PatchSubscriber;
use Oro\Bundle\UserBundle\Form\EventListener\UserApiSubscriber;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class UserApiType extends UserType
{
    /**
     * {@inheritdoc}
     */
    public function addEntityFields(FormBuilderInterface $builder)
    {
        parent::addEntityFields($builder);

        $builder
            ->addEventSubscriber(new UserApiSubscriber($builder->getFormFactory()))
            ->addEventSubscriber(new PatchSubscriber());
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);

        $resolver->setDefaults(
            array(
                'csrf_protection' => false,
                'validation_groups'    => array('ProfileAPI', 'Default'),
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'user';
    }
}

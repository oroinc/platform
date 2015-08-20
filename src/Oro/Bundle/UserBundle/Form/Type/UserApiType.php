<?php

namespace Oro\Bundle\UserBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\UserBundle\Form\EventListener\UserApiSubscriber;
use Oro\Bundle\SoapBundle\Form\EventListener\PatchSubscriber;

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

        $resolver->setDefaults([
            'csrf_protection'   => false,
            'validation_groups' => function ($form) {
                if ($form instanceof FormInterface) {
                    $user = $form->getData();
                } elseif ($form instanceof FormView) {
                    $user = $form->vars['value'];
                } else {
                    $user = null;
                }

                return $user && $user->getId()
                    ? ['ProfileAPI', 'Default']
                    : ['Registration', 'ProfileAPI', 'Default'];
            },
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'user';
    }

    /**
     * Add Invite user fields
     *
     * @param FormBuilderInterface $builder
     */
    protected function addInviteUserField(FormBuilderInterface $builder)
    {
    }
}

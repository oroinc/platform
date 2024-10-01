<?php

namespace Oro\Bundle\UserBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\SoapBundle\Form\EventListener\PatchSubscriber;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Role API form type.
 */
class RoleApiType extends AclRoleType
{
    public function __construct()
    {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'label',
            TextType::class,
            array(
                'required' => true,
                'label' => 'oro.user.role.label.label'
            )
        );

        $builder->add(
            'appendUsers',
            EntityIdentifierType::class,
            array(
                'class'    => User::class,
                'required' => false,
                'mapped'   => false,
                'multiple' => true,
            )
        );

        $builder->add(
            'removeUsers',
            EntityIdentifierType::class,
            array(
                'class'    => User::class,
                'required' => false,
                'mapped'   => false,
                'multiple' => true,
            )
        );
    }

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
        return 'role';
    }
}

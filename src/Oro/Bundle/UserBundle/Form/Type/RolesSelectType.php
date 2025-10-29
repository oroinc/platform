<?php

namespace Oro\Bundle\UserBundle\Form\Type;

use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Form\Provider\RolesChoicesForUserProviderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form type to select available roles.
 */
class RolesSelectType extends AbstractType
{
    public function __construct(
        private readonly RolesChoicesForUserProviderInterface $choicesForUserProvider
    ) {
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'class' => Role::class,
            'choice_label' => function (Role $role) {
                return $this->choicesForUserProvider->getChoiceLabel($role);
            },
            'choices' => $this->choicesForUserProvider->getRoles(),
            'multiple' => true,
            'expanded' => true,
            'translatable_options' => false,
        ]);
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_role_select';
    }

    #[\Override]
    public function getParent(): ?string
    {
        return EntityType::class;
    }
}

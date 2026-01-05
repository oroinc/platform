<?php

namespace Oro\Bundle\OrganizationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OwnershipType extends AbstractType
{
    public const NAME = 'oro_type_choice_ownership_type';

    public const OWNER_TYPE_NONE = 'NONE';
    public const OWNER_TYPE_USER = 'USER';
    public const OWNER_TYPE_BUSINESS_UNIT = 'BUSINESS_UNIT';
    public const OWNER_TYPE_ORGANIZATION = 'ORGANIZATION';

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'choices' => array_flip($this->getOwnershipsArray()),
            ]
        );
    }

    /**
     * @return array
     */
    public function getOwnershipsArray()
    {
        return  array(
            self::OWNER_TYPE_NONE => 'None',
            self::OWNER_TYPE_USER => 'User',
            self::OWNER_TYPE_BUSINESS_UNIT => 'Business Unit',
            self::OWNER_TYPE_ORGANIZATION => 'Organization',
        );
    }

    #[\Override]
    public function getParent(): ?string
    {
        return ChoiceType::class;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}

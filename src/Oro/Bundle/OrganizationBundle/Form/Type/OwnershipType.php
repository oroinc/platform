<?php

namespace Oro\Bundle\OrganizationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Provides a form type for selecting ownership types in entity configuration.
 *
 * This form type renders as a choice field with four ownership options: NONE, USER, BUSINESS_UNIT,
 * and ORGANIZATION. It is used in entity configuration forms to define the ownership model for
 * custom entities, determining which entity type (`User`, `BusinessUnit`, or `Organization`) can own
 * instances of the entity.
 */
class OwnershipType extends AbstractType
{
    const NAME = 'oro_type_choice_ownership_type';

    const OWNER_TYPE_NONE = 'NONE';
    const OWNER_TYPE_USER = 'USER';
    const OWNER_TYPE_BUSINESS_UNIT = 'BUSINESS_UNIT';
    const OWNER_TYPE_ORGANIZATION = 'ORGANIZATION';

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

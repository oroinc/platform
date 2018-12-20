<?php

namespace Oro\Bundle\OrganizationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class OwnershipType extends AbstractType
{
    const NAME = 'oro_type_choice_ownership_type';

    const OWNER_TYPE_NONE = 'NONE';
    const OWNER_TYPE_USER = 'USER';
    const OWNER_TYPE_BUSINESS_UNIT = 'BUSINESS_UNIT';
    const OWNER_TYPE_ORGANIZATION = 'ORGANIZATION';

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}

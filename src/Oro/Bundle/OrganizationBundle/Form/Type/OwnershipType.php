<?php

namespace Oro\Bundle\OrganizationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;

class OwnershipType extends AbstractType
{
    const NAME = 'oro_type_choice_ownership_type';

    const OWNER_TYPE_NONE = 'NONE';
    const OWNER_TYPE_USER = 'USER';
    const OWNER_TYPE_BUSINESS_UNIT = 'BUSINESS_UNIT';
    const OWNER_TYPE_ORGANIZATION = 'ORGANIZATION';

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'choices' => $this->getOwnershipsArray()
            )
        );
    }

    public function getOwnershipsArray()
    {
        return  array(
            self::OWNER_TYPE_NONE => 'None',
            self::OWNER_TYPE_USER => 'User',
            self::OWNER_TYPE_BUSINESS_UNIT => 'Business Unit',
            self::OWNER_TYPE_ORGANIZATION => 'Organization',
        );
    }

    public function getParent()
    {
        return 'choice';
    }

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

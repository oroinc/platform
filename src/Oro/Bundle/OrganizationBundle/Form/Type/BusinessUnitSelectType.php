<?php

namespace Oro\Bundle\OrganizationBundle\Form\Type;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Oro\Bundle\FormBundle\Form\Type\Select2EntityType;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BusinessUnitSelectType extends AbstractType
{
    /** @var Registry */
    private $doctrine;

    /** @var TokenAccessorInterface */
    private $tokenAccessor;

    /**
     * @param Registry               $doctrine
     * @param TokenAccessorInterface $tokenAccessor
     */
    public function __construct(Registry $doctrine, TokenAccessorInterface $tokenAccessor)
    {
        $this->doctrine = $doctrine;
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'placeholder' => 'oro.business_unit.form.choose_business_user',
                'empty_data'  => null,
                'class'       => 'Oro\Bundle\OrganizationBundle\Entity\BusinessUnit',
            ]
        );

        $queryBuilderNormalizer = function () {
            $qb = $this->doctrine->getRepository('OroOrganizationBundle:BusinessUnit')
                ->createQueryBuilder('bu');

            $qb->select('bu')
                ->where('bu.organization = :organization');

            $qb->setParameter('organization', $this->tokenAccessor->getOrganization());

            return $qb;
        };

        $resolver->setNormalizer('query_builder', $queryBuilderNormalizer);
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
        return 'oro_business_unit_select';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return Select2EntityType::class;
    }
}

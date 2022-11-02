<?php

namespace Oro\Bundle\OrganizationBundle\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Form\Type\Select2EntityType;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form type to select a business unit.
 */
class BusinessUnitSelectType extends AbstractType
{
    private ManagerRegistry $doctrine;
    private TokenAccessorInterface $tokenAccessor;

    public function __construct(ManagerRegistry $doctrine, TokenAccessorInterface $tokenAccessor)
    {
        $this->doctrine = $doctrine;
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'placeholder' => 'oro.business_unit.form.choose_business_user',
            'empty_data'  => null,
            'class'       => BusinessUnit::class,
        ]);

        $resolver->setNormalizer('query_builder', function () {
            return $this->doctrine->getRepository(BusinessUnit::class)
                ->createQueryBuilder('bu')
                ->select('bu')
                ->where('bu.organization = :organization')
                ->setParameter('organization', $this->tokenAccessor->getOrganization());
        });
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

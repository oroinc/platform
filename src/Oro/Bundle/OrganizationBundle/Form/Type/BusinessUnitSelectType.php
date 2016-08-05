<?php

namespace Oro\Bundle\OrganizationBundle\Form\Type;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class BusinessUnitSelectType extends AbstractType
{
    /** @var Registry */
    private $doctrine;
    /** @var SecurityFacade */
    private $securityFacade;

    /**
     * BusinessUnitSelectType constructor.
     *
     * @param Registry       $doctrine
     * @param SecurityFacade $securityFacade
     */
    public function __construct(Registry $doctrine, SecurityFacade $securityFacade)
    {
        $this->doctrine = $doctrine;
        $this->securityFacade = $securityFacade;
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

        $securityFacade = $this->securityFacade;
        $doctrine = $this->doctrine;

        $queryBuilderNormalizer = function () use ($securityFacade, $doctrine) {
            $qb = $doctrine->getRepository('OroOrganizationBundle:BusinessUnit')
                ->createQueryBuilder('bu');

            $qb->select('bu')
                ->where('bu.organization = :organization');

            $qb->setParameter('organization', $securityFacade->getOrganization());

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
        return 'genemu_jqueryselect2_entity';
    }
}

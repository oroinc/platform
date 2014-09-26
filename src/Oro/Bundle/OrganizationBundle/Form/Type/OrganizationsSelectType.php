<?php

namespace Oro\Bundle\OrganizationBundle\Form\Type;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\PersistentCollection;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager;

class OrganizationsSelectType extends AbstractType
{
    /** @var  EntityManager */
    protected $em;

    /** @var BusinessUnitManager */
    protected $buManager;

    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param EntityManager       $em
     * @param BusinessUnitManager $buManager
     * @param SecurityFacade      $securityFacade
     */
    public function __construct(
        EntityManager $em,
        BusinessUnitManager $buManager,
        SecurityFacade $securityFacade
    ) {
        $this->em              = $em;
        $this->buManager       = $buManager;
        $this->securityFacade  = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'label'                   => 'oro.user.business_units.label',
                'configs'                 => [
                    'is_translated_option' => false,
                    'is_safe'              => false,
                ],
                'organization_tree_ids'   => [],
                'selected_organizations'  => [],
                'selected_business_units' => [],
                'inherit_data'            => true,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_organizations_select';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'default_organization',
            'hidden',
            [
                'mapped' => false,
                'data'   => $this->securityFacade->getOrganizationId(),
            ]
        );
        $builder->add(
            'organizations',
            'entity',
            [
                'class'    => 'OroOrganizationBundle:Organization',
                'property' => 'name',
                'multiple' => true,
                'expanded' => true,
                'choices'  => $this->getOrganizationOptions(),
            ]
        );
        $builder->add(
            'businessUnits',
            'oro_business_unit_tree',
            [
                'multiple' => true,
                'expanded' => true,
                'required' => false,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $buTree = $this->buManager->getBusinessUnitRepo()->getOrganizationBusinessUnitsTree(
            $this->securityFacade->getOrganizationId()
        );

        $view->vars['organization_tree_ids'] = $buTree;

        /** @var PersistentCollection $businessUnitData */
        $businessUnitData = $view->vars['data']->getBusinessUnits();
        if ($businessUnitData) {
            $businessUnitData = $businessUnitData->map(
                function ($item) {
                    return $item->getId();
                }
            )->getValues();
        }

        $view->vars['selected_organizations']  = [$this->securityFacade->getOrganizationId()];
        $view->vars['selected_business_units'] = $businessUnitData;
    }

    /**
     * Prepare choice options for a select
     *
     * @return array
     */
    protected function getOrganizationOptions()
    {
        return $this->em->getRepository('OroOrganizationBundle:Organization')->getEnabled();
    }
}

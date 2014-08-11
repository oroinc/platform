<?php

namespace Oro\Bundle\OrganizationBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager;
use Oro\Bundle\OrganizationBundle\Entity\Manager\OrganizationManager;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class BusinessUnitByOrganizationType extends AbstractType
{
    const FORM_NAME = 'oro_business_unit_by_organization';

    /** @var BusinessUnitManager */
    protected $businessUnitManager;

    /** @var OrganizationManager */
    protected $organizationManager;

    /**
     * @param BusinessUnitManager $businessUnitManager
     * @param OrganizationManager $organizationManager
     */
    public function __construct(
        BusinessUnitManager $businessUnitManager,
        OrganizationManager $organizationManager
    ) {
        $this->businessUnitManager = $businessUnitManager;
        $this->organizationManager = $organizationManager;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'organization',
            'entity',
            [
                'class'       => 'OroOrganizationBundle:Organization',
                'property'    => 'name',
                'label'       => 'oro.organization.businessunit.organization.label',
                'empty_value' => 'oro.organization.form.choose_organization',
                'required'    => true,
                'choices'     => $this->organizationManager->getOrganizationRepo()->getEnabled(),
                'attr'        => [
                    'class' => 'oro_bu_by_org_select_org'
                ],
            ]
        );

        $builder->add(
            'businessUnit',
            'oro_business_unit_tree', //'entity',
            [
                //'class'         => 'OroOrganizationBundle:BusinessUnit',
                //'property'      => 'name',
                'label'         => 'oro.organization.businessunit.parent.label',
                'empty_value'   => 'oro.business_unit.form.none_business_user',
                'required'      => false,
                'property_path' => 'owner',
                'choices'       => [],
                'attr'          => [
                    'class' => 'oro_bu_by_org_select_bu'
                ],
                'inherit_data'  => false,
            ]
        );

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
        //$builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSetData']);
    }

    public function onPreSetData(FormEvent $event)
    {
        $form = $event->getForm()->getParent();
        $data = $form->getData();
        if ($data) {
            $selectedOrganizationId = null;

            /** @var Organization|null $selectedOrganization */
            $selectedOrganization = null;
            if ($data->getOrganization()) {
                $selectedOrganization   = $data->getOrganization();
                $selectedOrganizationId = $selectedOrganization->getId();
            }

            /** @var BusinessUnit|null $selectedBusinessUnit */
            $selectedBusinessUnit = $data->getOwner()
                ? $data->getOwner()
                : null;

            if ($selectedOrganization) {
                $event->getForm()->remove('businessUnit');
                $event->getForm()->add(
                    'businessUnit',
                    'oro_business_unit_tree', //'entity',
                    [
                        //'class'         => 'OroOrganizationBundle:BusinessUnit',
                        //'property'      => 'name',
                        'label'         => 'oro.organization.businessunit.parent.label',
                        'empty_value'   => 'oro.business_unit.form.none_business_user',
                        'required'      => false,
                        'property_path' => 'owner',
                        /*'query_builder' => function (EntityRepository $er) use ($selectedOrganization) {
                            return $er->createQueryBuilder('b')
                                ->where('b.organization = '. $selectedOrganization->getId());
                        },*/
                        'choices'       => $selectedOrganizationId
                            ? $this->getBusinessUnitChoices(
                                $this->businessUnitManager->getBusinessUnitsTree(null, $selectedOrganizationId)
                            )
                            : [],
                        //'choices' => [],
                        'attr'          => [
                            'class' => 'oro_bu_by_org_select_bu'
                        ],
                        'data'          => $selectedBusinessUnit
                    ]
                );
            }
        }
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $selectedOrganization = null;
        if ($form->getParent()->getData()) {
            $selectedOrganization = $form->getParent()->getData()->getOrganization() ? : null;
            //$form->get('organization')->setData($selectedOrganization);
        }
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if ($form->get('organization')->getData()) {
            $view->children['organization']->vars['disabled'] = true;
        }
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class'         => 'Oro\Bundle\OrganizationBundle\Entity\BusinessUnit',
                'ownership_disabled' => true,
                'inherit_data'       => true,
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::FORM_NAME;
    }

    /**
     * Prepare choice options for a hierarchical select
     *
     * @param array $options
     * @param int   $level
     * @return array
     */
    protected function getBusinessUnitChoices($options, $level = 0)
    {
        $choices = [];
        $blanks  = str_repeat('&nbsp;&nbsp;&nbsp;', $level);
        foreach ($options as $option) {
            $choices += array($option['id'] => $blanks . $option['name']);
            if (isset($option['children'])) {
                $choices += $this->getBusinessUnitChoices($option['children'], $level + 1);
            }
        }

        return $choices;
    }
}

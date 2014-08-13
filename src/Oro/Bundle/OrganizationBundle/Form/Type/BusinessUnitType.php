<?php

namespace Oro\Bundle\OrganizationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager;
use Oro\Bundle\OrganizationBundle\Entity\Manager\OrganizationManager;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class BusinessUnitType extends AbstractType
{
    const FORM_NAME = 'oro_business_unit';

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
        $builder
            ->add(
                'name',
                'text',
                array(
                    'label'    => 'oro.organization.businessunit.name.label',
                    'required' => true,
                )
            )
            ->add(
                'organization',
                'entity',
                [
                    'class'       => 'OroOrganizationBundle:Organization',
                    'property'    => 'name',
                    'label'       => 'oro.organization.businessunit.organization.label',
                    'empty_value' => 'oro.organization.form.choose_organization',
                    'required'    => false,
                    'choices'     => $this->organizationManager->getOrganizationRepo()->getEnabled(),
                    'attr'        => [
                        'class' => 'oro_bu_by_org_select_org'
                    ],
                    'disabled'    => true
                ]
            )
            ->add(
                'businessUnit',
                'oro_business_unit_tree',
                [
                    'label'         => 'oro.organization.businessunit.parent.label',
                    'empty_value'   => 'oro.business_unit.form.none_business_user',
                    'property_path' => 'owner',
                    'required'      => false,
                    'choices'       => [],
                    'attr'          => [
                        'class' => 'oro_bu_by_org_select_bu'
                    ],
                ]
            )
            ->add(
                'phone',
                'text',
                array(
                    'label'    => 'oro.organization.businessunit.phone.label',
                    'required' => false,
                )
            )
            ->add(
                'website',
                'text',
                array(
                    'label'    => 'oro.organization.businessunit.website.label',
                    'required' => false,
                )
            )
            ->add(
                'email',
                'text',
                array(
                    'label'    => 'oro.organization.businessunit.email.label',
                    'required' => false,
                )
            )
            ->add(
                'fax',
                'text',
                array(
                    'label'    => 'oro.organization.businessunit.fax.label',
                    'required' => false,
                )
            )
            ->add(
                'appendUsers',
                'oro_entity_identifier',
                array(
                    'class'    => 'OroUserBundle:User',
                    'required' => false,
                    'mapped'   => false,
                    'multiple' => true,
                )
            )
            ->add(
                'removeUsers',
                'oro_entity_identifier',
                array(
                    'class'    => 'OroUserBundle:User',
                    'required' => false,
                    'mapped'   => false,
                    'multiple' => true,
                )
            );

        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'onPreSetData']);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit']);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if (!$form->get('organization')->getData()) {
            $view->children['organization']->vars['disabled'] = false;
            $view->children['organization']->vars['required'] = true;
        } else {
            $selectedOrganizationId = $form->get('organization')->getData()->getId();
            $businessUnitTreeIds    = $this->businessUnitManager->getBusinessUnitRepo()
                ->getOrganizationBusinessUnitsTree($selectedOrganizationId);

            $view->vars['business_unit_tree_ids'] = $businessUnitTreeIds;
            $view->vars['selected_organization']  = $selectedOrganizationId;
            $view->vars['selected_business_unit'] = $form->get('businessUnit')->getData()
                ? $form->get('businessUnit')->getData()->getId()
                : null;
        }
    }

    /**
     * @param FormEvent $event
     */
    public function onPreSubmit(FormEvent $event)
    {
        $selectedOrganizationId = null;
        $selectedBusinessUnitId = null;

        $data = $event->getData();
        if (isset($data['organization']) && $data['organization'] !== null) {
            $selectedOrganizationId = $data['organization'];
            $this->organizationManager->getOrganizationRepo()->find($selectedOrganizationId);
        }

        if (isset($data['businessUnit']) && $data['businessUnit'] !== null) {
            $selectedBusinessUnitId = $data['businessUnit'];
        }

        if ($selectedOrganizationId && $selectedBusinessUnitId) {
            $event->getForm()->remove('businessUnit');
            $event->getForm()->add(
                'businessUnit',
                'oro_business_unit_tree',
                [
                    'label'         => 'oro.organization.businessunit.parent.label',
                    'empty_value'   => 'oro.business_unit.form.none_business_user',
                    'required'      => false,
                    'property_path' => 'owner',
                    'choices'       => $selectedOrganizationId
                            ? $this->getBusinessUnitChoices(
                                $this->businessUnitManager->getBusinessUnitsTree(null, $selectedOrganizationId)
                            )
                            : [],
                    'attr'          => [
                        'class' => 'oro_bu_by_org_select_bu'
                    ],
                    'data'          => $this->businessUnitManager->getUserRepo()->find($selectedBusinessUnitId),
                ]
            );
        }
    }

    /**
     * @param FormEvent $event
     */
    public function onPreSetData(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $form->getData();
        if ($data) {
            $selectedOrganizationId = null;

            /** @var Organization|null $selectedOrganization */
            $selectedOrganization = null;
            if ($data->getOrganization()) {
                $selectedOrganization   = $data->getOrganization();
                $selectedOrganizationId = $selectedOrganization->getId();
            }

            if ($selectedOrganization) {
                $event->getForm()->remove('businessUnit');
                $event->getForm()->add(
                    'businessUnit',
                    'oro_business_unit_tree',
                    [
                        'label'         => 'oro.organization.businessunit.parent.label',
                        'empty_value'   => 'oro.business_unit.form.none_business_user',
                        'required'      => false,
                        'property_path' => 'owner',
                        'choices'       => $selectedOrganizationId
                                ? $this->getBusinessUnitChoices(
                                    $this->businessUnitManager->getBusinessUnitsTree(null, $selectedOrganizationId)
                                )
                                : [],
                        'attr'          => [
                            'class' => 'oro_bu_by_org_select_bu'
                        ],
                    ]
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class'              => 'Oro\Bundle\OrganizationBundle\Entity\BusinessUnit',
                'ownership_disabled'      => true,
                'business_unit_tree_ids'  => [],
                'selected_organizations'  => [],
                'selected_business_units' => [],
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

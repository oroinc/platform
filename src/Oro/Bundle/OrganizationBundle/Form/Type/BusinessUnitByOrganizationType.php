<?php

namespace Oro\Bundle\OrganizationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager;

class BusinessUnitByOrganizationType extends AbstractType
{
    const FORM_NAME = 'oro_business_unit_by_organization';

    /** @var BusinessUnitManager */
    protected $businessUnitManager;

    /**
     * @param BusinessUnitManager $businessUnitManager
     */
    public function __construct(BusinessUnitManager $businessUnitManager)
    {
        $this->businessUnitManager = $businessUnitManager;
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
                'label'       => 'oro.organization.businessunit.organization.label',
                'empty_value' => 'oro.organization.form.choose_organization',
                'class'       => 'OroOrganizationBundle:Organization',
                'property'    => 'name',
                'required'    => true,
                'attr'        => [
                    'class' => 'oro_bu_by_org_select_org'
                ]
            ]
        );
        $builder->add(
            'owner',
            'oro_business_unit_tree',
            [
                'label'       => 'oro.organization.businessunit.parent.label',
                'empty_value' => 'oro.business_unit.form.none_business_user',
                //'choices'     => $this->getBusinessUnitChoices($this->businessUnitManager->getBusinessUnitsTree()),
                'choices'     => [],
                'required'    => false,
                'attr'        => [
                    'class' => 'oro_bu_by_org_select_bu'
                ],
            ]
        );
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if ($form->getData()->getId()) {
            $view->children['organization']->vars['attr']['disabled'] = true;
        }

        if ($view->children['organization']->vars['value']) {
//            $a = 1;
//            $view->children['owner']->vars['choices'] =
//                $this->getBusinessUnitChoices($this->businessUnitManager->getBusinessUnitsTree());
        }
    }


    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(['inherit_data' => true]);
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

<?php

namespace Oro\Bundle\OrganizationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class BusinessUnitByOrganizationType extends AbstractType
{
    const FORM_NAME = 'oro_business_unit_by_organization';

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
            'choice',
            [
                'label'       => 'oro.organization.businessunit.parent.label',
                'empty_value' => 'oro.business_unit.form.choose_business_user',
                'choices'     => $this->getBusinessUnitChoices($options),
                'required'    => false,
                //'disabled'    => true,
                'attr'        => [
                    'class' => 'oro_bu_by_org_select_bu'
                ]
            ]
        );
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class'         => 'Oro\Bundle\OrganizationBundle\Entity\BusinessUnit',
                'inherit_data'       => true,
                'ownership_disabled' => true
            ]
        );
    }

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
        $blanks  = str_repeat("&nbsp;&nbsp;&nbsp;", $level);

        foreach ($options as $option) {
            $option = $option;
//            $choices += array($option['id'] => $blanks . $option['name']);
//            if (isset($option['children'])) {
//                $choices += $this->getTreeOptions($option['children'], $level + 1);
//            }
        }

        return $choices;
    }
}

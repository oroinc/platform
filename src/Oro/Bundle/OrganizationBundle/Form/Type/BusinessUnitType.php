<?php

namespace Oro\Bundle\OrganizationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;

class BusinessUnitType extends AbstractType
{
    const FORM_NAME = 'oro_business_unit';

    /** @var BusinessUnitManager */
    protected $businessUnitManager;

    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param BusinessUnitManager $businessUnitManager
     * @param SecurityFacade      $securityFacade
     */
    public function __construct(
        BusinessUnitManager $businessUnitManager,
        SecurityFacade $securityFacade
    ) {
        $this->businessUnitManager = $businessUnitManager;
        $this->securityFacade      = $securityFacade;
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
                [
                    'label'    => 'oro.organization.businessunit.name.label',
                    'required' => true,
                ]
            )
            ->add(
                'parentBusinessUnit',
                'oro_type_business_unit_select_autocomplete',
                [
                    'required' => false,
                    'label' => 'oro.organization.businessunit.parent.label',
                    'autocomplete_alias' => 'business_units_owner_search_handler',
                    'empty_value' => 'oro.business_unit.form.none_business_user',
                    'configs' => [
                        'multiple' => false,
                        'component'   => 'tree-autocomplete',
                        'width'       => '400px',
                        'placeholder' => 'oro.dashboard.form.choose_business_unit',
                        'allowClear'  => true
                    ]
                ]
            )
            ->add(
                'phone',
                'text',
                [
                    'label'    => 'oro.organization.businessunit.phone.label',
                    'required' => false,
                ]
            )
            ->add(
                'website',
                'text',
                [
                    'label'    => 'oro.organization.businessunit.website.label',
                    'required' => false,
                ]
            )
            ->add(
                'email',
                'text',
                [
                    'label'    => 'oro.organization.businessunit.email.label',
                    'required' => false,
                ]
            )
            ->add(
                'fax',
                'text',
                [
                    'label'    => 'oro.organization.businessunit.fax.label',
                    'required' => false,
                ]
            )
            ->add(
                'appendUsers',
                'oro_entity_identifier',
                [
                    'class'    => 'OroUserBundle:User',
                    'required' => false,
                    'mapped'   => false,
                    'multiple' => true,
                ]
            )
            ->add(
                'removeUsers',
                'oro_entity_identifier',
                [
                    'class'    => 'OroUserBundle:User',
                    'required' => false,
                    'mapped'   => false,
                    'multiple' => true,
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class'              => 'Oro\Bundle\OrganizationBundle\Entity\BusinessUnit',
                'ownership_disabled'      => true,
            ]
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
     * Return current organization id
     *
     * @return int|null
     */
    protected function getOrganizationId()
    {
        return $this->securityFacade->getOrganizationId();
    }
}

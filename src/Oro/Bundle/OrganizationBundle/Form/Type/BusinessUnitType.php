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
                array(
                    'label'    => 'oro.organization.businessunit.name.label',
                    'required' => true,
                )
            )
            ->add(
                'businessUnit',
                'oro_business_unit_tree_select',
                [
                    'label' => 'oro.organization.businessunit.parent.label',
                    'empty_value' => 'oro.business_unit.form.none_business_user',
                    'property_path' => 'owner',
                    'required' => false,
                    'choices' => $this->getBusinessUnitChoices(
                        $this->businessUnitManager->getBusinessUnitsTree(
                            null,
                            $this->securityFacade->getOrganizationId()
                        )
                    ),
                    'business_unit_ids' => $this->businessUnitManager->getBusinessUnitIds(
                        null,
                        $this->securityFacade->getOrganizationId()
                    )
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

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
    }

    /**
     * @param FormEvent $event
     */
    public function onPreSetData(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();
        if ($data) {
            if ($data->getId()) {
                $form->remove('businessUnit');
                $form->add(
                    'businessUnit',
                    'oro_business_unit_tree_select',
                    [
                        'label' => 'oro.organization.businessunit.parent.label',
                        'empty_value' => 'oro.business_unit.form.none_business_user',
                        'property_path' => 'owner',
                        'required' => false,
                        'choices' => $this->getBusinessUnitChoices(
                            $this->businessUnitManager->getBusinessUnitsTree(
                                null,
                                $this->securityFacade->getOrganizationId()
                            )
                        ),
                        'forbidden_business_unit_ids' => $this->businessUnitManager->getChildBusinessUnitIds(
                            $data->getId(),
                            $this->securityFacade->getOrganizationId()
                        )
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

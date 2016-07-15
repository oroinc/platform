<?php

namespace Oro\Bundle\OrganizationBundle\Form\Type;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\PersistentCollection;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

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
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) {
                $data = is_string($event->getData()) ? json_decode($event->getData(), true) : $event->getData();

                $organizations = json_decode(reset($data['organizations']));
                $data['organizations'] = $organizations->organizations;

                $event->setData($data);
            }
        );

        $builder->add(
            'organizations',
            'entity',
            [
                'class'    => 'OroOrganizationBundle:Organization',
                'property' => 'name',
                'multiple' => true
            ]
        );
        $builder->add(
            'businessUnits',
            'oro_type_business_unit_select_autocomplete',
            [
                'required' => false,
                'label' => 'oro.user.form.access_settings.label',
                'autocomplete_alias' => 'business_units_tree_search_handler',
                'configs'            => [
                    'multiple'    => true,
                    'width'       => '400px',
                    'component'   => 'bu-tree-autocomplete',
                    'placeholder' => 'oro.dashboard.form.choose_business_unit',
                    'allowClear'  => true,
                ]
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

        $view->vars['default_organization'] = $this->securityFacade->getOrganizationId();
        $view->vars['selected_organizations']  = [$this->securityFacade->getOrganizationId()];
        $view->vars['selected_business_units'] = $businessUnitData;
        $view->vars['accordion_enabled'] = $this->buManager->getTreeNodesCount($buTree) > 1000;
    }

    /**
     * @return User
     */
    protected function getLoggedInUser()
    {
        return $this->securityFacade->getLoggedUser();
    }
}

<?php

namespace Oro\Bundle\OrganizationBundle\Form\Type;

use Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Select organization form type.
 */
class OrganizationsSelectType extends AbstractType
{
    private BusinessUnitManager $buManager;
    private TokenAccessorInterface $tokenAccessor;

    public function __construct(
        BusinessUnitManager $buManager,
        TokenAccessorInterface $tokenAccessor
    ) {
        $this->buManager = $buManager;
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'label'                   => 'oro.user.business_units.label',
                'configs'                 => [
                    'is_safe'              => false,
                ],
                'organization_tree_ids'   => [],
                'selected_organizations'  => [],
                'inherit_data'            => true,
            ]
        );
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
                $data = $event->getData();

                if (!empty($data['organizations'])) {
                    $organizations = json_decode(reset($data['organizations']), true);

                    if (!$organizations['organizations'] && !empty($data['businessUnits'])) {
                        $data['organizations'] = [$this->tokenAccessor->getOrganizationId()];
                    } else {
                        $data['organizations'] = $organizations['organizations'];
                    }
                }

                $event->setData($data);
            }
        );

        $this->addOrganizationsField($builder);
        $builder->add(
            'businessUnits',
            BusinessUnitSelectAutocomplete::class,
            [
                'required' => false,
                'label' => 'oro.user.form.business_units.label',
                'autocomplete_alias' => 'business_units_tree_search_handler',
                'label_attr' => [
                    'class' => 'business-units-label'
                ],
                'configs'            => [
                    'multiple'    => true,
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
        $view->vars['attr']['class'] = 'control-group-choice';
        $view->vars['organization_tree_ids'] = $this->buManager->getBusinessUnitRepo()
            ->getOrganizationBusinessUnitsTree($this->tokenAccessor->getOrganizationId());
        $view->vars['default_organization'] = $this->tokenAccessor->getOrganizationId();
        $view->vars['selected_organizations'] = [$this->tokenAccessor->getOrganizationId()];
    }

    private function addOrganizationsField(FormBuilderInterface $builder): void
    {
        $builder->add(
            'organizations',
            EntityType::class,
            [
                'class' => Organization::class,
                'choice_label' => 'name',
                'multiple' => true
            ]
        );
    }
}

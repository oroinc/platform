<?php

namespace Oro\Bundle\OrganizationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager;
use Oro\Bundle\OrganizationBundle\Form\Transformer\BusinessUnitTreeTransformer;

/**
 * Class WidgetBusinessUnitSelect
 * @package Oro\Bundle\DashboardBundle\Form\Type
 */
class BusinessUnitAutocomplete extends AbstractType
{
    const NAME = 'oro_business_unit_select_autocomplete';

    /** @var BusinessUnitManager */
    protected $businessUnitManager;

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    public function __construct(BusinessUnitManager $businessUnitManager)
    {
        $this->businessUnitManager = $businessUnitManager;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $transformer = new BusinessUnitTreeTransformer($this->businessUnitManager);
        $builder->resetModelTransformers();
        $builder->resetViewTransformers();
        $builder->addModelTransformer($transformer);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'configs'            => [
                    'multiple'    => false,
                    'width'       => '400px',
                    'component'   => 'tree-autocomplete',
                    'placeholder' => 'oro.dashboard.form.choose_business_unit',
                    'allowClear'  => false,
                ]
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'oro_jqueryselect2_hidden';
    }
}

<?php
namespace Oro\Bundle\OrganizationBundle\Form\Type;

use Oro\Bundle\OrganizationBundle\Form\Transformer\BusinessUnitTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class BusinessUnitTreeSelectType extends AbstractType
{
    /**
     * @var BusinessUnitTransformer
     */
    protected $transformer;

    public function __construct(BusinessUnitTransformer $transformer)
    {
        $this->transformer = $transformer;
    }

    public function getParent()
    {
        return ChoiceType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder->addModelTransformer($this->transformer);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_business_unit_tree_select';
    }
}

<?php
namespace Oro\Bundle\OrganizationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager;
use Oro\Bundle\OrganizationBundle\Form\Transformer\BusinessUnitTreeTransformer;

class BusinessUnitTreeType extends AbstractType
{
    /**
     * @var BusinessUnitManager
     */
    protected $businessUnitManager;

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
        $builder->addModelTransformer($transformer);
    }

    public function getParent()
    {
        return 'choice';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_business_unit_tree';
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'choices' => $this->getTreeOptions($this->businessUnitManager->getBusinessUnitsTree())
            )
        );
    }

    /**
     * Prepare choice options for a hierarchical select
     *
     * @param $options
     * @param int $level
     * @return array
     */
    protected function getTreeOptions($options, $level = 0)
    {
        $choices = array();
        $blanks = str_repeat("&nbsp;&nbsp;&nbsp;", $level);
        foreach ($options as $option) {
            $choices += array($option['id'] => $blanks . $option['name']);
            if (isset($option['children'])) {
                $choices += $this->getTreeOptions($option['children'], $level + 1);
            }
        }

        return $choices;
    }
}

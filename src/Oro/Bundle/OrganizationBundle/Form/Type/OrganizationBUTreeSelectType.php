<?php
namespace Oro\Bundle\OrganizationBundle\Form\Type;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager;
use Oro\Bundle\OrganizationBundle\Form\Transformer\BusinessUnitTreeTransformer;

class OrganizationBUTreeSelectType extends AbstractType
{
    /** @var  EntityManager */
    protected $em;

    /** @var BusinessUnitManager */
    protected $businessUnitManager;

    public function __construct(EntityManager $em, BusinessUnitManager $businessUnitManager)
    {
        $this->em = $em;
        $this->businessUnitManager = $businessUnitManager;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $choices = $this->getTreeOptions($this->businessUnitManager->getBusinessUnitsTree());

        $resolver->setDefaults([
            'expanded' => false,
            'configs' => [
                'is_translated_option' => false,
                'is_safe'              => false,
            ],
            'choices' => $choices,
            'business_unit_ids' => [],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_organization_bu_tree_select';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['business_unit_ids'] = $options['business_unit_ids'];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $businessUnitTransformer = new BusinessUnitTreeTransformer($this->businessUnitManager);

        $builder->addModelTransformer($businessUnitTransformer);
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
        $choices = [];
        $blanks = str_repeat("&nbsp;&nbsp;&nbsp;", $level);
        foreach ($options as $option) {
            $choices['BU_' . $option['id']] = [
                $option['id'] => $blanks . $option['name']
            ];
            if (isset($option['children'])) {
                $choices[] = $this->getTreeOptions($option['children'], $level + 1);
            }
        }

        return $choices;
    }
}

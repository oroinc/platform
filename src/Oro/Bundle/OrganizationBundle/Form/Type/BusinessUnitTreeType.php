<?php
namespace Oro\Bundle\OrganizationBundle\Form\Type;

use Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

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

    public function getParent()
    {
        return 'entity';
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
                'class' => 'OroOrganizationBundle:BusinessUnit',
                'choices' => $this->businessUnitManager->getBusinessUnitTreeWithLevels()
            )
        );
    }
}

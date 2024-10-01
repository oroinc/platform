<?php

namespace Oro\Bundle\OrganizationBundle\Form\Type;

use Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager;
use Oro\Bundle\OrganizationBundle\Form\Transformer\BusinessUnitTreeTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for business unit tree
 */
class BusinessUnitTreeType extends AbstractType
{
    /** @var BusinessUnitManager */
    protected $businessUnitManager;

    public function __construct(BusinessUnitManager $businessUnitManager)
    {
        $this->businessUnitManager = $businessUnitManager;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $transformer = new BusinessUnitTreeTransformer($this->businessUnitManager);
        $builder->addModelTransformer($transformer);
    }

    #[\Override]
    public function getParent(): ?string
    {
        return ChoiceType::class;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_business_unit_tree';
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices' => function (Options $options) {
                return $this->businessUnitManager->getTreeOptions(
                    $this->businessUnitManager->getBusinessUnitsTree()
                );
            }
        ]);
    }
}

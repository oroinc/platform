<?php

namespace Oro\Bundle\OrganizationBundle\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntitiesToIdsTransformer;
use Oro\Bundle\FormBundle\Form\Type\OroJquerySelect2HiddenType;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager;
use Oro\Bundle\OrganizationBundle\Form\Transformer\BusinessUnitTreeTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Select business unit with autocomplete form type.
 */
class BusinessUnitSelectAutocomplete extends AbstractType
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private BusinessUnitManager $businessUnitManager
    ) {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (isset($options['configs']['multiple']) &&  $options['configs']['multiple'] === true) {
            $builder->addModelTransformer(new EntitiesToIdsTransformer($this->doctrine, BusinessUnit::class));
        } else {
            $builder->resetModelTransformers();
            $builder->addModelTransformer(new BusinessUnitTreeTransformer($this->businessUnitManager));
        }
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'configs'            => [
                    'multiple'    => true,
                    'component'   => 'tree-autocomplete',
                    'placeholder' => 'oro.dashboard.form.choose_business_unit',
                    'allowClear'  => true,
                    'entity_id'   => null
                ]
            ]
        );
    }

    #[\Override]
    public function getParent(): ?string
    {
        return OroJquerySelect2HiddenType::class;
    }
}

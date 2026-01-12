<?php

namespace Oro\Bundle\DataAuditBundle\Form\Type;

use Oro\Bundle\FilterBundle\Form\Type\Filter\FilterType as BaseFilterType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Form type for configuring data audit filters in segments and grids.
 *
 * This form type handles the configuration of audit-specific filters, allowing users to filter
 * entities based on their audit history. It provides two main filter components: `auditFilter`
 * for audit-specific criteria (field changes, timestamps) and `filter` for standard filtering
 * options. The form validates the structure of filter data to ensure proper query construction
 * when filtering by audit history.
 */
class FilterType extends AbstractType
{
    public const NAME = 'oro_type_audit_filter';

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('auditFilter', null, [
                'constraints' => [
                    new Assert\Collection([
                        'fields' => [
                            'data' => [],
                            'type' => [],
                            'columnName' => [],
                        ],
                    ]),
                ],
            ])
            ->add('filter', null, [
                'constraints' => [
                    new Assert\Collection([
                        'fields' => [
                            'data' => [],
                            'type' => [],
                            'filter' => [],
                        ],
                        'allowMissingFields' => true,
                    ]),
                ],
            ])
        ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
        ]);
    }

    #[\Override]
    public function getParent(): ?string
    {
        return BaseFilterType::class;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return static::NAME;
    }
}

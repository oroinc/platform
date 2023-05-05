<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type\Stub;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The stub form type that can be used instead of {@see \Oro\Bundle\EntityExtendBundle\Form\Type\EnumSelectType}
 * in unit tests for form types contains an enum field to simplify such tests.
 */
class EnumSelectTypeStub extends EntityTypeStub
{
    /**
     * @param AbstractEnumValue[] $choices
     */
    public function __construct(array $choices)
    {
        parent::__construct($this->getEnumChoices($choices));
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'enum_code' => null,
            'configs' => [],
            'placeholder' => null,
            'disabled_values' => [],
            'excluded_values' => [],
            'compound' => false
        ]);
    }

    private function getEnumChoices(array $choices): array
    {
        $enumChoices = [];
        foreach ($choices as $choice) {
            $enumChoices[$choice->getId()] = $choice;
        }

        return $enumChoices;
    }
}

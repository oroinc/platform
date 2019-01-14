<?php

namespace Oro\Component\Testing\Unit\Form\Type\Stub;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The stub form type that can be used instead of {@see \Oro\Bundle\EntityExtendBundle\Form\Type\EnumSelectType}
 * in unit tests for form types contains an enum field to simplify such tests.
 */
class EnumSelectType extends EntityType
{
    const NAME = 'oro_enum_select';

    /**
     * @param array $choices
     */
    public function __construct(array $choices)
    {
        $choices = $this->getEnumChoices($choices);
        parent::__construct($choices, static::NAME);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults(
            [
                'enum_code' => null,
                'configs' => [],
                'placeholder' => null,
                'disabled_values' => [],
                'excluded_values' => [],
                'compound' => false
            ]
        );
    }

    /**
     * @param AbstractEnumValue[] $choices
     * @return array
     */
    protected function getEnumChoices($choices)
    {
        $enumChoices = [];
        foreach ($choices as $choice) {
            $enumChoices[$choice->getId()] = $choice;
        }
        return $enumChoices;
    }
}

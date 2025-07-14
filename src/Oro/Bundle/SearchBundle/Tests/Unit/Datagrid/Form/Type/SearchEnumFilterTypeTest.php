<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Datagrid\Form\Type;

use Oro\Bundle\FilterBundle\Form\Type\Filter\EnumFilterType;
use Oro\Bundle\SearchBundle\Datagrid\Form\Type\SearchEnumFilterType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchEnumFilterTypeTest extends TestCase
{
    private SearchEnumFilterType $type;

    #[\Override]
    protected function setUp(): void
    {
        $this->type = new SearchEnumFilterType();
    }

    public function testConfigureOptions(): void
    {
        $resolver = new OptionsResolver();
        $resolver->setDefined(['field_options', 'operator_choices']);

        $this->type->configureOptions($resolver);

        $resolvedOptions = $resolver->resolve(
            [
                'field_options' => [
                    'choices' => [
                        'value1' => 0,
                        'value2' => 1,
                    ]
                ],
                'operator_choices' => null
            ]
        );

        $this->assertEquals(
            [
                'field_options' => [
                    'choices' => [
                        'value1' => 0,
                        'value2' => 1
                    ]
                ],
                'operator_choices' => [
                    'value1' => 0,
                    'value2' => 1
                ]
            ],
            $resolvedOptions
        );
    }

    public function testGetBlockPrefix(): void
    {
        $this->assertEquals('oro_search_type_enum_filter', $this->type->getBlockPrefix());
    }

    public function testGetParent(): void
    {
        $this->assertEquals(EnumFilterType::class, $this->type->getParent());
    }
}

<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Datagrid\Form\Type;

use Oro\Bundle\FilterBundle\Form\Type\Filter\EnumFilterType;
use Oro\Bundle\SearchBundle\Datagrid\Form\Type\SearchEnumFilterType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchEnumFilterTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var SearchEnumFilterType */
    protected $type;

    protected function setUp()
    {
        $this->type = new SearchEnumFilterType();
    }

    public function testConfigureOptions()
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

    public function testGetBlockPrefix()
    {
        $this->assertEquals(SearchEnumFilterType::NAME, $this->type->getBlockPrefix());
    }

    public function testGetParent()
    {
        $this->assertEquals(EnumFilterType::class, $this->type->getParent());
    }
}

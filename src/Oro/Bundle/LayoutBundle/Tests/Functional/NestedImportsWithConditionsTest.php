<?php

namespace Oro\Bundle\LayoutBundle\Tests\Functional;

class NestedImportsWithConditionsTest extends AbstractLayoutBuilderTest
{
    public function testLayoutTree()
    {
        $expectedTree = ['head' => [],
            'body' => [
                'wrapper' => [
                    'layout_1_wrapper' => [
                        'first_1_wrapper' => [
                            'first_1_second_1_wrapper' => []
                        ]
                    ],
                    'layout_2_wrapper' => [
                        'first_2_wrapper' => []
                    ],
                    'layout_3_wrapper' => []
                ]
            ]
        ];

        $layout = $this->getLayout('nested_imports_with_conditions');

        $tree = $this->getBlockViewTree($layout->getView());

        $this->assertEquals($expectedTree, $tree);
    }
}

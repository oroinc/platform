<?php

namespace Oro\Bundle\LayoutBundle\Tests\Functional;

class NestedImportsTest extends AbstractLayoutBuilderTest
{
    public function testLayoutTree()
    {
        $expectedTree = [
            'head' => [],
            'body' => [
                'wrapper' => [
                    'first_wrapper' => [
                        'first_second_wrapper' => [
                            'first_second_third_wrapper' => []
                        ]
                    ]
                ]
            ]
        ];

        $layout = $this->getLayout('nested_imports');

        $tree = $this->getBlockViewTree($layout->getView());

        $this->assertEquals($expectedTree, $tree);
    }
}

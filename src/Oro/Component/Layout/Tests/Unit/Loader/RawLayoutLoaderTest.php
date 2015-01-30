<?php

namespace Oro\Component\Layout\Tests\Unit\Loader;

use Oro\Component\Layout\Tests\Unit\DeferredLayoutManipulatorTestCase;
use Oro\Component\Layout\Loader\RawLayoutLoader;

class RawLayoutLoaderTest extends DeferredLayoutManipulatorTestCase
{
    /** @var RawLayoutLoader */
    protected $rawLayoutLoader;

    protected function setUp()
    {
        parent::setUp();

        $this->rawLayoutLoader = new RawLayoutLoader($this->layoutManipulator);
    }

    public function testSimpleLayout()
    {
        $config = [
            'oro_layout' => [
                'items' => [
                    'root' => [
                        'type' => 'root'
                    ]
                ],
                'tree' => [
                    'root' => []
                ]
            ]
        ];

        $this->rawLayoutLoader->load($config);
        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars' => ['id' => 'root'],
            ],
            $view
        );
    }

    public function testLayoutWithChildrenAndOptions()
    {
        $config = [
            'oro_layout' => [
                'items' => [
                    'root' => ['type' => 'root'],
                    'header' => ['type' => 'header'],
                    'logo' => ['type' => 'logo', 'options' => ['title' => 'test']],

                ],
                'tree' => [
                    'root' => [
                        'children' => [
                            'header' => [
                                'children' => [
                                    'logo' => []
                                ]
                            ],
                        ]
                    ]
                ]
            ]
        ];

        $this->rawLayoutLoader->load($config);
        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars' => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars'     => ['id' => 'header'],
                        'children' => [
                            [ // logo
                                'vars' => ['id' => 'logo', 'title' => 'test']
                            ]
                        ]
                    ]
                ]
            ],
            $view
        );
    }

    public function testLayoutWithChildrenAndAliases()
    {
        $config = [
            'oro_layout' => [
                'items' => [
                    'root' => ['type' => 'root'],
                    'header' => ['type' => 'header']
                ],
                'tree' => [
                    'root' => [
                        'children' => [
                            'header' => []
                        ]
                    ]
                ],
                'aliases' => [
                    'myroot' => 'root'
                ]
            ]
        ];

        $this->rawLayoutLoader->load($config);
        $view = $this->getLayoutView();

        $this->assertBlockView(
            [ // root
                'vars' => ['id' => 'root'],
                'children' => [
                    [ // header
                        'vars' => ['id' => 'header'],
                    ]
                ]
            ],
            $view
        );
    }
}

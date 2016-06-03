<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Extension\Generator;

use Oro\Bundle\LayoutBundle\Layout\Extension\Generator\ThemesRelativePathGeneratorExtension;
use Oro\Component\Layout\Loader\Generator\GeneratorData;
use Oro\Component\Layout\Loader\Visitor\VisitorCollection;

class ThemesRelativePathGeneratorExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ThemesRelativePathGeneratorExtension
     */
    protected $extension;

    public function setUp()
    {
        $this->extension = new ThemesRelativePathGeneratorExtension();
    }

    /**
     * @dataProvider prepareDataProvider
     *
     * @param array $source
     * @param string|null $fileName
     * @param array $expectedSource
     */
    public function testPrepare(array $source, $fileName, array $expectedSource)
    {
        $data = new GeneratorData($source, $fileName);

        $visitorCollection = new VisitorCollection();
        $this->extension->prepare($data, $visitorCollection);
        $this->assertEmpty($visitorCollection);
        $this->assertEquals($fileName, $data->getFilename());
        $this->assertEquals($expectedSource, $data->getSource());
    }

    /**
     * @return array
     */
    public function prepareDataProvider()
    {
        return [
            'empty_actions' => [
                'source' => [],
                'filename' => '/path',
                'expectedSource' => [],
            ],
            'no_set_theme' => [
                'source' => ['actions' => [['@add' => ['id' => 'block_id']]]],
                'filename' => '/path',
                'expectedSource' => ['actions' => [['@add' => ['id' => 'block_id']]]],
            ],
            'twig_resource' => [
                'source' => [
                    'actions' => [['@setBlockTheme' => ['themes' => 'OroBundle:layouts:default/page.html.twig']]]
                ],
                'filename' => '/path',
                'expectedSource' => [
                    'actions' => [['@setBlockTheme' => ['themes' => 'OroBundle:layouts:default/page.html.twig']]]
                ],
            ],
            'full_path' => [
                'source' => ['actions' => [['@setBlockTheme' => ['themes' => '/full/path/page.html.twig']]]],
                'filename' => '/path',
                'expectedSource' => ['actions' => [['@setBlockTheme' => ['themes' => '/full/path/page.html.twig']]]],
            ],
            'filename_null' => [
                'source' => ['actions' => [['@setBlockTheme' => ['themes' => ['themes.htm.twig']]]]],
                'filename' => null,
                'expectedSource' => ['actions' => [['@setBlockTheme' => ['themes' => ['themes.htm.twig']]]]],
            ],
            'themes_null' => [
                'source' => ['actions' => [['@setBlockTheme' => ['themes' => null]]]],
                'filename' => __DIR__.'/data/layout.yml',
                'expectedSource' => [
                    'actions' => [['@setBlockTheme' => ['themes' => __DIR__.'/data/layout.html.twig']]]
                ],
            ],
            'themes_null_in_array' => [
                'source' => ['actions' => [['@setBlockTheme' => ['themes' => [null]]]]],
                'filename' => __DIR__.'/data/layout.yml',
                'expectedSource' => [
                    'actions' => [['@setBlockTheme' => ['themes' => __DIR__.'/data/layout.html.twig']]]
                ],
            ],
            'themes_null_and_relative' => [
                'source' => ['actions' => [['@setBlockTheme' => ['themes' => [null, 'sub/update.html.twig']]]]],
                'filename' => __DIR__.'/data/layout.yml',
                'expectedSource' => [
                    'actions' => [
                        [
                            '@setBlockTheme' => [
                                'themes' => [
                                    __DIR__.'/data/layout.html.twig',
                                    __DIR__.'/data/sub/update.html.twig',
                                ]
                            ]
                        ]
                    ]
                ],
            ],
            'themes_relative_with_cd' => [
                'source' => ['actions' => [['@setBlockTheme' => ['themes' => '../data/sub/update.html.twig']]]],
                'filename' => __DIR__.'/data/layout.yml',
                'expectedSource' => [
                    'actions' => [['@setBlockTheme' => ['themes' => __DIR__.'/data/sub/update.html.twig']]]
                ],
            ],
            'themes_form' => [
                'source' => ['actions' => [['@setFormTheme' => ['themes' => '../data/sub/update.html.twig']]]],
                'filename' => __DIR__.'/data/layout.yml',
                'expectedSource' => [
                    'actions' => [['@setFormTheme' => ['themes' => __DIR__.'/data/sub/update.html.twig']]]
                ],
            ],
        ];
    }
}

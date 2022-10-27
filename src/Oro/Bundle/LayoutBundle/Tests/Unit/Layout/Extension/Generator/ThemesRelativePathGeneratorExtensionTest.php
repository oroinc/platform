<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Extension\Generator;

use Oro\Bundle\LayoutBundle\Layout\Extension\Generator\ThemesRelativePathGeneratorExtension;
use Oro\Component\Layout\Loader\Generator\GeneratorData;
use Oro\Component\Layout\Loader\Visitor\VisitorCollection;

class ThemesRelativePathGeneratorExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var ThemesRelativePathGeneratorExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->extension = new ThemesRelativePathGeneratorExtension(DIRECTORY_SEPARATOR);
    }

    /**
     * @dataProvider prepareDataProvider
     */
    public function testPrepare(array $source, ?string $fileName, array $expectedSource): void
    {
        $data = new GeneratorData($source, $fileName);

        $visitorCollection = new VisitorCollection();
        $this->extension->prepare($data, $visitorCollection);
        self::assertEmpty($visitorCollection);
        self::assertEquals($fileName, $data->getFilename());
        self::assertEquals($expectedSource, $data->getSource());
    }

    public function prepareDataProvider(): array
    {
        $namespacedThemeName = '@OroLayout/Tests/Unit/Layout/Extension/Generator/data/sub/update.html.twig';

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
            'empty_theme' => [
                'source' => [
                    'actions' => [['@setBlockTheme' => ['themes' => '']]]
                ],
                'filename' => '/path',
                'expectedSource' => [
                    'actions' => [['@setBlockTheme' => ['themes' => '']]]
                ],
            ],
            'resource' => [
                'source' => [
                    'actions' => [['@setBlockTheme' => ['themes' => '@OroBundle/layouts/default/page.html.twig']]]
                ],
                'filename' => '/path',
                'expectedSource' => [
                    'actions' => [['@setBlockTheme' => ['themes' => '@OroBundle/layouts/default/page.html.twig']]]
                ],
            ],
            'twig_resource' => [
                'source' => [
                    'actions' => [['@setBlockTheme' => ['themes' => '@OroTest/layouts/default/page.html.twig']]]
                ],
                'filename' => '/path',
                'expectedSource' => [
                    'actions' => [['@setBlockTheme' => ['themes' => '@OroTest/layouts/default/page.html.twig']]]
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
                    'actions' => [['@setBlockTheme' => ['themes' => []]]]
                ],
            ],
            'themes_null_in_array' => [
                'source' => ['actions' => [['@setBlockTheme' => ['themes' => [null]]]]],
                'filename' => __DIR__.'/data/layout.yml',
                'expectedSource' => [
                    'actions' => [['@setBlockTheme' => ['themes' => null]]]
                ],
            ],
            'themes_null_and_relative' => [
                'source' => ['actions' => [['@setBlockTheme' => ['themes' => [null, 'sub/update.html.twig']]]]],
                'filename' => __DIR__.'/data/layout.yml',
                'expectedSource' => ['actions' => [['@setBlockTheme' => ['themes' => [null, $namespacedThemeName]]]]],
            ],
            'themes_relative_with_cd' => [
                'source' => ['actions' => [['@setBlockTheme' => ['themes' => '../data/sub/update.html.twig']]]],
                'filename' => __DIR__.'/data/layout.yml',
                'expectedSource' => [
                    'actions' => [['@setBlockTheme' => ['themes' => $namespacedThemeName]]]
                ],
            ],
            'themes_form' => [
                'source' => ['actions' => [['@setFormTheme' => ['themes' => '../data/sub/update.html.twig']]]],
                'filename' => __DIR__.'/data/layout.yml',
                'expectedSource' => [
                    'actions' => [['@setFormTheme' => ['themes' => $namespacedThemeName]]]
                ],
            ],
            'themes_relative_in_resources_views' => [
                'source' => ['actions' => [['@setBlockTheme' => ['themes' => 'Resources/views/sub/update.html.twig']]]],
                'filename' => __DIR__.'/data/layout.yml',
                'expectedSource' => [
                    'actions' => [['@setBlockTheme' => ['themes' => $namespacedThemeName]]]
                ],
            ],
        ];
    }

    public function testPrepareReturnAbsolutePath(): void
    {
        $fileName = __DIR__.'/data/layout.yml';
        $source = [
            'actions' => [
                [
                    '@setBlockTheme' => ['themes' => [null, 'sub/templates/update.html.twig']]
                ]
            ]
        ];
        $expectedSource = [
            'actions' => [
                [
                    '@setBlockTheme' => ['themes' => [null, '/data/sub/update.html.twig']]
                ]
            ]
        ];

        $extension = new ThemesRelativePathGeneratorExtension(__DIR__);

        $data = new GeneratorData($source, $fileName);
        $visitorCollection = new VisitorCollection();
        $extension->prepare($data, $visitorCollection);

        self::assertEmpty($visitorCollection);
        self::assertEquals($fileName, $data->getFilename());
        self::assertEquals($expectedSource, $data->getSource());
    }
}

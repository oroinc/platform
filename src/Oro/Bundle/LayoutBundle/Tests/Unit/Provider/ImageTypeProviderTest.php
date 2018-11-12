<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Provider;

use Oro\Bundle\LayoutBundle\Model\ThemeImageType;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;

class ImageTypeProviderTest extends \PHPUnit\Framework\TestCase
{
    const DIMENSION_ORIGINAL = 'product_original';
    const DIMENSION_LARGE = 'product_large';
    const DIMENSION_SMALL = 'product_small';
    const DIMENSION_CUSTOM = 'product_custom';

    /**
     * @var ImageTypeProvider
     */
    protected $provider;

    /**
     * @var ThemeManager
     */
    protected $themeManager;

    public function setUp()
    {
        $this->themeManager = $this->prophesize('Oro\Component\Layout\Extension\Theme\Model\ThemeManager');
        $this->provider = new ImageTypeProvider($this->themeManager->reveal());
    }

    public function testGetImageTypes()
    {
        $theme1MainDimensions = [self::DIMENSION_ORIGINAL, self::DIMENSION_LARGE];
        $theme1ListingDimensions = [self::DIMENSION_ORIGINAL];
        $theme2ListingDimensions = [self::DIMENSION_ORIGINAL, self::DIMENSION_CUSTOM];

        $this->prepareThemeManager($theme1MainDimensions, $theme1ListingDimensions, $theme2ListingDimensions);

        $imageTypes = $this->provider->getImageTypes();

        $this->assertCount(2, $imageTypes);
        $this->assertValidImageType($imageTypes['main'], 'main', 'Main', 1, $theme1MainDimensions);
        $this->assertValidImageType($imageTypes['listing'], 'listing', 'Listing', 5, $theme2ListingDimensions);
        $this->assertTrue($imageTypes['main']->getDimensions()['product_large']->getOption('option1'));
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    public function testInvalidConfig()
    {
        $this->themeManager->getAllThemes()->willReturn([
            $this->prepareTheme('theme1', [
                'main' => ['Main', 1, ['non_existing_dimension']],
            ], [
                self::DIMENSION_SMALL => [50, 50]
            ])
        ]);

        $this->provider->getImageTypes();
    }

    public function testGetImageDimensions()
    {
        $theme1MainDimensions = [self::DIMENSION_ORIGINAL, self::DIMENSION_LARGE];
        $theme1ListingDimensions = [self::DIMENSION_ORIGINAL];
        $theme2ListingDimensions = [self::DIMENSION_ORIGINAL, self::DIMENSION_CUSTOM];

        $this->prepareThemeManager($theme1MainDimensions, $theme1ListingDimensions, $theme2ListingDimensions);

        $dimensions = $this->provider->getImageDimensions();

        $dimensionLarge = $dimensions[self::DIMENSION_LARGE];
        $this->assertCount(4, $dimensions);
        $this->assertEquals(400, $dimensionLarge->getWidth());
        $this->assertEquals(400, $dimensionLarge->getHeight());
        $this->assertTrue($dimensionLarge->getOption('option1'));
    }

    /**
     * @param string $name
     * @param array $imageTypes
     * @param array $dimensions
     * @return Theme
     */
    private function prepareTheme($name, array $imageTypes, array $dimensions)
    {
        $config = [
            'images' => [
                'types' => [],
                'dimensions' => []
            ]
        ];

        foreach ($imageTypes as $key => $imageType) {
            list($label, $maxNumber, $dimensionNames) = $imageType;
            $config['images']['types'][$key] = [
                'label' => $label,
                'dimensions' => $dimensionNames,
                'max_number' => $maxNumber
            ];
        }

        foreach ($dimensions as $name => $dimension) {
            list($width, $height) = $dimension;
            $config['images']['dimensions'][$name] = ['width' => $width, 'height' => $height];

            if (isset($dimension[2])) {
                $config['images']['dimensions'][$name]['options'] = $dimension[2];
            }
        }

        $theme = new Theme($name);
        $theme->setConfig($config);

        return $theme;
    }

    /**
     * @param ThemeImageType $imageType
     * @param string $name
     * @param string $label
     * @param int $maxNumber
     * @param array $dimensions
     */
    private function assertValidImageType(ThemeImageType $imageType, $name, $label, $maxNumber, array $dimensions)
    {
        $this->assertEquals($name, $imageType->getName());
        $this->assertEquals($label, $imageType->getLabel());
        $this->assertEquals($maxNumber, $imageType->getMaxNumber());
        $this->assertCount(count($dimensions), $imageType->getDimensions());
        $this->assertEquals($dimensions, array_keys($imageType->getDimensions()));
    }

    /**
     * @param array $theme1MainDimensions
     * @param array $theme1ListingDimensions
     * @param array $theme2ListingDimensions
     */
    private function prepareThemeManager(
        array $theme1MainDimensions,
        array $theme1ListingDimensions,
        array $theme2ListingDimensions
    ) {
        $this->themeManager->getAllThemes()->willReturn([
            $this->prepareTheme(
                'theme1',
                [
                    'main' => ['Main', 1, $theme1MainDimensions],
                    'listing' => ['Listing', 3, $theme1ListingDimensions],
                ],
                [
                    self::DIMENSION_ORIGINAL => [null, null],
                    self::DIMENSION_LARGE => [400, 400, ['option1' => true]],
                    self::DIMENSION_SMALL => [50, 50],
                ]
            ),
            $this->prepareTheme(
                'theme2',
                [
                    'listing' => ['Listing', 5, $theme2ListingDimensions],
                ],
                [
                    self::DIMENSION_CUSTOM => [88, 88],
                ]
            ),
            $this->prepareTheme('theme3', [], [])
        ]);
    }
}

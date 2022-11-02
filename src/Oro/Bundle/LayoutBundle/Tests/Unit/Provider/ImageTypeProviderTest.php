<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Provider;

use Oro\Bundle\LayoutBundle\Model\ThemeImageType;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class ImageTypeProviderTest extends \PHPUnit\Framework\TestCase
{
    private const DIMENSION_ORIGINAL = 'product_original';
    private const DIMENSION_LARGE = 'product_large';
    private const DIMENSION_SMALL = 'product_small';
    private const DIMENSION_CUSTOM = 'product_custom';

    /** @var ThemeManager|\PHPUnit\Framework\MockObject\MockObject */
    private $themeManager;

    /** @var ImageTypeProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->themeManager = $this->createMock(ThemeManager::class);

        $this->provider = new ImageTypeProvider($this->themeManager);
    }

    public function testGetImageTypes()
    {
        $theme1MainDimensions = [self::DIMENSION_ORIGINAL, self::DIMENSION_LARGE];
        $theme1ListingDimensions = [self::DIMENSION_ORIGINAL];
        $theme2ListingDimensions = [self::DIMENSION_ORIGINAL, self::DIMENSION_CUSTOM];

        $this->prepareThemeManager($theme1MainDimensions, $theme1ListingDimensions, $theme2ListingDimensions);

        $imageTypes = $this->provider->getImageTypes();

        self::assertCount(2, $imageTypes);
        self::assertValidImageType($imageTypes['main'], 'main', 'Main', 1, $theme1MainDimensions);
        self::assertValidImageType($imageTypes['listing'], 'listing', 'Listing', 5, $theme2ListingDimensions);
        self::assertTrue($imageTypes['main']->getDimensions()['product_large']->getOption('option1'));
    }

    public function testInvalidConfig()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->themeManager->expects(self::any())
            ->method('getAllThemes')
            ->willReturn([
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
        self::assertCount(4, $dimensions);
        self::assertEquals(400, $dimensionLarge->getWidth());
        self::assertEquals(400, $dimensionLarge->getHeight());
        self::assertTrue($dimensionLarge->getOption('option1'));
    }

    private function prepareTheme(string $name, array $imageTypes, array $dimensions): Theme
    {
        $config = [
            'images' => [
                'types' => [],
                'dimensions' => []
            ]
        ];

        foreach ($imageTypes as $key => $imageType) {
            [$label, $maxNumber, $dimensionNames] = $imageType;
            $config['images']['types'][$key] = [
                'label' => $label,
                'dimensions' => $dimensionNames,
                'max_number' => $maxNumber
            ];
        }

        foreach ($dimensions as $dimensionName => $dimension) {
            [$width, $height] = $dimension;
            $config['images']['dimensions'][$dimensionName] = ['width' => $width, 'height' => $height];

            if (isset($dimension[2])) {
                $config['images']['dimensions'][$dimensionName]['options'] = $dimension[2];
            }
        }

        $theme = new Theme($name);
        $theme->setConfig($config);

        return $theme;
    }

    private static function assertValidImageType(
        ThemeImageType $imageType,
        string $name,
        string $label,
        int $maxNumber,
        array $dimensions
    ): void {
        self::assertEquals($name, $imageType->getName());
        self::assertEquals($label, $imageType->getLabel());
        self::assertEquals($maxNumber, $imageType->getMaxNumber());
        self::assertCount(count($dimensions), $imageType->getDimensions());
        self::assertEquals($dimensions, array_keys($imageType->getDimensions()));
    }

    private function prepareThemeManager(
        array $theme1MainDimensions,
        array $theme1ListingDimensions,
        array $theme2ListingDimensions
    ): void {
        $this->themeManager->expects(self::any())
            ->method('getAllThemes')
            ->willReturn([
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

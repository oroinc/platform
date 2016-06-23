<?php

namespace LayoutBundle\Tests\Unit\Provider;

use Oro\Bundle\LayoutBundle\Model\ThemeImageType;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;

class ImageTypeProviderTest extends \PHPUnit_Framework_TestCase
{
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
        $theme1MainDimensions = [
            ['width' => 100, 'height' => 100],
            ['width' => 1000, 'height' => 1000]
        ];
        $theme1ListingDimensions = [
            ['width' => 10, 'height' => 10]
        ];
        $theme2ListingDimensions = [
            ['width' => 10, 'height' => 10],
            ['width' => 200, 'height' => 200]
        ];
        $this->themeManager->getAllThemes()->willReturn([
            $this->prepareTheme('theme1', [
                'main' => ['Main', 1, $theme1MainDimensions],
                'listing' => ['Listing', 3, $theme1ListingDimensions],
            ]),
            $this->prepareTheme('theme2', [
                'listing' => ['Listing', 5, $theme2ListingDimensions],
            ]),
            $this->prepareTheme('theme3', [])
        ]);

        $imageTypes = $this->provider->getImageTypes();

        $this->assertCount(2, $imageTypes);
        $this->assertValidImageType($imageTypes['main'], 'main', 'Main', 1, [[100, 100], [1000, 1000]]);
        $this->assertValidImageType($imageTypes['listing'], 'listing', 'Listing', 5, [[10, 10], [200, 200]]);
    }

    /**
     * @param string $name
     * @param array $imageTypes
     * @return Theme
     */
    private function prepareTheme($name, array $imageTypes)
    {
        $data = [
            'images' => [
                'types' => []
            ]
        ];

        foreach ($imageTypes as $key => $imageType) {
            list($label, $maxNumber, $dimensions) = $imageType;
            $data['images']['types'][$key] = [
                'label' => $label,
                'dimensions' => $dimensions,
                'max_number' => $maxNumber
            ];
        }

        $theme = new Theme($name);
        $theme->setData($data);

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

        foreach ($dimensions as $dimension) {
            $this->assertHasDimension($imageType, $dimension[0], $dimension[1]);
        }
    }

    /**
     * @param ThemeImageType $imageType
     * @param int $width
     * @param int $height
     */
    private function assertHasDimension(ThemeImageType $imageType, $width, $height)
    {
        $hasDimension = false;

        foreach ($imageType->getDimensions() as $dimension) {
            if ($dimension->getWidth() === $width && $dimension->getHeight() === $height) {
                $hasDimension = true;
                break;
            }
        }

        $this->assertTrue($hasDimension, sprintf('Image type does not contain dimension %dx%d', $width, $height));
    }
}

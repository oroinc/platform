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
        $this->themeManager->getAllThemes()->willReturn([
            $this->prepareTheme('theme1', [
                'main' => ['Main', 1],
                'listing' => ['Listing', 3],
            ]),
            $this->prepareTheme('theme2', [
                'listing' => ['Listing', 5],
            ]),
            $this->prepareTheme('theme3', [])
        ]);

        $imageTypes = $this->provider->getImageTypes();

        $this->assertCount(2, $imageTypes);
        $this->assertValidImageType($imageTypes['main'], 'main', 'Main', 1);
        $this->assertValidImageType($imageTypes['listing'], 'listing', 'Listing', 5);
    }

    /**
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
            list($label, $maxNumber) = $imageType;
            $data['images']['types'][$key] = [
                'label' => $label,
                'dimensions' => [],
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
     */
    private function assertValidImageType(ThemeImageType $imageType, $name, $label, $maxNumber)
    {
        $this->assertEquals($name, $imageType->getName());
        $this->assertEquals($label, $imageType->getLabel());
        $this->assertEquals($maxNumber, $imageType->getMaxNumber());
    }
}

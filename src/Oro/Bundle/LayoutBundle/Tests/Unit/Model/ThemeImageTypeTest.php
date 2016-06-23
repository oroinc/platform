<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Model;

use Oro\Bundle\LayoutBundle\Model\ThemeImageType;
use Oro\Bundle\LayoutBundle\Model\ThemeImageTypeDimension;

class ThemeImageTypeTest extends \PHPUnit_Framework_TestCase
{
    const NAME = 'main';
    const LABEL = 'Main';
    const MAX_NUMBER = 1;

    /**
     * @var array
     */
    protected $initialDimensions = [
        ['width' => 10, 'height' => 20],
        ['width' => 50, 'height' => 50]
    ];

    /**
     * @var ThemeImageType
     */
    protected $imageType;

    public function setUp()
    {
        $this->imageType = new ThemeImageType(
            self::NAME,
            self::LABEL,
            $this->initialDimensions,
            self::MAX_NUMBER
        );
    }

    public function testAccessors()
    {
        $this->assertEquals(self::NAME, $this->imageType->getName());
        $this->assertEquals(self::LABEL, $this->imageType->getLabel());
        $this->assertEquals(self::MAX_NUMBER, $this->imageType->getMaxNumber());
        $this->assertCount(count($this->initialDimensions), $this->imageType->getDimensions());

        $this->assertHasDimension(10, 20);
        $this->assertHasDimension(50, 50);
    }

    public function testMergeDimensions()
    {
        $this->imageType->mergeDimensions([
            new ThemeImageTypeDimension(10, 20),
            new ThemeImageTypeDimension(20, 10)
        ]);

        $this->assertCount(3, $this->imageType->getDimensions());

        $this->assertHasDimension(10, 20);
        $this->assertHasDimension(20, 10);
        $this->assertHasDimension(50, 50);
    }

    /**
     * @param int $width
     * @param int $height
     */
    private function assertHasDimension($width, $height)
    {
        $hasDimension = false;

        foreach ($this->imageType->getDimensions() as $dimension) {
            if ($dimension->getWidth() === $width && $dimension->getHeight() === $height) {
                $hasDimension = true;
                break;
            }
        }

        $this->assertTrue($hasDimension, sprintf('Image type does not contain dimension %dx%d', $width, $height));
    }
}

<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Model;

use Oro\Bundle\LayoutBundle\Model\ThemeImageType;
use Oro\Bundle\LayoutBundle\Model\ThemeImageTypeDimension;

class ThemeImageTypeTest extends \PHPUnit\Framework\TestCase
{
    const NAME = 'main';
    const LABEL = 'Main';
    const MAX_NUMBER = 1;

    const DIMENSION_1 = 'dim1';
    const DIMENSION_2 = 'dim2';
    const DIMENSION_3 = 'dim3';

    /**
     * @var ThemeImageType
     */
    protected $imageType;

    public function setUp()
    {
        $this->imageType = new ThemeImageType(
            self::NAME,
            self::LABEL,
            $this->prepareInitialDimensions(),
            self::MAX_NUMBER
        );
    }

    public function testAccessors()
    {
        $this->assertEquals(self::NAME, $this->imageType->getName());
        $this->assertEquals(self::LABEL, $this->imageType->getLabel());
        $this->assertEquals(self::MAX_NUMBER, $this->imageType->getMaxNumber());
        $this->assertCount(count($this->prepareInitialDimensions()), $this->imageType->getDimensions());

        $this->assertHasDimension(10, 20);
        $this->assertHasDimension(50, 50);
    }

    public function testMergeDimensions()
    {
        $this->imageType->mergeDimensions([
            new ThemeImageTypeDimension(self::DIMENSION_1, 20, 10),
            new ThemeImageTypeDimension(self::DIMENSION_3, 100, 200)
        ]);

        $this->assertCount(3, $this->imageType->getDimensions());

        $this->assertHasDimension(100, 200);
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

    /**
     * @return ThemeImageTypeDimension[]
     */
    private function prepareInitialDimensions()
    {
        return [
            new ThemeImageTypeDimension(self::DIMENSION_1, 10, 20),
            new ThemeImageTypeDimension(self::DIMENSION_2, 50, 50),
        ];
    }
}

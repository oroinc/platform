<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Model;

use Oro\Bundle\LayoutBundle\Model\ThemeImageTypeDimension;

class ThemeImageTypeDimensionTest extends \PHPUnit\Framework\TestCase
{
    private const NAME = 'dim';
    private const WIDTH = 100;
    private const HEIGHT = 200;
    private const OPTION_1 = 'option1';
    private const OPTION_2 = 'option2';

    private ThemeImageTypeDimension $imageTypeDimension;

    protected function setUp(): void
    {
        $this->imageTypeDimension = new ThemeImageTypeDimension(self::NAME, self::WIDTH, self::HEIGHT);
    }

    public function testAccessors(): void
    {
        self::assertEquals(self::NAME, $this->imageTypeDimension->getName());
        self::assertEquals(self::WIDTH, $this->imageTypeDimension->getWidth());
        self::assertEquals(self::HEIGHT, $this->imageTypeDimension->getHeight());
    }

    public function testHasOption(): void
    {
        $this->imageTypeDimension = new ThemeImageTypeDimension(self::NAME, self::WIDTH, self::HEIGHT, [
            self::OPTION_1 => 1,
        ]);

        self::assertTrue($this->imageTypeDimension->hasOption(self::OPTION_1));
        self::assertFalse($this->imageTypeDimension->hasOption(self::OPTION_2));
    }

    public function testGetOption(): void
    {
        $optionValue = 1;
        $this->imageTypeDimension = new ThemeImageTypeDimension(self::NAME, self::WIDTH, self::HEIGHT, [
            self::OPTION_1 => $optionValue,
        ]);

        self::assertEquals($optionValue, $this->imageTypeDimension->getOption(self::OPTION_1));
        self::assertNull($this->imageTypeDimension->getOption(self::OPTION_2));
    }

    public function testGetOptions(): void
    {
        $optionValue = 1;
        $this->imageTypeDimension = new ThemeImageTypeDimension(self::NAME, self::WIDTH, self::HEIGHT, [
            self::OPTION_1 => $optionValue,
        ]);

        self::assertEquals([self::OPTION_1 => $optionValue], $this->imageTypeDimension->getOptions());
    }
}

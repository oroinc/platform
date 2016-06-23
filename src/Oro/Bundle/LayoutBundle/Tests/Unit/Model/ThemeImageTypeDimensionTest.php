<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Model;

use Oro\Bundle\LayoutBundle\Model\ThemeImageTypeDimension;

class ThemeImageTypeDimensionTest extends \PHPUnit_Framework_TestCase
{
    const WIDTH = 100;
    const HEIGHT = 200;

    /**
     * @var ThemeImageTypeDimension
     */
    protected $imageTypeDimension;

    public function setUp()
    {
        $this->imageTypeDimension = new ThemeImageTypeDimension(self::WIDTH, self::HEIGHT);
    }

    public function testAccessors()
    {
        $this->assertEquals(self::WIDTH, $this->imageTypeDimension->getWidth());
        $this->assertEquals(self::HEIGHT, $this->imageTypeDimension->getHeight());
    }

    public function testToString()
    {
        $this->assertEquals(self::WIDTH . '_' . self::HEIGHT, (string) $this->imageTypeDimension);
    }
}
